<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ConfiguracaoEstabelecimento;
use App\Models\ConfiguracaoEsusPec;
use App\Models\HistoricoSincronizacaoEsus;
use App\Models\Paciente;
use App\Rules\CnsValido;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class EsusPecIntegrationService
{
    public const LOCK_NAME = 'lock_sincronizacao_esus_pec';

    public const LOCK_TTL_SEGUNDOS = 7200; // 2 horas

    protected static ?PDO $pdoTeste = null;

    /**
     * Define uma conexão PDO de teste para mock em testes automatizados.
     */
    public static function definirPdoTeste(?PDO $pdo): void
    {
        self::$pdoTeste = $pdo;
    }

    /**
     * Retorna o PDO de teste caso configurado.
     */
    public static function obterPdoTeste(): ?PDO
    {
        return self::$pdoTeste;
    }

    /**
     * Verifica se existe sincronização atualmente em andamento.
     */
    public function verificarExecucaoAtiva(?int $ignorarHistoricoId = null): ?HistoricoSincronizacaoEsus
    {
        $query = HistoricoSincronizacaoEsus::where('status', 'EM_ANDAMENTO')
            ->orderByDesc('id');

        if ($ignorarHistoricoId !== null) {
            $query->where('id', '!=', $ignorarHistoricoId);
        }

        return $query->first();
    }

    /**
     * Instancia conexão PDO independente e segura para leitura exclusiva do PostgreSQL.
     */
    public function criarConexaoPdo(?ConfiguracaoEsusPec $config = null): PDO
    {
        if (self::$pdoTeste !== null) {
            return self::$pdoTeste;
        }

        if (! extension_loaded('pdo_pgsql')) {
            throw new RuntimeException('A extensão PHP pdo_pgsql não está habilitada neste servidor.');
        }

        $config ??= ConfiguracaoEsusPec::atual();

        $host = trim($config->host ?: 'localhost');
        $porta = (int) ($config->porta ?: 5433);
        $banco = trim($config->banco ?: 'esus');
        $usuario = trim($config->usuario ?: 'esus_leitura');
        $senha = (string) ($config->senha ?? '');

        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $porta, $banco);

        $opcoes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 4, // Timeout curto para não congelar requisições web
        ];

        try {
            $pdo = new PDO($dsn, $usuario, $senha, $opcoes);

            // Blindagem de segurança: assegura transação READ ONLY na sessão PostgreSQL
            try {
                $pdo->exec('SET default_transaction_read_only = on;');
            } catch (Throwable) {
                // Silencioso se o usuário ou banco já for restrito
            }

            return $pdo;
        } catch (PDOException $e) {
            $mensagemTratada = $this->sanitizarMensagemErroConexao($e->getMessage(), $host, $porta, $usuario);
            throw new RuntimeException($mensagemTratada, (int) $e->getCode(), $e);
        }
    }

    /**
     * Valida conectividade básica com SELECT 1 e diagnostica o schema e-SUS.
     *
     * @return array{sucesso: bool, mensagem: string, latencia_ms: ?float, diagnostico: ?array<string, mixed>}
     */
    public function testarConexao(?ConfiguracaoEsusPec $config = null, ?PDO $pdoInjetado = null): array
    {
        $config ??= ConfiguracaoEsusPec::atual();
        $inicio = microtime(true);

        try {
            $pdo = $pdoInjetado ?? $this->criarConexaoPdo($config);
            $stmt = $pdo->query('SELECT 1 as teste;');
            $resultado = $stmt ? $stmt->fetchColumn() : null;

            if ((int) $resultado !== 1) {
                throw new RuntimeException('O banco de dados não respondeu à consulta de verificação.');
            }

            $latencia = round((microtime(true) - $inicio) * 1000, 2);
            $diagnostico = $this->descobrirSchema($pdo);

            $config->update([
                'ultimo_status_conexao' => 'SUCESSO',
                'mensagem_status_conexao' => sprintf('Conexão ativa e validada via PostgreSQL (latência: %s ms).', $latencia),
                'tipo_banco_detectado' => $diagnostico['tipo_banco'],
                'tabela_origem_detectada' => $diagnostico['tabela_origem'],
                'total_tabelas_descobertas' => $diagnostico['total_tabelas'],
                'coluna_atualizacao_detectada' => $diagnostico['coluna_atualizacao'] ?? null,
                'coluna_sequencial_detectada' => $diagnostico['coluna_sequencial'] ?? null,
                'detalhes_schema' => $diagnostico,
                'ultima_conexao_em' => now(),
            ]);

            return [
                'sucesso' => true,
                'mensagem' => sprintf('Conexão realizada com sucesso via PostgreSQL (latência: %s ms).', $latencia),
                'latencia_ms' => $latencia,
                'diagnostico' => $diagnostico,
            ];
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $config->update([
                'ultimo_status_conexao' => 'ERRO',
                'mensagem_status_conexao' => $msg,
                'ultima_conexao_em' => now(),
            ]);

            return [
                'sucesso' => false,
                'mensagem' => $msg,
                'latencia_ms' => null,
                'diagnostico' => null,
            ];
        }
    }

    /**
     * Inspeciona information_schema para descobrir tabelas, colunas e identificar DW vs Operacional.
     *
     * @return array{
     *     tipo_banco: string,
     *     tabela_origem: ?string,
     *     compativel_importacao: bool,
     *     versao_pec: ?string,
     *     total_tabelas: int,
     *     tabelas_descobertas: array<int, string>,
     *     colunas_origem: array<int, string>,
     *     coluna_atualizacao: ?string,
     *     coluna_sequencial: ?string,
     *     suporta_incremental: bool,
     *     diagnostico_texto: string
     * }
     */
    public function descobrirSchema(PDO $pdo): array
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $tabelas = [];
        if ($driver === 'pgsql') {
            $stmt = $pdo->query("
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema NOT IN ('pg_catalog', 'information_schema')
                ORDER BY table_name;
            ");
            if ($stmt) {
                $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            }
        } elseif ($driver === 'sqlite') {
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type IN ('table', 'view');");
            if ($stmt) {
                $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            }
        }

        $temTabelaDw = in_array('tb_acomp_cidadaos_vinculados', $tabelas, true);
        $temCidadaoOperacional = in_array('tb_cidadao', $tabelas, true);

        // Identifica a versão do e-SUS se houver tabela de versão
        $versaoPec = null;
        if (in_array('tb_versao_sistema', $tabelas, true)) {
            try {
                $stmtVersao = $pdo->query('SELECT nu_versao FROM tb_versao_sistema ORDER BY 1 DESC LIMIT 1;');
                if ($stmtVersao) {
                    $versaoPec = (string) $stmtVersao->fetchColumn();
                }
            } catch (Throwable) {
                // Silencioso se não houver coluna nu_versao
            }
        }

        $colunasOrigem = [];
        $tabelaOrigem = null;
        $tipoBanco = 'DESCONHECIDO';
        $compativelImportacao = false;

        if ($temTabelaDw) {
            $tipoBanco = 'DW_PEC';
            $tabelaOrigem = 'tb_acomp_cidadaos_vinculados';
            $compativelImportacao = true;
            $colunasOrigem = $this->obterColunasTabela($pdo, 'tb_acomp_cidadaos_vinculados', $driver);
            $diagnosticoTexto = 'Banco identificado como DW e-SUS APS PEC oficial com a tabela tb_acomp_cidadaos_vinculados disponível para leitura.';
        } elseif ($temCidadaoOperacional) {
            $tipoBanco = 'PEC_OPERACIONAL';
            $tabelaOrigem = 'tb_cidadao';
            $compativelImportacao = false; // Restrição: apenas DW oficial com dados consolidados e não cifrados
            $colunasOrigem = $this->obterColunasTabela($pdo, 'tb_cidadao', $driver);
            $diagnosticoTexto = 'Banco identificado como PEC Operacional (tb_cidadao). Conforme diretriz de segurança, a importação requer a estrutura de DW consolidada (tb_acomp_cidadaos_vinculados).';
        } else {
            $diagnosticoTexto = 'Banco conectado, porém não foram localizadas as tabelas oficiais do e-SUS PEC (tb_acomp_cidadaos_vinculados ou tb_cidadao).';
        }

        // Diagnóstico de colunas temporais de atualização cadastral (sem presumir nomes fixos)
        $colunaAtualizacao = null;
        foreach (['dt_atualizacao', 'dt_modificacao', 'dt_alteracao', 'dt_ultima_atualizacao', 'dt_registro', 'dt_cadastro'] as $candData) {
            if (in_array($candData, $colunasOrigem, true)) {
                $colunaAtualizacao = $candData;
                break;
            }
        }

        // Diagnóstico de coluna de identificação sequencial única e estável para Keyset Pagination
        $colunaSequencial = null;
        foreach (['co_seq_acomp_cidadaos_vinc', 'co_seq_acomp_cidadaos_vinculados', 'co_fat_cidadao_pec', 'co_seq_fat_cidadao_pec', 'id', 'id_cidadao'] as $candSeq) {
            if (in_array($candSeq, $colunasOrigem, true)) {
                $colunaSequencial = $candSeq;
                break;
            }
        }

        $suportaIncremental = ($colunaAtualizacao !== null);

        if ($compativelImportacao) {
            if ($suportaIncremental) {
                $diagnosticoTexto .= sprintf(' Coluna de atualização cadastral detectada: %s. Sincronização incremental ativa.', $colunaAtualizacao);
            } else {
                $diagnosticoTexto .= ' Tabela de DW sem coluna nativa de atualização temporal. Ativada Reconciliação Completa Otimizada via Keyset Pagination com projeção reduzida.';
            }
        }

        return [
            'tipo_banco' => $tipoBanco,
            'tabela_origem' => $tabelaOrigem,
            'compativel_importacao' => $compativelImportacao,
            'versao_pec' => $versaoPec,
            'total_tabelas' => count($tabelas),
            'tabelas_descobertas' => array_values(array_slice($tabelas, 0, 50)),
            'colunas_origem' => $colunasOrigem,
            'coluna_atualizacao' => $colunaAtualizacao,
            'coluna_sequencial' => $colunaSequencial,
            'suporta_incremental' => $suportaIncremental,
            'diagnostico_texto' => $diagnosticoTexto,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function obterColunasTabela(PDO $pdo, string $tabela, string $driver): array
    {
        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare('
                SELECT column_name
                FROM information_schema.columns
                WHERE table_name = :tabela
                ORDER BY ordinal_position;
            ');
            $stmt->execute(['tabela' => $tabela]);

            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }

        if ($driver === 'sqlite') {
            $stmt = $pdo->query("PRAGMA table_info({$tabela});");
            if ($stmt) {
                $cols = [];
                foreach ($stmt->fetchAll() as $row) {
                    $cols[] = $row['name'] ?? '';
                }

                return array_filter($cols);
            }
        }

        return [];
    }

    /**
     * Executa o processo de sincronização unidirecional e-SUS PEC -> CER Digital.
     */
    public function sincronizarPacientes(
        string $tipoExecucao = 'MANUAL',
        ?PDO $pdoInjetado = null,
        ?int $historicoId = null,
        ?callable $onProgresso = null,
        ?string $forcarModo = null,
        ?int $tamanhoLote = null,
        ?int $maxRegistros = null,
        bool $dryRun = false,
        bool $reiniciar = false,
        ?callable $onLoteConcluido = null
    ): HistoricoSincronizacaoEsus {
        $inicio = microtime(true);
        $duracaoConsultaMs = 0;
        $duracaoProcessamentoMs = 0;
        $duracaoGravacaoMs = 0;

        $lock = $this->assegurarLockConcorrencia($historicoId);

        try {
            $config = ConfiguracaoEsusPec::atual();

            $historico = null;
            if ($historicoId) {
                $historico = HistoricoSincronizacaoEsus::find($historicoId);
            }

            if (! $historico) {
                $historico = HistoricoSincronizacaoEsus::create([
                    'iniciado_em' => now(),
                    'tipo_execucao' => in_array($tipoExecucao, ['CLI', 'AGENDADA', 'MANUAL'], true) ? $tipoExecucao : 'MANUAL',
                    'status' => 'EM_ANDAMENTO',
                    'total_consultado' => 0,
                    'novos_pacientes' => 0,
                    'pacientes_atualizados' => 0,
                    'registros_ignorados' => 0,
                    'duplicidades_identificadas' => 0,
                    'detalhes' => [
                        'etapa' => 'Iniciando sincronização',
                        'percentual' => 0,
                        'dry_run' => $dryRun,
                    ],
                ]);
            }

            if (! $config->ativo && $pdoInjetado === null && self::$pdoTeste === null) {
                $historico->update([
                    'status' => 'INTERROMPIDO',
                    'finalizado_em' => now(),
                    'duracao_segundos' => 0,
                    'mensagem_erro' => 'A sincronização não foi executada porque a integração e-SUS APS PEC está inativa nas configurações.',
                    'detalhes' => array_merge($historico->detalhes ?? [], [
                        'etapa' => 'Integração inativa',
                        'percentual' => 0,
                    ]),
                ]);

                return $historico;
            }

            $contadores = [
                'validos' => 0,
                'invalidos' => 0,
                'sem_identificador' => 0,
                'novos' => 0,
                'atualizados' => 0,
                'sem_alteracoes' => 0,
                'duplicidades' => 0,
                'falhas' => 0,
            ];

            $this->atualizarProgressoExecucao(
                $historico, 'Consultando origem', 0, 0, $contadores, 'COMPLETA', $dryRun,
                $duracaoConsultaMs, $duracaoProcessamentoMs, $duracaoGravacaoMs, $onProgresso
            );

            $pdo = $pdoInjetado ?? $this->criarConexaoPdo($config);
            $diagnostico = $this->descobrirSchema($pdo);

            // Regra estrita: se a tabela de integração DW oficial não existir, interromper e diagnosticar
            if (! $diagnostico['compativel_importacao'] || empty($diagnostico['tabela_origem'])) {
                $historico->update([
                    'status' => 'INTERROMPIDO',
                    'finalizado_em' => now(),
                    'duracao_segundos' => (int) round(microtime(true) - $inicio),
                    'mensagem_erro' => sprintf(
                        'Sincronização interrompida: a tabela oficial do DW PEC (tb_acomp_cidadaos_vinculados) não foi encontrada. Banco detectado: %s. %s',
                        $diagnostico['tipo_banco'],
                        $diagnostico['diagnostico_texto']
                    ),
                    'detalhes' => [
                        'tipo_banco' => $diagnostico['tipo_banco'],
                        'total_tabelas' => $diagnostico['total_tabelas'],
                        'etapa' => 'Interrompido: ausência de tabela DW',
                        'percentual' => 0,
                    ],
                ]);

                if (! $dryRun) {
                    $config->update([
                        'ultima_sincronizacao_em' => now(),
                        'ultima_sincronizacao_status' => 'INTERROMPIDO',
                    ]);
                }

                return $historico;
            }

            $tabelaOrigem = $diagnostico['tabela_origem'];
            $ibgePadrao = ConfiguracaoEstabelecimento::atual()->codigo_ibge_padrao ?: '270430';

            // Determina a modalidade real de sincronização: incremental com janela ou reconciliação completa
            $colunaAtualizacao = $diagnostico['coluna_atualizacao'] ?? null;
            $colunaSequencial = $diagnostico['coluna_sequencial'] ?? null;
            $modoConfigurado = strtoupper((string) ($forcarModo ?: $config->modo_sincronizacao ?: 'AUTO'));

            $modoExecucaoReal = 'RECONCILIACAO_COMPLETA';
            $checkpointComJanela = null;

            if ($colunaAtualizacao !== null) {
                if ($modoConfigurado === 'COMPLETA') {
                    $modoExecucaoReal = 'CARGA_COMPLETA';
                } elseif ($config->ultimo_checkpoint_em !== null && ! $reiniciar) {
                    $modoExecucaoReal = 'INCREMENTAL';
                    $janelaMinutos = (int) ($config->janela_seguranca_minutos ?: 1440);
                    $checkpointComJanela = $config->ultimo_checkpoint_em->copy()->subMinutes($janelaMinutos);
                } else {
                    $modoExecucaoReal = 'CARGA_COMPLETA';
                }
            } else {
                $modoExecucaoReal = 'RECONCILIACAO_COMPLETA';
            }

            // Keyset Pagination / Checkpoint de retomada
            $retomarDeCheckpoint = ! $reiniciar
                && $config->ultimo_checkpoint_id
                && in_array($config->ultima_sincronizacao_status, ['PARCIAL', 'INTERROMPIDO', 'ERRO', 'EM_ANDAMENTO'], true);

            if ($reiniciar) {
                $ultimoIdChave = 0;
                if (! $dryRun) {
                    $config->update(['ultimo_checkpoint_id' => null]);
                }
            } elseif ($retomarDeCheckpoint) {
                $ultimoIdChave = (int) $config->ultimo_checkpoint_id;
            } else {
                $ultimoIdChave = 0;
            }

            $selectSql = $this->montarProjecaoColunas($diagnostico['colunas_origem'], $colunaAtualizacao, $colunaSequencial);

            $whereParts = [];
            $params = [];

            if ($modoExecucaoReal === 'INCREMENTAL' && $colunaAtualizacao && $checkpointComJanela) {
                $whereParts[] = "{$colunaAtualizacao} >= :checkpoint_data";
                $params[':checkpoint_data'] = $checkpointComJanela->format('Y-m-d H:i:s');
            }

            // Contagem do total de registros estimados a processar
            $countWhereParts = $whereParts;
            $countParams = $params;
            if ($colunaSequencial && $ultimoIdChave > 0) {
                $countWhereParts[] = "{$colunaSequencial} > :ultimo_id_chave_count";
                $countParams[':ultimo_id_chave_count'] = $ultimoIdChave;
            }
            $countWhereBase = ! empty($countWhereParts) ? 'WHERE '.implode(' AND ', $countWhereParts) : '';

            $tConsultaCountInicio = microtime(true);
            $totalEstimado = 0;
            try {
                $sqlCount = "SELECT COUNT(*) FROM {$tabelaOrigem} {$countWhereBase};";
                $stmtCount = $pdo->prepare($sqlCount);
                $stmtCount->execute($countParams);
                $totalEstimado = (int) $stmtCount->fetchColumn();
            } catch (Throwable) {
                $totalEstimado = 0;
            }
            $duracaoConsultaMs += (int) round((microtime(true) - $tConsultaCountInicio) * 1000);

            $this->atualizarProgressoExecucao(
                $historico, 'Consultando origem', 0, $totalEstimado, $contadores, $modoExecucaoReal, $dryRun,
                $duracaoConsultaMs, $duracaoProcessamentoMs, $duracaoGravacaoMs, $onProgresso
            );

            // Processamento em lotes configuráveis (padrão 2.000 via CLI ou 500 via web)
            $limite = $tamanhoLote !== null
                ? max(1, min(5000, $tamanhoLote))
                : max(100, min(2000, (int) ($config->tamanho_lote ?: 500)));

            $offset = 0;
            $totalConsultado = 0;
            $numeroLote = 0;

            while (true) {
                if ($maxRegistros !== null && $totalConsultado >= $maxRegistros) {
                    break;
                }

                $registrosRestantes = ($maxRegistros !== null) ? ($maxRegistros - $totalConsultado) : $limite;
                $limiteAtual = min($limite, $registrosRestantes);
                if ($limiteAtual <= 0) {
                    break;
                }

                $tConsultaLoteInicio = microtime(true);

                $whereLoop = $whereParts;
                $paramsLoop = $params;

                if ($colunaSequencial) {
                    $whereLoop[] = "{$colunaSequencial} > :ultimo_id_chave";
                    $paramsLoop[':ultimo_id_chave'] = $ultimoIdChave;
                    $whereSql = 'WHERE '.implode(' AND ', $whereLoop);
                    $sql = "SELECT {$selectSql} FROM {$tabelaOrigem} {$whereSql} ORDER BY {$colunaSequencial} ASC LIMIT {$limiteAtual};";
                } else {
                    $whereSql = ! empty($whereLoop) ? 'WHERE '.implode(' AND ', $whereLoop) : '';
                    $sql = "SELECT {$selectSql} FROM {$tabelaOrigem} {$whereSql} LIMIT {$limiteAtual} OFFSET {$offset};";
                }

                $stmt = $pdo->prepare($sql);
                $stmt->execute($paramsLoop);
                $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $duracaoConsultaMs += (int) round((microtime(true) - $tConsultaLoteInicio) * 1000);

                if (empty($linhas)) {
                    break;
                }

                $numeroLote++;
                $totalConsultado += count($linhas);

                $resLote = $this->processarLoteDeRegistros($linhas, $ibgePadrao, $contadores, $dryRun);

                $duracaoProcessamentoMs += $resLote['duracaoProcessamentoMs'];
                $duracaoGravacaoMs += $resLote['duracaoGravacaoMs'];

                // Atualização do cursor para Keyset Pagination
                if ($colunaSequencial) {
                    $ultimaLinha = end($linhas);
                    if (isset($ultimaLinha[$colunaSequencial])) {
                        $ultimoIdChave = (int) $ultimaLinha[$colunaSequencial];
                    }
                }
                $offset += $limiteAtual;

                // Checkpoint por lote para recuperação em caso de falhas (somente se gravação real)
                if (! $dryRun && $ultimoIdChave > 0) {
                    $config->update(['ultimo_checkpoint_id' => $ultimoIdChave]);
                }

                $duracaoLoteSegundos = round(microtime(true) - $tConsultaLoteInicio, 2);

                $metricasLote = [
                    'consultados' => count($linhas),
                    'validos' => $resLote['loteValidos'],
                    'invalidos' => $resLote['loteInvalidos'],
                    'sem_identificador' => $resLote['loteSemIdentificador'],
                    'novos' => $resLote['loteNovos'],
                    'atualizados' => $resLote['loteAtualizados'],
                    'sem_alteracoes' => $resLote['loteSemAlteracoes'],
                    'duplicidades' => $resLote['loteDuplicidades'],
                    'falhas' => $resLote['loteFalhas'],
                    'duracao_segundos' => $duracaoLoteSegundos,
                    'ultimo_id_chave' => $ultimoIdChave,
                ];

                if ($onLoteConcluido !== null) {
                    $onLoteConcluido($numeroLote, $metricasLote, $contadores, $totalConsultado, $totalEstimado);
                }

                $this->atualizarProgressoExecucao(
                    $historico, 'Processando lotes', $totalConsultado, $totalEstimado, $contadores, $modoExecucaoReal, $dryRun,
                    $duracaoConsultaMs, $duracaoProcessamentoMs, $duracaoGravacaoMs, $onProgresso
                );
            }

            $this->atualizarProgressoExecucao(
                $historico, 'Finalizando', $totalConsultado, $totalEstimado, $contadores, $modoExecucaoReal, $dryRun,
                $duracaoConsultaMs, $duracaoProcessamentoMs, $duracaoGravacaoMs, $onProgresso
            );

            $duracao = (int) round(microtime(true) - $inicio);
            $duracaoTotalMs = (int) round((microtime(true) - $inicio) * 1000);

            $atingiuLimiteMaxRegistros = ($maxRegistros !== null && $totalConsultado >= $maxRegistros);
            $existemRegistrosPendentes = false;

            if ($atingiuLimiteMaxRegistros) {
                try {
                    if ($colunaSequencial) {
                        $stmtCheck = $pdo->prepare("SELECT 1 FROM {$tabelaOrigem} WHERE {$colunaSequencial} > :ultimo_id LIMIT 1;");
                        $stmtCheck->execute([':ultimo_id' => $ultimoIdChave]);
                        $existemRegistrosPendentes = (bool) $stmtCheck->fetchColumn();
                    } else {
                        $stmtCheck = $pdo->prepare("SELECT 1 FROM {$tabelaOrigem} LIMIT 1 OFFSET :offset_check;");
                        $stmtCheck->execute([':offset_check' => $offset]);
                        $existemRegistrosPendentes = (bool) $stmtCheck->fetchColumn();
                    }
                } catch (Throwable) {
                    $existemRegistrosPendentes = true;
                }
            }

            [$statusFinal, $msgFinal] = $this->determinarStatusFinal(
                $contadores,
                $totalConsultado,
                $modoExecucaoReal,
                $dryRun,
                $atingiuLimiteMaxRegistros,
                $existemRegistrosPendentes,
                (int) ($maxRegistros ?? 0),
                $ultimoIdChave
            );

            $detalhesFinais = [
                'etapa' => 'Concluído',
                'percentual' => 100,
                'dry_run' => $dryRun,
                'tipo_banco' => $diagnostico['tipo_banco'],
                'tabela' => $tabelaOrigem,
                'versao_pec' => $diagnostico['versao_pec'],
                'modo_executado' => $modoExecucaoReal,
                'coluna_atualizacao' => $colunaAtualizacao,
                'coluna_sequencial' => $colunaSequencial,
                'paginacao' => $colunaSequencial ? 'KEYSET_PAGINATION' : 'OFFSET_LIMIT',
                'tamanho_lote' => $limite,
                'total_consultado' => $totalConsultado,
                'registros_validos' => $contadores['validos'],
                'registros_invalidos' => $contadores['invalidos'],
                'sem_identificador' => $contadores['sem_identificador'],
                'novos_pacientes' => $contadores['novos'],
                'pacientes_atualizados' => $contadores['atualizados'],
                'pacientes_sem_alteracoes' => $contadores['sem_alteracoes'],
                'duplicidades' => $contadores['duplicidades'],
                'falhas_gravacao' => $contadores['falhas'],
                'duracao_consulta_ms' => $duracaoConsultaMs,
                'duracao_processamento_ms' => $duracaoProcessamentoMs,
                'duracao_gravacao_ms' => $duracaoGravacaoMs,
                'duracao_total_ms' => $duracaoTotalMs,
                'ultimo_id_processado' => $ultimoIdChave,
                'mensagem_resumo' => $msgFinal,
            ];

            $historico->update([
                'status' => $statusFinal,
                'finalizado_em' => now(),
                'duracao_segundos' => $duracao,
                'total_consultado' => $totalConsultado,
                'novos_pacientes' => $contadores['novos'],
                'pacientes_atualizados' => $contadores['atualizados'],
                'registros_ignorados' => $contadores['invalidos'] + $contadores['sem_identificador'],
                'duplicidades_identificadas' => $contadores['duplicidades'],
                'mensagem_erro' => in_array($statusFinal, ['SUCESSO', 'PARCIAL', 'DRY_RUN'], true) ? null : $msgFinal,
                'detalhes' => $detalhesFinais,
            ]);

            // Persiste checkpoint após confirmação de gravação (apenas se não for dry-run)
            if (! $dryRun) {
                if (in_array($statusFinal, ['SUCESSO', 'PARCIAL'], true)) {
                    $config->update([
                        'ultima_sincronizacao_em' => now(),
                        'ultima_sincronizacao_status' => $statusFinal,
                        'ultimo_checkpoint_em' => now(),
                        'ultimo_checkpoint_id' => $ultimoIdChave ?: $config->ultimo_checkpoint_id,
                        'coluna_atualizacao_detectada' => $colunaAtualizacao,
                        'coluna_sequencial_detectada' => $colunaSequencial,
                    ]);
                } else {
                    $config->update([
                        'ultima_sincronizacao_em' => now(),
                        'ultima_sincronizacao_status' => $statusFinal,
                    ]);
                }
            }

            Cache::put("esus_sync_progresso_{$historico->id}", [
                'historico_id' => $historico->id,
                'etapa' => 'Concluído',
                'processados' => $totalConsultado,
                'total' => $totalConsultado,
                'percentual' => 100,
                'dry_run' => $dryRun,
                'modo' => $modoExecucaoReal,
                'contadores' => $contadores,
                'duracao_consulta_ms' => $duracaoConsultaMs,
                'duracao_processamento_ms' => $duracaoProcessamentoMs,
                'duracao_gravacao_ms' => $duracaoGravacaoMs,
                'status' => $statusFinal,
            ], 300);

            return $historico;
        } catch (Throwable $e) {
            $duracao = (int) round(microtime(true) - $inicio);
            $msgErro = $e->getMessage();

            if (isset($historico) && $historico instanceof HistoricoSincronizacaoEsus) {
                $historico->update([
                    'status' => 'ERRO',
                    'finalizado_em' => now(),
                    'duracao_segundos' => $duracao,
                    'mensagem_erro' => $msgErro,
                    'detalhes' => array_merge($historico->detalhes ?? [], [
                        'etapa' => 'Erro na execução',
                        'falhas_gravacao' => ($contadores['falhas'] ?? 0) + 1,
                        'duracao_consulta_ms' => $duracaoConsultaMs,
                        'duracao_processamento_ms' => $duracaoProcessamentoMs,
                        'duracao_gravacao_ms' => $duracaoGravacaoMs,
                    ]),
                ]);
            }

            if (! $dryRun) {
                ConfiguracaoEsusPec::atual()->update([
                    'ultima_sincronizacao_em' => now(),
                    'ultima_sincronizacao_status' => 'ERRO',
                ]);
            }

            return $historico ?? throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * Adquire o bloqueio atômico de concorrência e valida execuções em andamento.
     */
    protected function assegurarLockConcorrencia(?int $historicoId): mixed
    {
        $lock = Cache::lock(self::LOCK_NAME, self::LOCK_TTL_SEGUNDOS);
        if (! $lock->get()) {
            $ativa = $this->verificarExecucaoAtiva($historicoId);
            $origem = $ativa
                ? sprintf('%s (ID #%d, iniciada em %s)', $ativa->tipo_execucao, $ativa->id, $ativa->iniciado_em?->format('d/m/Y H:i:s') ?? 'momento desconhecido')
                : 'outro processo ativo';

            throw new RuntimeException("Já existe uma sincronização com o e-SUS PEC em andamento por {$origem}. Aguarde a conclusão ou libere o bloqueio.");
        }

        $ativa = $this->verificarExecucaoAtiva($historicoId);
        if ($ativa) {
            $limiteStale = now()->subHours(2);
            if ($ativa->iniciado_em && $ativa->iniciado_em->greaterThan($limiteStale)) {
                $lock->release();
                $origem = sprintf('%s (ID #%d, iniciada em %s)', $ativa->tipo_execucao, $ativa->id, $ativa->iniciado_em->format('d/m/Y H:i:s'));

                throw new RuntimeException("Já existe uma sincronização com o e-SUS PEC em andamento por {$origem}. Aguarde a conclusão.");
            }

            // Recupera e encerra execuções estagnadas há mais de 2 horas
            $ativa->update([
                'status' => 'INTERROMPIDO',
                'finalizado_em' => now(),
                'mensagem_erro' => 'Execução anterior expirou por inatividade (> 2 horas) e foi recuperada automaticamente.',
            ]);
        }

        return $lock;
    }

    /**
     * Monta a cláusula SELECT seletiva com base nas colunas existentes na tabela de origem.
     *
     * @param  array<int, string>  $colunasOrigem
     */
    protected function montarProjecaoColunas(array $colunasOrigem, ?string $colunaAtualizacao, ?string $colunaSequencial): string
    {
        $colunasDesejadas = [
            'co_seq_acomp_cidadaos_vinc',
            'co_seq_acomp_cidadaos_vinculados',
            'co_fat_cidadao_pec',
            'co_seq_fat_cidadao_pec',
            'co_cidadao',
            'co_seq_cidadao',
            'co_unico_cidadao',
            'co_seq_fat_cidadao_vinculado',
            'id_cidadao',
            'id',
            'no_cidadao',
            'nome',
            'nm_cidadao',
            'nome_cidadao',
            'no_social_cidadao',
            'nome_social',
            'dt_nascimento_cidadao',
            'dt_nascimento',
            'data_nascimento',
            'dt_nasc',
            'no_sexo_cidadao',
            'no_sexo',
            'sg_sexo',
            'co_sexo',
            'sexo',
            'nu_cpf_cidadao',
            'nu_cpf',
            'cpf',
            'num_cpf',
            'nu_cns_cidadao',
            'nu_cns',
            'cns',
            'num_cns',
            'no_mae_cidadao',
            'no_mae',
            'nome_mae',
            'nm_mae',
            'nu_telefone_celular',
            'nu_telefone_contato',
            'nu_telefone_residencial',
            'celular',
            'nu_telefone',
            'telefone',
            'telefone_secundario',
            'telefone_recado',
            'ds_logradouro_domicilio',
            'ds_logradouro_tb_cidadao',
            'ds_logradouro',
            'ds_endereco',
            'endereco',
            'ds_logradouro_completo',
            'nu_numero_domicilio',
            'nu_numero_tb_cidadao',
            'nu_numero',
            'numero',
            'ds_complemento_domicilio',
            'ds_complemento_tb_cidadao',
            'ds_complemento',
            'complemento',
            'no_bairro_domicilio',
            'no_bairro_tb_cidadao',
            'no_bairro',
            'bairro',
            'nm_bairro',
            'co_ibge_domicilio',
            'co_ibge_tb_cidadao',
            'co_ibge',
            'co_municipio_ibge',
            'municipio_ibge',
            'no_unidade_saude',
            'no_ubs',
            'no_equipe',
            'ds_equipe',
            'nu_cnes',
            'nu_ine',
            'ubs_referencia',
            'no_profissional_acs',
            'no_profissional',
            'no_acs',
            'nome_acs',
        ];

        if ($colunaAtualizacao) {
            $colunasDesejadas[] = $colunaAtualizacao;
        }
        if ($colunaSequencial) {
            $colunasDesejadas[] = $colunaSequencial;
        }

        $existentes = array_values(array_intersect(array_unique($colunasDesejadas), $colunasOrigem));

        return ! empty($existentes) ? implode(', ', $existentes) : '*';
    }

    /**
     * Atualiza o registro de histórico e o cache com o progresso em tempo real da sincronização.
     *
     * @param  array<string, int>  $contadores
     */
    protected function atualizarProgressoExecucao(
        HistoricoSincronizacaoEsus $historico,
        string $etapa,
        int $processados,
        int $total,
        array $contadores,
        string $modo,
        bool $dryRun,
        int $duracaoConsultaMs,
        int $duracaoProcessamentoMs,
        int $duracaoGravacaoMs,
        ?callable $onProgresso = null
    ): void {
        $percentual = $total > 0 ? (int) min(100, round(($processados / $total) * 100)) : 0;

        $detalhes = array_merge($historico->detalhes ?? [], [
            'etapa' => $etapa,
            'processados' => $processados,
            'total_estimado' => $total,
            'percentual' => $percentual,
            'dry_run' => $dryRun,
            'modo_executado' => $modo,
            'registros_validos' => $contadores['validos'],
            'registros_invalidos' => $contadores['invalidos'],
            'sem_identificador' => $contadores['sem_identificador'],
            'novos_pacientes' => $contadores['novos'],
            'pacientes_atualizados' => $contadores['atualizados'],
            'pacientes_sem_alteracoes' => $contadores['sem_alteracoes'],
            'duplicidades' => $contadores['duplicidades'],
            'falhas_gravacao' => $contadores['falhas'],
            'duracao_consulta_ms' => $duracaoConsultaMs,
            'duracao_processamento_ms' => $duracaoProcessamentoMs,
            'duracao_gravacao_ms' => $duracaoGravacaoMs,
        ]);

        $historico->update([
            'total_consultado' => $processados,
            'novos_pacientes' => $contadores['novos'],
            'pacientes_atualizados' => $contadores['atualizados'],
            'registros_ignorados' => $contadores['invalidos'] + $contadores['sem_identificador'],
            'duplicidades_identificadas' => $contadores['duplicidades'],
            'detalhes' => $detalhes,
        ]);

        Cache::put("esus_sync_progresso_{$historico->id}", [
            'historico_id' => $historico->id,
            'etapa' => $etapa,
            'processados' => $processados,
            'total' => $total,
            'percentual' => $percentual,
            'dry_run' => $dryRun,
            'modo' => $modo,
            'contadores' => $contadores,
            'duracao_consulta_ms' => $duracaoConsultaMs,
            'duracao_processamento_ms' => $duracaoProcessamentoMs,
            'duracao_gravacao_ms' => $duracaoGravacaoMs,
            'status' => $historico->status,
        ], 300);

        if ($onProgresso) {
            $onProgresso($etapa, $processados, $total, $contadores);
        }
    }

    /**
     * Processa a validação, matching e persistência ou simulação de um lote de registros.
     *
     * @param  array<int, array<string, mixed>>  $linhas
     * @param  array<string, int>  $contadores
     * @return array{
     *     loteValidos: int,
     *     loteInvalidos: int,
     *     loteSemIdentificador: int,
     *     loteNovos: int,
     *     loteAtualizados: int,
     *     loteSemAlteracoes: int,
     *     loteDuplicidades: int,
     *     loteFalhas: int,
     *     duracaoProcessamentoMs: int,
     *     duracaoGravacaoMs: int
     * }
     */
    protected function processarLoteDeRegistros(
        array $linhas,
        string $ibgePadrao,
        array &$contadores,
        bool $dryRun
    ): array {
        $tProcInicio = microtime(true);
        $duracaoGravacaoMs = 0;

        $loteNovos = 0;
        $loteAtualizados = 0;
        $loteSemAlteracoes = 0;
        $loteDuplicidades = 0;
        $loteFalhas = 0;
        $loteValidos = 0;
        $loteInvalidos = 0;
        $loteSemIdentificador = 0;

        $loteMapeado = [];
        $idsChunk = [];
        $cpfsChunk = [];
        $cnssChunk = [];

        foreach ($linhas as $linha) {
            $dados = $this->mapearCidadaoParaPaciente($linha, $ibgePadrao);

            if (empty($dados['nome']) || mb_strlen($dados['nome']) < 2 || empty($dados['data_nascimento'])) {
                $contadores['invalidos']++;
                $loteInvalidos++;

                continue;
            }

            if (empty($dados['id_externo_esus']) && empty($dados['cpf']) && empty($dados['cns'])) {
                $contadores['sem_identificador']++;
                $loteSemIdentificador++;

                continue;
            }

            $contadores['validos']++;
            $loteValidos++;
            $loteMapeado[] = $dados;

            if (! empty($dados['id_externo_esus'])) {
                $idsChunk[] = $dados['id_externo_esus'];
            }
            if (! empty($dados['cpf'])) {
                $cpfsChunk[] = $dados['cpf'];
            }
            if (! empty($dados['cns'])) {
                $cnssChunk[] = $dados['cns'];
            }
        }

        if (! empty($loteMapeado)) {
            $pacientesLocais = Paciente::where(function ($query) use ($idsChunk, $cpfsChunk, $cnssChunk) {
                if (! empty($idsChunk)) {
                    $query->orWhereIn('id_externo_esus', $idsChunk);
                }
                if (! empty($cpfsChunk)) {
                    $query->orWhereIn('cpf', $cpfsChunk);
                }
                if (! empty($cnssChunk)) {
                    $query->orWhereIn('cns', $cnssChunk);
                }
            })->get();

            $mapaId = [];
            $mapaCpf = [];
            $mapaCns = [];

            foreach ($pacientesLocais as $pac) {
                if (! empty($pac->id_externo_esus)) {
                    $mapaId[$pac->id_externo_esus] = $pac;
                }
                if (! empty($pac->cpf)) {
                    $mapaCpf[$pac->cpf][] = $pac;
                }
                if (! empty($pac->cns)) {
                    $mapaCns[$pac->cns][] = $pac;
                }
            }

            $tGravInicio = microtime(true);
            if ($dryRun) {
                foreach ($loteMapeado as $dadosPaciente) {
                    $res = $this->simularProcessamentoLoteComMapas($dadosPaciente, $mapaId, $mapaCpf, $mapaCns);
                    if ($res === 'NOVO') {
                        $contadores['novos']++;
                        $loteNovos++;
                    } elseif ($res === 'ATUALIZADO') {
                        $contadores['atualizados']++;
                        $loteAtualizados++;
                    } elseif ($res === 'SEM_ALTERACOES') {
                        $contadores['sem_alteracoes']++;
                        $loteSemAlteracoes++;
                    } elseif ($res === 'DUPLICIDADE') {
                        $contadores['duplicidades']++;
                        $loteDuplicidades++;
                    }
                }
            } else {
                DB::transaction(function () use (&$loteMapeado, &$contadores, &$mapaId, &$mapaCpf, &$mapaCns, &$loteNovos, &$loteAtualizados, &$loteSemAlteracoes, &$loteDuplicidades, &$loteFalhas) {
                    foreach ($loteMapeado as $dadosPaciente) {
                        $res = $this->processarRegistroPacienteComMapas($dadosPaciente, $mapaId, $mapaCpf, $mapaCns);

                        if ($res === 'NOVO') {
                            $contadores['novos']++;
                            $loteNovos++;
                        } elseif ($res === 'ATUALIZADO') {
                            $contadores['atualizados']++;
                            $loteAtualizados++;
                        } elseif ($res === 'SEM_ALTERACOES') {
                            $contadores['sem_alteracoes']++;
                            $loteSemAlteracoes++;
                        } elseif ($res === 'DUPLICIDADE') {
                            $contadores['duplicidades']++;
                            $loteDuplicidades++;
                        } elseif ($res === 'FALHA_GRAVACAO') {
                            $contadores['falhas']++;
                            $loteFalhas++;
                        }
                    }
                });
            }
            $duracaoGravacaoMs = (int) round((microtime(true) - $tGravInicio) * 1000);
        }

        $duracaoProcessamentoMs = (int) round((microtime(true) - $tProcInicio) * 1000) - $duracaoGravacaoMs;

        return [
            'loteValidos' => $loteValidos,
            'loteInvalidos' => $loteInvalidos,
            'loteSemIdentificador' => $loteSemIdentificador,
            'loteNovos' => $loteNovos,
            'loteAtualizados' => $loteAtualizados,
            'loteSemAlteracoes' => $loteSemAlteracoes,
            'loteDuplicidades' => $loteDuplicidades,
            'loteFalhas' => $loteFalhas,
            'duracaoProcessamentoMs' => max(0, $duracaoProcessamentoMs),
            'duracaoGravacaoMs' => $duracaoGravacaoMs,
        ];
    }

    /**
     * Determina o status final e a mensagem descritiva de encerramento da sincronização.
     *
     * @param  array<string, int>  $contadores
     * @return array{0: string, 1: string} [statusFinal, mensagemFinal]
     */
    protected function determinarStatusFinal(
        array $contadores,
        int $totalConsultado,
        string $modoExecucaoReal,
        bool $dryRun,
        bool $atingiuLimiteMaxRegistros,
        bool $existemRegistrosPendentes,
        int $maxRegistros,
        int $ultimoIdChave
    ): array {
        $sucessoEfetivo = ($contadores['novos'] + $contadores['atualizados'] + $contadores['sem_alteracoes']) > 0;

        if ($contadores['falhas'] > 0 && ! $sucessoEfetivo) {
            return ['ERRO', sprintf('A sincronização falhou: %d erros de gravação durante a persistência.', $contadores['falhas'])];
        }

        if ($dryRun) {
            return [
                'DRY_RUN',
                sprintf(
                    '[DRY-RUN] Simulação concluída (%s): %d consultados, %d seriam novos, %d atualizados, %d sem alterações, %d ignorados, %d duplicidades. Nenhum dado foi gravado.',
                    $modoExecucaoReal,
                    $totalConsultado,
                    $contadores['novos'],
                    $contadores['atualizados'],
                    $contadores['sem_alteracoes'],
                    $contadores['invalidos'] + $contadores['sem_identificador'],
                    $contadores['duplicidades']
                ),
            ];
        }

        if ($atingiuLimiteMaxRegistros && $existemRegistrosPendentes) {
            return [
                'PARCIAL',
                sprintf(
                    'Sincronização pausada após atingir o limite de %d registros (--max-registros=%d). Último checkpoint registrado: %d. Execute novamente sem --reiniciar para prosseguir.',
                    $totalConsultado,
                    $maxRegistros,
                    $ultimoIdChave
                ),
            ];
        }

        if ($totalConsultado > 0 && ! $sucessoEfetivo) {
            return [
                'INTERROMPIDO',
                sprintf(
                    'Consulta finalizada com %d registros, mas nenhum paciente foi importado ou atualizado. Motivos: %d registros inválidos, %d sem identificador mínimo confiável, %d duplicidades/conflitos.',
                    $totalConsultado,
                    $contadores['invalidos'],
                    $contadores['sem_identificador'],
                    $contadores['duplicidades']
                ),
            ];
        }

        if ($totalConsultado === 0 && $modoExecucaoReal === 'INCREMENTAL') {
            return ['SUCESSO', 'Sincronização incremental concluída: nenhum registro novo ou alterado no e-SUS PEC desde o último checkpoint.'];
        }

        return [
            'SUCESSO',
            sprintf(
                'Sincronização concluída com sucesso (%s): %d novos pacientes, %d atualizados, %d sem alterações (%d consultados no e-SUS PEC).',
                $modoExecucaoReal,
                $contadores['novos'],
                $contadores['atualizados'],
                $contadores['sem_alteracoes'],
                $totalConsultado
            ),
        ];
    }

    /**
     * Mapeia dinamicamente os nomes de colunas do DW PEC para os campos do CER Digital.
     *
     * @param  array<string, mixed>  $linha
     * @return array<string, mixed>
     */
    public function mapearCidadaoParaPaciente(array $linha, string $ibgePadrao): array
    {
        $resolver = function (array $possiveis) use ($linha): ?string {
            foreach ($possiveis as $campo) {
                if (isset($linha[$campo]) && trim((string) $linha[$campo]) !== '') {
                    return trim((string) $linha[$campo]);
                }
            }

            return null;
        };

        // Identificador pessoal único do cidadão no PEC (NÃO o identificador do vínculo)
        // co_seq_acomp_cidadaos_vinc e co_seq_acomp_cidadaos_vinculados representam o vínculo e são excluídos aqui
        $idExterno = $resolver([
            'co_fat_cidadao_pec',
            'co_seq_fat_cidadao_pec',
            'co_cidadao',
            'co_seq_cidadao',
            'co_unico_cidadao',
            'co_seq_fat_cidadao_vinculado',
            'id_cidadao',
        ]);

        // Nome completo (prioriza nome civil oficial no_cidadao, com fallback)
        $nome = $resolver(['no_cidadao', 'nome', 'nm_cidadao', 'nome_cidadao', 'no_social_cidadao']);
        $nomeSocial = $resolver(['no_social_cidadao', 'nome_social']);

        // CPF (apenas 11 números)
        $cpfCru = $resolver(['nu_cpf_cidadao', 'nu_cpf', 'cpf', 'num_cpf']);
        $cpfLimpo = $cpfCru ? (preg_replace('/\D/', '', $cpfCru) ?: null) : null;
        if ($cpfLimpo !== null && strlen($cpfLimpo) !== 11) {
            $cpfLimpo = null;
        }

        // CNS (apenas 15 números com validação DATASUS Módulo 11)
        $cnsCru = $resolver(['nu_cns_cidadao', 'nu_cns', 'cns', 'num_cns']);
        $cnsLimpo = $cnsCru ? (preg_replace('/\D/', '', $cnsCru) ?: null) : null;
        if ($cnsLimpo !== null && (! CnsValido::validar($cnsLimpo) || strlen($cnsLimpo) !== 15)) {
            $cnsLimpo = null;
        }

        // Data de nascimento
        $dtCru = $resolver(['dt_nascimento_cidadao', 'dt_nascimento', 'data_nascimento', 'dt_nasc']);
        $dtNascimento = null;
        if ($dtCru !== null) {
            try {
                $dtCarbon = Carbon::parse($dtCru);
                if ($dtCarbon->year >= 1900 && $dtCarbon->isPast()) {
                    $dtNascimento = $dtCarbon->format('Y-m-d');
                }
            } catch (Throwable) {
                $dtNascimento = null;
            }
        }

        // Sexo
        $sexoCru = $resolver(['no_sexo_cidadao', 'no_sexo', 'sg_sexo', 'co_sexo', 'sexo']);
        $sexo = 'M';
        if ($sexoCru !== null) {
            $sexoUp = strtoupper($sexoCru);
            $sexo = (str_starts_with($sexoUp, 'F') || str_contains($sexoUp, 'FEM')) ? 'F' : 'M';
        }

        // Nome da mãe
        $nomeMae = $resolver(['no_mae_cidadao', 'no_mae', 'nome_mae', 'nm_mae']);

        // Telefones
        $telefonePrincipal = $resolver(['nu_telefone_celular', 'nu_telefone_contato', 'celular', 'nu_telefone', 'telefone']);
        $telefoneSecundario = $resolver(['nu_telefone_residencial', 'telefone_secundario', 'telefone_recado']);

        // Endereço residencial (domicílio tem prioridade no PEC sobre cadastro individual)
        $logradouro = $resolver(['ds_logradouro_domicilio', 'ds_logradouro_tb_cidadao', 'ds_logradouro', 'ds_endereco', 'endereco', 'ds_logradouro_completo']);
        $numero = $resolver(['nu_numero_domicilio', 'nu_numero_tb_cidadao', 'nu_numero', 'numero']);
        $complemento = $resolver(['ds_complemento_domicilio', 'ds_complemento_tb_cidadao', 'ds_complemento', 'complemento']);

        $enderecoCompleto = null;
        if ($logradouro !== null) {
            $enderecoCompleto = $logradouro;
            if ($numero !== null) {
                $enderecoCompleto .= ', '.$numero;
            }
            if ($complemento !== null) {
                $enderecoCompleto .= ' - '.$complemento;
            }
        }

        // Bairro
        $bairro = $resolver(['no_bairro_domicilio', 'no_bairro_tb_cidadao', 'no_bairro', 'bairro', 'nm_bairro']);

        // Município IBGE
        $ibge = $resolver(['co_ibge_domicilio', 'co_ibge_tb_cidadao', 'co_ibge', 'co_municipio_ibge', 'municipio_ibge']) ?: $ibgePadrao;
        $ibgeLimpo = preg_replace('/\D/', '', $ibge) ?: $ibgePadrao;
        $ibgeLimpo = substr($ibgeLimpo, 0, 6);

        // Vínculo APS (UBS e ACS)
        $ubs = $resolver(['no_unidade_saude', 'no_ubs', 'no_equipe', 'ds_equipe', 'nu_cnes', 'nu_ine', 'ubs_referencia']);
        $acs = $resolver(['no_profissional_acs', 'no_profissional', 'no_acs', 'nome_acs']);

        return [
            'id_externo_esus' => $idExterno,
            'nome' => $nome,
            'nome_social' => $nomeSocial,
            'cpf' => $cpfLimpo,
            'cns' => $cnsLimpo,
            'data_nascimento' => $dtNascimento,
            'sexo' => $sexo,
            'nome_mae' => $nomeMae,
            'telefone_contato' => $telefonePrincipal,
            'telefone_secundario' => $telefoneSecundario,
            'endereco' => $enderecoCompleto,
            'bairro' => $bairro,
            'municipio_ibge' => $ibgeLimpo,
            'ubs_referencia' => $ubs,
            'nome_acs' => $acs,
        ];
    }

    /**
     * Simula o processamento de um paciente em modo Dry-Run sem persistir dados no banco.
     *
     * @param  array<string, mixed>  $dados
     * @param  array<string, Paciente>  $mapaId
     * @param  array<string, array<int, Paciente>>  $mapaCpf
     * @param  array<string, array<int, Paciente>>  $mapaCns
     * @return 'NOVO'|'ATUALIZADO'|'SEM_ALTERACOES'|'DUPLICIDADE'
     */
    public function simularProcessamentoLoteComMapas(
        array $dados,
        array &$mapaId,
        array &$mapaCpf,
        array &$mapaCns
    ): string {
        $idExterno = $dados['id_externo_esus'] ?? null;
        $cpf = $dados['cpf'] ?? null;
        $cns = $dados['cns'] ?? null;

        // 1. Identificador estável do cidadão
        $pacientePorId = null;
        if (! empty($idExterno) && isset($mapaId[$idExterno])) {
            $pacientePorId = $mapaId[$idExterno];
        }

        // 2. Busca por CPF
        $pacientePorCpf = null;
        if (! empty($cpf) && isset($mapaCpf[$cpf])) {
            $candidatos = $mapaCpf[$cpf];
            if (count($candidatos) > 1) {
                return 'DUPLICIDADE';
            }
            if (count($candidatos) === 1) {
                $pacientePorCpf = $candidatos[0];
            }
        }

        // 3. Busca por CNS
        $pacientePorCns = null;
        if (! empty($cns) && isset($mapaCns[$cns])) {
            $candidatos = $mapaCns[$cns];
            if (count($candidatos) > 1) {
                return 'DUPLICIDADE';
            }
            if (count($candidatos) === 1) {
                $pacientePorCns = $candidatos[0];
            }
        }

        // Conflitos cruzados
        if ($pacientePorCpf && $pacientePorCns && $pacientePorCpf->id !== $pacientePorCns->id) {
            return 'DUPLICIDADE';
        }
        if ($pacientePorId && $pacientePorCpf && $pacientePorId->id !== $pacientePorCpf->id) {
            return 'DUPLICIDADE';
        }
        if ($pacientePorId && $pacientePorCns && $pacientePorId->id !== $pacientePorCns->id) {
            return 'DUPLICIDADE';
        }

        $pacienteExistente = $pacientePorId ?: ($pacientePorCpf ?: $pacientePorCns);

        if ($pacienteExistente) {
            $haveraAlteracao = false;
            if (empty($pacienteExistente->id_externo_esus) && ! empty($dados['id_externo_esus'])) {
                $haveraAlteracao = true;
            }
            if (empty($pacienteExistente->cpf) && ! empty($dados['cpf'])) {
                $haveraAlteracao = true;
            }
            if (empty($pacienteExistente->cns) && ! empty($dados['cns'])) {
                $haveraAlteracao = true;
            }

            $camposOpcionais = [
                'nome_mae',
                'telefone_contato',
                'telefone_secundario',
                'endereco',
                'bairro',
                'ubs_referencia',
                'nome_acs',
            ];
            foreach ($camposOpcionais as $campo) {
                if (empty($pacienteExistente->{$campo}) && ! empty($dados[$campo])) {
                    $haveraAlteracao = true;
                    break;
                }
            }

            return $haveraAlteracao ? 'ATUALIZADO' : 'SEM_ALTERACOES';
        }

        // Simula novo paciente adicionando à memória para detectar duplicações internas do mesmo lote
        $pacienteSimulado = new Paciente([
            'id' => -1 * (count($mapaId) + count($mapaCpf) + count($mapaCns) + 1),
            'nome' => $dados['nome'] ?? '',
            'cpf' => $cpf,
            'cns' => $cns,
            'id_externo_esus' => $idExterno,
        ]);

        if (! empty($idExterno)) {
            $mapaId[$idExterno] = $pacienteSimulado;
        }
        if (! empty($cpf)) {
            $mapaCpf[$cpf] = [$pacienteSimulado];
        }
        if (! empty($cns)) {
            $mapaCns[$cns] = [$pacienteSimulado];
        }

        return 'NOVO';
    }

    /**
     * Processa um registro mapeado utilizando mapas de busca em memória para alta performance.
     *
     * @param  array<string, mixed>  $dados
     * @param  array<string, Paciente>  $mapaId
     * @param  array<string, array<int, Paciente>>  $mapaCpf
     * @param  array<string, array<int, Paciente>>  $mapaCns
     * @return 'NOVO'|'ATUALIZADO'|'SEM_ALTERACOES'|'DUPLICIDADE'|'FALHA_GRAVACAO'
     */
    public function processarRegistroPacienteComMapas(
        array $dados,
        array &$mapaId,
        array &$mapaCpf,
        array &$mapaCns
    ): string {
        $idExterno = $dados['id_externo_esus'] ?? null;
        $cpf = $dados['cpf'] ?? null;
        $cns = $dados['cns'] ?? null;
        $nome = trim((string) ($dados['nome'] ?? ''));
        $dtNascimento = $dados['data_nascimento'] ?? null;

        // 1. Prioridade 1: Identificador estável do cidadão no PEC
        $pacientePorId = null;
        if (! empty($idExterno) && isset($mapaId[$idExterno])) {
            $pacientePorId = $mapaId[$idExterno];
        }

        // 2. Prioridade 2: Busca por CPF
        $pacientePorCpf = null;
        if (! empty($cpf) && isset($mapaCpf[$cpf])) {
            $candidatos = $mapaCpf[$cpf];
            if (count($candidatos) > 1) {
                return 'DUPLICIDADE'; // Múltiplos pacientes locais com o mesmo CPF
            }
            if (count($candidatos) === 1) {
                $pacientePorCpf = $candidatos[0];
            }
        }

        // 3. Prioridade 3: Busca por CNS
        $pacientePorCns = null;
        if (! empty($cns) && isset($mapaCns[$cns])) {
            $candidatos = $mapaCns[$cns];
            if (count($candidatos) > 1) {
                return 'DUPLICIDADE'; // Múltiplos pacientes locais com o mesmo CNS
            }
            if (count($candidatos) === 1) {
                $pacientePorCns = $candidatos[0];
            }
        }

        // Conflito cruzado entre documentos: CPF aponta para um paciente e CNS para outro
        if ($pacientePorCpf && $pacientePorCns && $pacientePorCpf->id !== $pacientePorCns->id) {
            return 'DUPLICIDADE';
        }

        // Conflito entre ID externo e CPF
        if ($pacientePorId && $pacientePorCpf && $pacientePorId->id !== $pacientePorCpf->id) {
            return 'DUPLICIDADE';
        }

        // Conflito entre ID externo e CNS
        if ($pacientePorId && $pacientePorCns && $pacientePorId->id !== $pacientePorCns->id) {
            return 'DUPLICIDADE';
        }

        $pacienteExistente = $pacientePorId ?: ($pacientePorCpf ?: $pacientePorCns);

        // Se encontrou paciente correspondente unívoco, atualiza campos cadastrais faltantes
        if ($pacienteExistente) {
            $alterou = $this->atualizarPacienteLocalSemSobrescrever($pacienteExistente, $dados);

            // Atualiza os índices em memória se novos identificadores foram associados
            if (! empty($pacienteExistente->id_externo_esus)) {
                $mapaId[$pacienteExistente->id_externo_esus] = $pacienteExistente;
            }
            if (! empty($pacienteExistente->cpf)) {
                $mapaCpf[$pacienteExistente->cpf] = [$pacienteExistente];
            }
            if (! empty($pacienteExistente->cns)) {
                $mapaCns[$pacienteExistente->cns] = [$pacienteExistente];
            }

            return $alterou ? 'ATUALIZADO' : 'SEM_ALTERACOES';
        }

        // 4. Inserção de novo paciente no banco do CER Digital
        $novoPaciente = Paciente::create([
            'nome' => $nome,
            'cpf' => $cpf,
            'cns' => $cns,
            'data_nascimento' => $dtNascimento,
            'sexo' => $dados['sexo'] ?? 'M',
            'nome_mae' => $dados['nome_mae'] ?? null,
            'municipio_ibge' => $dados['municipio_ibge'] ?? '270430',
            'ubs_referencia' => $dados['ubs_referencia'] ?? null,
            'nome_acs' => $dados['nome_acs'] ?? null,
            'telefone_contato' => $dados['telefone_contato'] ?? null,
            'telefone_secundario' => $dados['telefone_secundario'] ?? null,
            'endereco' => $dados['endereco'] ?? null,
            'bairro' => $dados['bairro'] ?? null,
            'origem_cadastro' => 'ESUS_APS',
            'id_externo_esus' => $idExterno,
            'data_ultima_sincronizacao_esus' => now(),
            'ativo' => true,
        ]);

        // Registra nos índices em memória para evitar colisões no mesmo lote
        if (! empty($idExterno)) {
            $mapaId[$idExterno] = $novoPaciente;
        }
        if (! empty($cpf)) {
            $mapaCpf[$cpf] = [$novoPaciente];
        }
        if (! empty($cns)) {
            $mapaCns[$cns] = [$novoPaciente];
        }

        return 'NOVO';
    }

    /**
     * Aplica regras de desduplicação do SUS e persistência unidirecional (modo individual).
     *
     * @param  array<string, mixed>  $dados
     * @return 'NOVO'|'ATUALIZADO'|'SEM_ALTERACOES'|'DUPLICIDADE'|'SEM_IDENTIFICADOR'|'INVALIDO'|'FALHA_GRAVACAO'
     */
    public function processarRegistroPaciente(array $dados): string
    {
        // Validação mínima
        if (empty($dados['nome']) || mb_strlen(trim((string) $dados['nome'])) < 2 || empty($dados['data_nascimento'])) {
            return 'INVALIDO';
        }

        if (empty($dados['id_externo_esus']) && empty($dados['cpf']) && empty($dados['cns'])) {
            return 'SEM_IDENTIFICADOR';
        }

        $idExterno = $dados['id_externo_esus'] ?? null;
        $cpf = $dados['cpf'] ?? null;
        $cns = $dados['cns'] ?? null;

        $mapaId = [];
        $mapaCpf = [];
        $mapaCns = [];

        if (! empty($idExterno)) {
            $pac = Paciente::where('id_externo_esus', $idExterno)->first();
            if ($pac) {
                $mapaId[$idExterno] = $pac;
            }
        }
        if (! empty($cpf)) {
            $mapaCpf[$cpf] = Paciente::where('cpf', $cpf)->get()->all();
        }
        if (! empty($cns)) {
            $mapaCns[$cns] = Paciente::where('cns', $cns)->get()->all();
        }

        return $this->processarRegistroPacienteComMapas($dados, $mapaId, $mapaCpf, $mapaCns);
    }

    /**
     * Atualiza dados locais preservando valores preenchidos contra valores nulos da origem
     * e protegendo diagnósticos e modalidades clínicas.
     *
     * @param  array<string, mixed>  $dadosNovos
     * @return bool Retorna true se algum campo cadastral foi efetivamente alterado
     */
    public function atualizarPacienteLocalSemSobrescrever(Paciente $paciente, array $dadosNovos): bool
    {
        $camposParaAtualizar = [];
        $houveAlteracaoCadastral = false;

        // Atualiza vínculo externo se não estava preenchido
        if (empty($paciente->id_externo_esus) && ! empty($dadosNovos['id_externo_esus'])) {
            $camposParaAtualizar['id_externo_esus'] = $dadosNovos['id_externo_esus'];
            $houveAlteracaoCadastral = true;
        }

        // Se local não tiver CPF e a origem tiver, atualiza
        if (empty($paciente->cpf) && ! empty($dadosNovos['cpf'])) {
            $camposParaAtualizar['cpf'] = $dadosNovos['cpf'];
            $houveAlteracaoCadastral = true;
        }

        // Se local não tiver CNS e a origem tiver, atualiza
        if (empty($paciente->cns) && ! empty($dadosNovos['cns'])) {
            $camposParaAtualizar['cns'] = $dadosNovos['cns'];
            $houveAlteracaoCadastral = true;
        }

        // Atualiza dados cadastrais se local estiver vazio
        $camposOpcionais = [
            'nome_mae',
            'telefone_contato',
            'telefone_secundario',
            'endereco',
            'bairro',
            'ubs_referencia',
            'nome_acs',
        ];

        foreach ($camposOpcionais as $campo) {
            if (empty($paciente->{$campo}) && ! empty($dadosNovos[$campo])) {
                $camposParaAtualizar[$campo] = $dadosNovos[$campo];
                $houveAlteracaoCadastral = true;
            }
        }

        $camposParaAtualizar['data_ultima_sincronizacao_esus'] = now();

        $paciente->update($camposParaAtualizar);

        return $houveAlteracaoCadastral;
    }

    /**
     * Sanitiza mensagens de erro de conexão para nunca expor senhas ou DSNs aos usuários e logs.
     */
    public function sanitizarMensagemErroConexao(string $mensagemBruta, string $host, int $porta, string $usuario): string
    {
        if (str_contains($mensagemBruta, 'timeout expired') || str_contains($mensagemBruta, 'Connection timed out') || str_contains($mensagemBruta, 'ETIMEDOUT')) {
            return sprintf(
                'Tempo limite de conexão esgotado ao acessar %s:%d. Verifique se o servidor está ativo, se a porta 5433 está liberada no firewall do PostgreSQL ou se a rede necessita de rota/VPN autorizada.',
                $host,
                $porta
            );
        }

        if (str_contains($mensagemBruta, 'password authentication failed')) {
            return sprintf(
                "Autenticação falhou para o usuário '%s'. A senha cadastrada foi rejeitada pelo servidor PostgreSQL do e-SUS.",
                $usuario
            );
        }

        if (str_contains($mensagemBruta, 'Connection refused') || str_contains($mensagemBruta, 'actively refused')) {
            return sprintf(
                'Conexão recusada em %s:%d. O serviço PostgreSQL pode estar inativo ou não configurado para aceitar conexões externas.',
                $host,
                $porta
            );
        }

        if (str_contains($mensagemBruta, 'database') && str_contains($mensagemBruta, 'does not exist')) {
            return 'O banco de dados informado não existe no servidor PostgreSQL conectado.';
        }

        // Remove eventuais fragmentos de strings de conexão com senhas
        $sanitizada = preg_replace('/password=[^; ]+/i', 'password=***', $mensagemBruta) ?? $mensagemBruta;

        return 'Falha de comunicação com o PostgreSQL do e-SUS: '.substr($sanitizada, 0, 200);
    }
}

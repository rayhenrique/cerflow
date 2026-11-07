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
    /**
     * Instancia conexão PDO independente e segura para leitura exclusiva do PostgreSQL.
     */
    public function criarConexaoPdo(?ConfiguracaoEsusPec $config = null): PDO
    {
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

        return [
            'tipo_banco' => $tipoBanco,
            'tabela_origem' => $tabelaOrigem,
            'compativel_importacao' => $compativelImportacao,
            'versao_pec' => $versaoPec,
            'total_tabelas' => count($tabelas),
            'tabelas_descobertas' => array_values(array_slice($tabelas, 0, 50)),
            'colunas_origem' => $colunasOrigem,
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
        ?callable $onProgresso = null
    ): HistoricoSincronizacaoEsus {
        $inicio = microtime(true);
        $config = ConfiguracaoEsusPec::atual();

        $historico = null;
        if ($historicoId) {
            $historico = HistoricoSincronizacaoEsus::find($historicoId);
        }

        if (! $historico) {
            $historico = HistoricoSincronizacaoEsus::create([
                'iniciado_em' => now(),
                'tipo_execucao' => $tipoExecucao === 'AGENDADA' ? 'AGENDADA' : 'MANUAL',
                'status' => 'EM_ANDAMENTO',
                'total_consultado' => 0,
                'novos_pacientes' => 0,
                'pacientes_atualizados' => 0,
                'registros_ignorados' => 0,
                'duplicidades_identificadas' => 0,
                'detalhes' => [
                    'etapa' => 'Iniciando sincronização',
                    'percentual' => 0,
                ],
            ]);
        }

        if (! $config->ativo && $pdoInjetado === null) {
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

        $notificarProgresso = function (
            string $etapa,
            int $processados,
            int $total,
            array $contadoresAtuais
        ) use ($historico, $onProgresso): void {
            $percentual = $total > 0 ? (int) min(100, round(($processados / $total) * 100)) : 0;

            $detalhes = array_merge($historico->detalhes ?? [], [
                'etapa' => $etapa,
                'processados' => $processados,
                'total_estimado' => $total,
                'percentual' => $percentual,
                'registros_validos' => $contadoresAtuais['validos'],
                'registros_invalidos' => $contadoresAtuais['invalidos'],
                'sem_identificador' => $contadoresAtuais['sem_identificador'],
                'novos_pacientes' => $contadoresAtuais['novos'],
                'pacientes_atualizados' => $contadoresAtuais['atualizados'],
                'pacientes_sem_alteracoes' => $contadoresAtuais['sem_alteracoes'],
                'duplicidades' => $contadoresAtuais['duplicidades'],
                'falhas_gravacao' => $contadoresAtuais['falhas'],
            ]);

            $historico->update([
                'total_consultado' => $processados,
                'novos_pacientes' => $contadoresAtuais['novos'],
                'pacientes_atualizados' => $contadoresAtuais['atualizados'],
                'registros_ignorados' => $contadoresAtuais['invalidos'] + $contadoresAtuais['sem_identificador'],
                'duplicidades_identificadas' => $contadoresAtuais['duplicidades'],
                'detalhes' => $detalhes,
            ]);

            Cache::put("esus_sync_progresso_{$historico->id}", [
                'historico_id' => $historico->id,
                'etapa' => $etapa,
                'processados' => $processados,
                'total' => $total,
                'percentual' => $percentual,
                'contadores' => $contadoresAtuais,
                'status' => $historico->status,
            ], 300);

            if ($onProgresso) {
                $onProgresso($etapa, $processados, $total, $contadoresAtuais);
            }
        };

        try {
            $notificarProgresso('Consultando origem', 0, 0, $contadores);

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

                $config->update([
                    'ultima_sincronizacao_em' => now(),
                    'ultima_sincronizacao_status' => 'INTERROMPIDO',
                ]);

                return $historico;
            }

            $tabelaOrigem = $diagnostico['tabela_origem'];
            $ibgePadrao = ConfiguracaoEstabelecimento::atual()->codigo_ibge_padrao ?: '270430';

            // Contagem do total real de registros na origem
            $totalEstimado = 0;
            try {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM {$tabelaOrigem};");
                $totalEstimado = $countStmt ? (int) $countStmt->fetchColumn() : 0;
            } catch (Throwable) {
                $totalEstimado = 0;
            }

            $notificarProgresso('Consultando origem', 0, $totalEstimado, $contadores);

            // Processamento em lotes otimizados
            $limite = 500;
            $offset = 0;
            $totalConsultado = 0;

            while (true) {
                $stmt = $pdo->query("SELECT * FROM {$tabelaOrigem} LIMIT {$limite} OFFSET {$offset};");
                $linhas = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

                if (empty($linhas)) {
                    break;
                }

                $notificarProgresso('Validando registros', $totalConsultado, $totalEstimado, $contadores);

                $loteMapeado = [];
                $idsChunk = [];
                $cpfsChunk = [];
                $cnssChunk = [];

                foreach ($linhas as $linha) {
                    $totalConsultado++;
                    $dados = $this->mapearCidadaoParaPaciente($linha, $ibgePadrao);

                    // Validação de dados cadastrais mínimos: nome e data de nascimento
                    if (empty($dados['nome']) || mb_strlen($dados['nome']) < 2 || empty($dados['data_nascimento'])) {
                        $contadores['invalidos']++;

                        continue;
                    }

                    // Validação de identificador mínimo confiável: não criar sem documento válido
                    if (empty($dados['id_externo_esus']) && empty($dados['cpf']) && empty($dados['cns'])) {
                        $contadores['sem_identificador']++;

                        continue;
                    }

                    $contadores['validos']++;
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
                    $notificarProgresso('Identificando pacientes existentes', $totalConsultado, $totalEstimado, $contadores);

                    // Pré-carrega pacientes locais existentes em UMA consulta para alta performance
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

                    // Constrói mapas de busca em memória para o lote atual
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

                    $notificarProgresso('Importando novos', $totalConsultado, $totalEstimado, $contadores);

                    // Persistência transacional do lote no banco local do CER Digital
                    DB::transaction(function () use (&$loteMapeado, &$contadores, &$mapaId, &$mapaCpf, &$mapaCns) {
                        foreach ($loteMapeado as $dadosPaciente) {
                            $res = $this->processarRegistroPacienteComMapas($dadosPaciente, $mapaId, $mapaCpf, $mapaCns);

                            if ($res === 'NOVO') {
                                $contadores['novos']++;
                            } elseif ($res === 'ATUALIZADO') {
                                $contadores['atualizados']++;
                            } elseif ($res === 'SEM_ALTERACOES') {
                                $contadores['sem_alteracoes']++;
                            } elseif ($res === 'DUPLICIDADE') {
                                $contadores['duplicidades']++;
                            } elseif ($res === 'FALHA_GRAVACAO') {
                                $contadores['falhas']++;
                            }
                        }
                    });
                }

                $offset += $limite;
                $notificarProgresso('Atualizando existentes', $totalConsultado, $totalEstimado, $contadores);
            }

            $notificarProgresso('Finalizando', $totalConsultado, $totalEstimado, $contadores);

            $duracao = (int) round(microtime(true) - $inicio);
            $sucessoEfetivo = ($contadores['novos'] + $contadores['atualizados'] + $contadores['sem_alteracoes']) > 0;

            if ($contadores['falhas'] > 0 && ! $sucessoEfetivo) {
                $statusFinal = 'ERRO';
                $msgFinal = sprintf('A sincronização falhou: %d erros de gravação durante a persistência.', $contadores['falhas']);
            } elseif ($totalConsultado > 0 && ! $sucessoEfetivo) {
                $statusFinal = 'INTERROMPIDO';
                $msgFinal = sprintf(
                    'Consulta finalizada com %d registros, mas nenhum paciente foi importado ou atualizado. Motivos: %d registros inválidos, %d sem identificador mínimo confiável, %d duplicidades/conflitos.',
                    $totalConsultado,
                    $contadores['invalidos'],
                    $contadores['sem_identificador'],
                    $contadores['duplicidades']
                );
            } else {
                $statusFinal = 'SUCESSO';
                $msgFinal = sprintf(
                    'Sincronização concluída com sucesso: %d novos pacientes, %d atualizados, %d sem alterações (%d consultados no e-SUS PEC).',
                    $contadores['novos'],
                    $contadores['atualizados'],
                    $contadores['sem_alteracoes'],
                    $totalConsultado
                );
            }

            $detalhesFinais = [
                'etapa' => 'Concluído',
                'percentual' => 100,
                'tipo_banco' => $diagnostico['tipo_banco'],
                'tabela' => $tabelaOrigem,
                'versao_pec' => $diagnostico['versao_pec'],
                'total_consultado' => $totalConsultado,
                'registros_validos' => $contadores['validos'],
                'registros_invalidos' => $contadores['invalidos'],
                'sem_identificador' => $contadores['sem_identificador'],
                'novos_pacientes' => $contadores['novos'],
                'pacientes_atualizados' => $contadores['atualizados'],
                'pacientes_sem_alteracoes' => $contadores['sem_alteracoes'],
                'duplicidades' => $contadores['duplicidades'],
                'falhas_gravacao' => $contadores['falhas'],
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
                'mensagem_erro' => $statusFinal === 'SUCESSO' ? null : $msgFinal,
                'detalhes' => $detalhesFinais,
            ]);

            $config->update([
                'ultima_sincronizacao_em' => now(),
                'ultima_sincronizacao_status' => $statusFinal,
            ]);

            Cache::put("esus_sync_progresso_{$historico->id}", [
                'historico_id' => $historico->id,
                'etapa' => 'Concluído',
                'processados' => $totalConsultado,
                'total' => $totalConsultado,
                'percentual' => 100,
                'contadores' => $contadores,
                'status' => $statusFinal,
            ], 300);

            return $historico;
        } catch (Throwable $e) {
            $duracao = (int) round(microtime(true) - $inicio);
            $msgErro = $e->getMessage();

            $historico->update([
                'status' => 'ERRO',
                'finalizado_em' => now(),
                'duracao_segundos' => $duracao,
                'mensagem_erro' => $msgErro,
                'detalhes' => array_merge($historico->detalhes ?? [], [
                    'etapa' => 'Erro na execução',
                    'falhas_gravacao' => $contadores['falhas'] + 1,
                ]),
            ]);

            $config->update([
                'ultima_sincronizacao_em' => now(),
                'ultima_sincronizacao_status' => 'ERRO',
            ]);

            return $historico;
        }
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
        try {
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
        } catch (Throwable) {
            return 'FALHA_GRAVACAO';
        }
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

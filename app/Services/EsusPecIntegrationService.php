<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ConfiguracaoEstabelecimento;
use App\Models\ConfiguracaoEsusPec;
use App\Models\HistoricoSincronizacaoEsus;
use App\Models\Paciente;
use App\Models\UnidadeBasicaSaude;
use App\Rules\CnsValido;
use App\Support\IbgeMunicipio;
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
     * Cache em memória de instâncias de UBS para otimização de processamento em lote.
     *
     * @var array<string, UnidadeBasicaSaude>
     */
    protected array $cacheUbsMemoria = [];

    /**
     * Verifica se o nome informado para a UBS é inconclusivo, genérico ou inválido.
     */
    public function nomeUbsInconclusivo(?string $nome): bool
    {
        if ($nome === null) {
            return true;
        }

        $nomeLimpo = trim(mb_strtoupper($nome));
        if ($nomeLimpo === '' || mb_strlen($nomeLimpo) < 3) {
            return true;
        }

        $termosInconclusivos = [
            'SEM UBS',
            'SEM UNIDADE',
            'NAO INFORMADO',
            'NÃO INFORMADO',
            'IGNORADO',
            'NAO DECLARADO',
            'NÃO DECLARADO',
            'DESCONHECIDO',
            'INDEFINIDO',
            'NULL',
            'TESTE',
            'OUTROS',
            'OUTRO',
        ];

        if (in_array($nomeLimpo, $termosInconclusivos, true)) {
            return true;
        }

        if (str_starts_with($nomeLimpo, 'UBS CNES ') && strlen(trim(substr($nomeLimpo, 9))) < 6) {
            return true;
        }

        return false;
    }

    /**
     * Localiza uma UBS existente exclusivamente por consulta (somente leitura, sem alterações no banco).
     */
    public function localizarUbsExistente(?string $nomeUbs, ?string $cnes = null, ?string $municipioIbge = null): ?UnidadeBasicaSaude
    {
        $nomeUbs = trim((string) $nomeUbs);
        $cnes = trim((string) $cnes);
        if ($cnes !== '') {
            $cnes = substr(preg_replace('/\D/', '', $cnes) ?? '', 0, 7);
        }

        // 1. Busca por CNES válido (6 ou 7 dígitos)
        if ($cnes !== '' && strlen($cnes) >= 6 && strlen($cnes) <= 7) {
            $ubs = UnidadeBasicaSaude::where('cnes', $cnes)->first();
            if ($ubs) {
                return $ubs;
            }
        }

        // 2. Busca por Nome quando conclusivo
        if ($nomeUbs !== '' && ! $this->nomeUbsInconclusivo($nomeUbs) && mb_strlen($nomeUbs) >= 3) {
            $candidatos = UnidadeBasicaSaude::where('nome', $nomeUbs)->get();
            if ($candidatos->count() === 1) {
                $candidato = $candidatos->first();
                if (! $municipioIbge || ! $candidato->municipio_ibge || $candidato->municipio_ibge === $municipioIbge) {
                    return $candidato;
                }
            } elseif ($candidatos->count() > 1 && $municipioIbge) {
                $filtrados = $candidatos->where('municipio_ibge', $municipioIbge);
                if ($filtrados->count() === 1) {
                    return $filtrados->first();
                }
            }
        }

        return null;
    }

    /**
     * Resolve ou cria uma Unidade Básica de Saúde (UBS) a partir de CNES ou Nome sem duplicidade.
     * Em modo somente leitura ($somenteLeitura = true), não realiza nenhuma gravação no MySQL.
     */
    public function resolverOuCriarUbs(
        ?string $nomeUbs,
        ?string $cnes = null,
        bool $somenteLeitura = false,
        ?string $municipioIbge = null
    ): ?UnidadeBasicaSaude {
        $nomeUbs = trim((string) $nomeUbs);
        $cnes = trim((string) $cnes);
        if ($cnes !== '') {
            $cnes = substr(preg_replace('/\D/', '', $cnes) ?? '', 0, 7);
        }

        if ($nomeUbs === '' && $cnes === '') {
            return null;
        }

        $chaveCache = "{$cnes}|{$nomeUbs}|{$municipioIbge}|".($somenteLeitura ? 'RO' : 'RW');
        if (isset($this->cacheUbsMemoria[$chaveCache])) {
            return $this->cacheUbsMemoria[$chaveCache];
        }

        $ubs = $this->localizarUbsExistente($nomeUbs, $cnes, $municipioIbge);

        if ($ubs) {
            if (! $somenteLeitura) {
                if ($nomeUbs !== '' && (empty($ubs->nome) || str_starts_with($ubs->nome, 'UBS CNES '))) {
                    $ubs->update(['nome' => $nomeUbs]);
                }
                if ($cnes !== '' && empty($ubs->cnes) && strlen($cnes) >= 6 && strlen($cnes) <= 7) {
                    $ubs->update(['cnes' => $cnes]);
                }
            }
            $this->cacheUbsMemoria[$chaveCache] = $ubs;

            return $ubs;
        }

        $cnesValido = $cnes !== '' && strlen($cnes) >= 6 && strlen($cnes) <= 7;
        $nomeValido = ! $this->nomeUbsInconclusivo($nomeUbs) && mb_strlen($nomeUbs) >= 3;

        if (! $cnesValido && ! $nomeValido) {
            return null;
        }

        if ($somenteLeitura) {
            $ubsSimulada = new UnidadeBasicaSaude([
                'nome' => $nomeUbs !== '' ? $nomeUbs : ('UBS CNES '.$cnes),
                'cnes' => $cnesValido ? $cnes : null,
                'municipio_ibge' => $municipioIbge ?: (ConfiguracaoEstabelecimento::atual()->codigo_ibge_padrao ?: '2709152'),
                'origem' => 'ESUS',
                'ativo' => true,
            ]);
            $ubsSimulada->id = -1 * (count($this->cacheUbsMemoria) + 1);
            $this->cacheUbsMemoria[$chaveCache] = $ubsSimulada;

            return $ubsSimulada;
        }

        $ubs = UnidadeBasicaSaude::create([
            'nome' => $nomeUbs !== '' ? $nomeUbs : ('UBS CNES '.$cnes),
            'cnes' => $cnesValido ? $cnes : null,
            'municipio_ibge' => $municipioIbge ?: (ConfiguracaoEstabelecimento::atual()->codigo_ibge_padrao ?: '2709152'),
            'origem' => 'ESUS',
            'ativo' => true,
        ]);

        $this->cacheUbsMemoria[$chaveCache] = $ubs;

        return $ubs;
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

        $tabelasAuxiliares = [
            'tb_unidade_saude' => in_array('tb_unidade_saude', $tabelas, true),
            'tb_dim_unidade_saude' => in_array('tb_dim_unidade_saude', $tabelas, true),
            'tb_equipe' => in_array('tb_equipe', $tabelas, true),
            'tb_dim_equipe' => in_array('tb_dim_equipe', $tabelas, true),
            'tb_dim_profissional' => in_array('tb_dim_profissional', $tabelas, true),
            'tb_dim_cbo' => in_array('tb_dim_cbo', $tabelas, true),
            'tb_dim_tempo' => in_array('tb_dim_tempo', $tabelas, true),
            'tb_fat_cad_individual' => in_array('tb_fat_cad_individual', $tabelas, true),
            'tb_fat_visita_domiciliar' => in_array('tb_fat_visita_domiciliar', $tabelas, true),
            'tb_cidadao' => in_array('tb_cidadao', $tabelas, true),
        ];

        return [
            'tipo_banco' => $tipoBanco,
            'tabela_origem' => $tabelaOrigem,
            'compativel_importacao' => $compativelImportacao,
            'versao_pec' => $versaoPec,
            'total_tabelas' => count($tabelas),
            'tabelas_descobertas' => array_values(array_slice($tabelas, 0, 50)),
            'tabelas_auxiliares' => $tabelasAuxiliares,
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
     * Carrega dicionários em memória de UBSs e Equipes a partir das tabelas dimensionais ou operacionais do PEC.
     *
     * @param  array<string, mixed>  $diagnostico
     * @return array{
     *     ubs: array<string, string>,
     *     equipes: array<string, array{no_equipe: ?string, nu_cnes: ?string, no_unidade_saude: ?string}>
     * }
     */
    public function carregarDicionariosUbsEquipes(PDO $pdo, array $diagnostico): array
    {
        $mapaUbs = [];
        $mapaEquipes = [];

        $aux = $diagnostico['tabelas_auxiliares'] ?? [];

        // 1. Dicionário de Unidades de Saúde (dimensão DW)
        if (! empty($aux['tb_dim_unidade_saude'])) {
            try {
                $stmt = $pdo->query('
                    SELECT nu_cnes, no_unidade_saude
                    FROM tb_dim_unidade_saude
                    WHERE nu_cnes IS NOT NULL AND TRIM(nu_cnes) != \'\'
                ');
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $cnes = trim((string) ($row['nu_cnes'] ?? ''));
                        $nome = trim((string) ($row['no_unidade_saude'] ?? ''));
                        if ($cnes !== '' && $nome !== '') {
                            $mapaUbs[$cnes] = $nome;
                        }
                    }
                }
            } catch (Throwable) {
                // Silencioso
            }
        }

        // Dicionário de Unidades de Saúde (operacional e-SUS)
        if (! empty($aux['tb_unidade_saude'])) {
            try {
                $stmt = $pdo->query('
                    SELECT nu_cnes, no_unidade_saude
                    FROM tb_unidade_saude
                    WHERE nu_cnes IS NOT NULL AND TRIM(nu_cnes) != \'\'
                ');
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $cnes = trim((string) ($row['nu_cnes'] ?? ''));
                        $nome = trim((string) ($row['no_unidade_saude'] ?? ''));
                        if ($cnes !== '' && $nome !== '' && ! isset($mapaUbs[$cnes])) {
                            $mapaUbs[$cnes] = $nome;
                        }
                    }
                }
            } catch (Throwable) {
                // Silencioso
            }
        }

        // 2. Dicionário de Equipes e seu relacionamento com a Unidade de Saúde (tb_equipe + tb_unidade_saude)
        if (! empty($aux['tb_equipe']) && ! empty($aux['tb_unidade_saude'])) {
            try {
                $stmt = $pdo->query('
                    SELECT e.nu_ine, e.no_equipe, u.nu_cnes, u.no_unidade_saude
                    FROM tb_equipe e
                    LEFT JOIN tb_unidade_saude u ON u.co_seq_unidade_saude = e.co_unidade_saude
                    WHERE e.nu_ine IS NOT NULL AND TRIM(e.nu_ine) != \'\'
                ');
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $ine = trim((string) ($row['nu_ine'] ?? ''));
                        if ($ine !== '') {
                            $cnes = trim((string) ($row['nu_cnes'] ?? ''));
                            $nomeUbs = trim((string) ($row['no_unidade_saude'] ?? ''));
                            if ($cnes !== '' && $nomeUbs !== '' && ! isset($mapaUbs[$cnes])) {
                                $mapaUbs[$cnes] = $nomeUbs;
                            }
                            $mapaEquipes[$ine] = [
                                'no_equipe' => trim((string) ($row['no_equipe'] ?? '')) ?: null,
                                'nu_cnes' => $cnes ?: null,
                                'no_unidade_saude' => $nomeUbs ?: null,
                            ];
                        }
                    }
                }
            } catch (Throwable) {
                // Silencioso
            }
        } elseif (! empty($aux['tb_dim_equipe'])) {
            try {
                $stmt = $pdo->query('
                    SELECT nu_ine, no_equipe
                    FROM tb_dim_equipe
                    WHERE nu_ine IS NOT NULL AND TRIM(nu_ine) != \'\'
                ');
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $ine = trim((string) ($row['nu_ine'] ?? ''));
                        if ($ine !== '') {
                            $mapaEquipes[$ine] = [
                                'no_equipe' => trim((string) ($row['no_equipe'] ?? '')) ?: null,
                                'nu_cnes' => null,
                                'no_unidade_saude' => null,
                            ];
                        }
                    }
                }
            } catch (Throwable) {
                // Silencioso
            }
        }

        return [
            'ubs' => $mapaUbs,
            'equipes' => $mapaEquipes,
        ];
    }

    /**
     * Resolve o ACS individual para uma lista de IDs de cidadãos (co_fat_cidadao_pec).
     * Consulta primeiramente tb_fat_cad_individual (MICI) e, para os que restarem, tb_fat_visita_domiciliar.
     * Exige estritamente CBO de ACS ('515105', '322255').
     *
     * @param  array<int, int|string>  $coFatCidadaoIds
     * @param  array<string, mixed>  $diagnostico
     * @return array<int, string> [co_fat_cidadao_pec => nome_profissional_acs]
     */
    public function resolverAcsParaLote(PDO $pdo, array $coFatCidadaoIds, array $diagnostico): array
    {
        $coFatCidadaoIds = array_values(array_unique(array_filter(array_map('intval', $coFatCidadaoIds))));
        if (empty($coFatCidadaoIds)) {
            return [];
        }

        $mapaAcs = [];
        $aux = $diagnostico['tabelas_auxiliares'] ?? [];

        $placeholders = implode(',', array_fill(0, count($coFatCidadaoIds), '?'));

        // 1. Consulta tb_fat_cad_individual (Cadastro Individual - MICI)
        if (! empty($aux['tb_fat_cad_individual']) && ! empty($aux['tb_dim_profissional']) && ! empty($aux['tb_dim_cbo'])) {
            try {
                $temTempo = ! empty($aux['tb_dim_tempo']);
                $orderClausula = $temTempo ? 'ORDER BY dt.dt_registro DESC' : 'ORDER BY fci.co_fat_cidadao_pec';
                $joinTempo = $temTempo ? 'JOIN tb_dim_tempo dt ON dt.co_seq_dim_tempo = fci.co_dim_tempo' : '';

                $sql = "
                    SELECT fci.co_fat_cidadao_pec, prof.no_profissional
                    FROM tb_fat_cad_individual fci
                    {$joinTempo}
                    JOIN tb_dim_cbo cbo ON cbo.co_seq_dim_cbo = fci.co_dim_cbo
                    JOIN tb_dim_profissional prof ON prof.co_seq_dim_profissional = fci.co_dim_profissional
                    WHERE fci.co_fat_cidadao_pec IN ({$placeholders})
                      AND REPLACE(REPLACE(cbo.nu_cbo, '-', ''), ' ', '') IN ('515105', '322255')
                      AND (fci.st_ficha_inativa = 0 OR fci.st_ficha_inativa IS NULL)
                      AND (fci.st_recusa_cadastro = 0 OR fci.st_recusa_cadastro IS NULL)
                      AND prof.no_profissional IS NOT NULL AND TRIM(prof.no_profissional) != ''
                    {$orderClausula}
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($coFatCidadaoIds);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $cidId = (int) $row['co_fat_cidadao_pec'];
                    if (! isset($mapaAcs[$cidId])) {
                        $nomeProf = trim((string) $row['no_profissional']);
                        if ($nomeProf !== '') {
                            $mapaAcs[$cidId] = $nomeProf;
                        }
                    }
                }
            } catch (Throwable) {
                // Silencioso se houver variação de colunas
            }
        }

        // 2. Para cidadãos ainda sem ACS identificado, consulta tb_fat_visita_domiciliar
        $idsRestantes = array_values(array_diff($coFatCidadaoIds, array_keys($mapaAcs)));
        if (! empty($idsRestantes) && ! empty($aux['tb_fat_visita_domiciliar']) && ! empty($aux['tb_dim_profissional']) && ! empty($aux['tb_dim_cbo'])) {
            try {
                $placeholdersRestantes = implode(',', array_fill(0, count($idsRestantes), '?'));
                $temTempo = ! empty($aux['tb_dim_tempo']);
                $orderClausula = $temTempo ? 'ORDER BY dt.dt_registro DESC' : 'ORDER BY v.co_fat_cidadao_pec';
                $joinTempo = $temTempo ? 'JOIN tb_dim_tempo dt ON dt.co_seq_dim_tempo = v.co_dim_tempo' : '';

                $sql = "
                    SELECT v.co_fat_cidadao_pec, prof.no_profissional
                    FROM tb_fat_visita_domiciliar v
                    {$joinTempo}
                    JOIN tb_dim_cbo cbo ON cbo.co_seq_dim_cbo = v.co_dim_cbo
                    JOIN tb_dim_profissional prof ON prof.co_seq_dim_profissional = v.co_dim_profissional
                    WHERE v.co_fat_cidadao_pec IN ({$placeholdersRestantes})
                      AND REPLACE(REPLACE(cbo.nu_cbo, '-', ''), ' ', '') IN ('515105', '322255')
                      AND prof.no_profissional IS NOT NULL AND TRIM(prof.no_profissional) != ''
                    {$orderClausula}
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($idsRestantes);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $cidId = (int) $row['co_fat_cidadao_pec'];
                    if (! isset($mapaAcs[$cidId])) {
                        $nomeProf = trim((string) $row['no_profissional']);
                        if ($nomeProf !== '') {
                            $mapaAcs[$cidId] = $nomeProf;
                        }
                    }
                }
            } catch (Throwable) {
                // Silencioso se houver variação
            }
        }

        return $mapaAcs;
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

        $lock = $this->assegurarLockConcorrencia($historicoId, $dryRun);

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
            $ibgePadrao = ConfiguracaoEstabelecimento::atual()->codigo_ibge_padrao ?: '2709152';

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

            // Dicionários em memória de UBSs e Equipes para resolução de vínculos relacionais
            $dicionarios = $this->carregarDicionariosUbsEquipes($pdo, $diagnostico);
            $mapaUbs = $dicionarios['ubs'];
            $mapaEquipes = $dicionarios['equipes'];

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

                // Extração dos identificadores de cidadão para resolução em lote do ACS
                $idsCidadaosLote = [];
                foreach ($linhas as $linha) {
                    $idCid = $linha['co_fat_cidadao_pec'] ?? ($linha['co_seq_fat_cidadao_pec'] ?? null);
                    if ($idCid) {
                        $idsCidadaosLote[] = (int) $idCid;
                    }
                }
                $mapaAcsLote = $this->resolverAcsParaLote($pdo, $idsCidadaosLote, $diagnostico);

                $resLote = $this->processarLoteDeRegistros(
                    $linhas,
                    $ibgePadrao,
                    $contadores,
                    $dryRun,
                    $mapaUbs,
                    $mapaEquipes,
                    $mapaAcsLote
                );

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
    protected function assegurarLockConcorrencia(?int $historicoId, bool $dryRun = false): mixed
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

            if ($dryRun) {
                $lock->release();

                throw new RuntimeException("Existe uma sincronização em andamento (ID #{$ativa->id}). Dry-run não altera execuções ativas.");
            }

            // Recupera e encerra execuções estagnadas há mais de 2 horas (somente em execução real)
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
            'nu_cnes_vinc_equipe',
            'nu_cnes_vinc_unidade',
            'no_unidade_vinc',
            'nu_ine_vinc_equipe',
            'no_equipe_vinc_equipe',
            'nu_micro_area_domicilio',
            'nu_micro_area_tb_cidadao',
            'nu_micro_area',
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
        bool $dryRun,
        array $mapaUbs = [],
        array $mapaEquipes = [],
        array $mapaAcs = []
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
            $dados = $this->mapearCidadaoParaPaciente($linha, $ibgePadrao, $mapaUbs, $mapaEquipes, $mapaAcs);

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
     * @param  array<string, string>  $mapaUbs
     * @param  array<string, array{no_equipe: ?string, nu_cnes: ?string, no_unidade_saude: ?string}>  $mapaEquipes
     * @param  array<int, string>  $mapaAcs
     * @return array<string, mixed>
     */
    public function mapearCidadaoParaPaciente(
        array $linha,
        string $ibgePadrao,
        array $mapaUbs = [],
        array $mapaEquipes = [],
        array $mapaAcs = []
    ): array {
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
        if (strlen($ibgeLimpo) === 6) {
            $ibgeLimpo = IbgeMunicipio::formatar7($ibgeLimpo);
        }

        // Vínculo APS — Unidade Básica de Saúde (UBS) de Referência
        $cnesVinculado = $resolver(['nu_cnes_vinc_equipe', 'nu_cnes_vinc_unidade', 'nu_cnes']);
        $ineVinculado = $resolver(['nu_ine_vinc_equipe', 'nu_ine']);

        $ubs = null;
        // Prioridade 1: Nome oficial da UBS obtido pelo mapa de CNES
        if ($cnesVinculado !== null && isset($mapaUbs[$cnesVinculado])) {
            $ubs = $mapaUbs[$cnesVinculado];
        }
        // Prioridade 2: Nome oficial da UBS obtido pelo vínculo da equipe
        if ($ubs === null && $ineVinculado !== null && isset($mapaEquipes[$ineVinculado]['no_unidade_saude'])) {
            $ubs = $mapaEquipes[$ineVinculado]['no_unidade_saude'];
        }
        if ($cnesVinculado === null && $ineVinculado !== null && ! empty($mapaEquipes[$ineVinculado]['nu_cnes'])) {
            $cnesVinculado = $mapaEquipes[$ineVinculado]['nu_cnes'];
        }
        // Prioridade 3: Nome textual de unidade de saúde diretamente na linha
        if ($ubs === null) {
            $ubs = $resolver(['no_unidade_vinc', 'no_unidade_saude', 'no_ubs', 'ubs_referencia']);
        }
        // Prioridade 4: Se não encontrou nome textual mas possui CNES, registra identificação formal
        if ($ubs === null && $cnesVinculado !== null && trim($cnesVinculado) !== '') {
            $ubs = 'UBS CNES '.trim($cnesVinculado);
        }

        // Vínculo APS — Agente Comunitário de Saúde (ACS)
        $acs = null;
        $cidIdNum = $idExterno ? (int) $idExterno : 0;
        // Prioridade 1: ACS individual resolvido por CBO nas fichas de cadastro individual ou visita
        if ($cidIdNum > 0 && isset($mapaAcs[$cidIdNum])) {
            $acs = $mapaAcs[$cidIdNum];
        }
        // Prioridade 2: Coluna de ACS diretamente presente na linha do DW (se existir e não for vazia)
        if ($acs === null) {
            $acsDireto = $resolver(['no_profissional_acs', 'no_acs', 'nome_acs']);
            if ($acsDireto !== null && mb_strlen($acsDireto) >= 3) {
                $acs = $acsDireto;
            }
        }

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
            'cnes_ubs' => $cnesVinculado ? trim($cnesVinculado) : null,
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

        $ubsModel = $this->resolverOuCriarUbs($dados['ubs_referencia'] ?? null, $dados['cnes_ubs'] ?? null);

        // 4. Inserção de novo paciente no banco do CER Digital
        $novoPaciente = Paciente::create([
            'nome' => $nome,
            'cpf' => $cpf,
            'cns' => $cns,
            'data_nascimento' => $dtNascimento,
            'sexo' => $dados['sexo'] ?? 'M',
            'nome_mae' => $dados['nome_mae'] ?? null,
            'municipio_ibge' => $dados['municipio_ibge'] ?? $ibgePadrao,
            'ubs_id' => $ubsModel?->id,
            'ubs_referencia' => $dados['ubs_referencia'] ?? $ubsModel?->nome,
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

        if (empty($paciente->ubs_id) && ! empty($camposParaAtualizar['ubs_referencia'])) {
            $ubsModel = $this->resolverOuCriarUbs($camposParaAtualizar['ubs_referencia'], $dadosNovos['cnes_ubs'] ?? null);
            if ($ubsModel) {
                $camposParaAtualizar['ubs_id'] = $ubsModel->id;
            }
        }

        $camposParaAtualizar['data_ultima_sincronizacao_esus'] = now();

        $paciente->update($camposParaAtualizar);

        return $houveAlteracaoCadastral;
    }

    /**
     * Executa a complementação exclusiva dos vínculos de UBS e ACS para pacientes existentes no CER Digital.
     *
     * Diretrizes estritas:
     * - Atualiza apenas ubs_referencia e nome_acs se estiverem nulos/vazios no CER Digital.
     * - Nunca sobrescreve valores preenchidos manualmente pelo usuário.
     * - Nunca altera CPF, CNS, nome, data de nascimento, endereço, telefone ou dados clínicos.
     * - Nunca cria novos pacientes (garantia: novos_pacientes = 0).
     * - Nunca exclui registros.
     * - Utiliza transações locais por lote e Keyset Pagination na leitura do e-SUS.
     *
     * @param  callable|null  $onLoteConcluido  fn(int $lote, array $metricas, array $acumulados, int $totalConsultado, int $totalEstimado)
     */
    public function complementarVinculosPacientes(
        ?PDO $pdoInjetado = null,
        ?int $tamanhoLote = null,
        ?int $maxRegistros = null,
        bool $dryRun = false,
        bool $reiniciar = false,
        ?callable $onLoteConcluido = null
    ): HistoricoSincronizacaoEsus {
        $inicio = microtime(true);
        $duracaoConsultaMs = 0;
        $duracaoGravacaoMs = 0;

        $lock = $this->assegurarLockConcorrencia(null, $dryRun);

        try {
            $config = ConfiguracaoEsusPec::atual();

            $historico = HistoricoSincronizacaoEsus::create([
                'iniciado_em' => now(),
                'tipo_execucao' => 'CLI',
                'status' => 'EM_ANDAMENTO',
                'total_consultado' => 0,
                'novos_pacientes' => 0,
                'pacientes_atualizados' => 0,
                'registros_ignorados' => 0,
                'duplicidades_identificadas' => 0,
                'detalhes' => [
                    'etapa' => 'Iniciando complementação de vínculos',
                    'modo_executado' => 'APENAS_VINCULOS',
                    'percentual' => 0,
                    'dry_run' => $dryRun,
                ],
            ]);

            $pdo = $pdoInjetado ?? $this->criarConexaoPdo($config);
            $diagnostico = $this->descobrirSchema($pdo);

            if (! $diagnostico['compativel_importacao'] || empty($diagnostico['tabela_origem'])) {
                throw new RuntimeException('Tabela de DW do e-SUS PEC (tb_acomp_cidadaos_vinculados) não encontrada.');
            }

            $tabelaOrigem = $diagnostico['tabela_origem'];
            $colunaSequencial = $diagnostico['coluna_sequencial'] ?? null;
            $ibgePadrao = ConfiguracaoEstabelecimento::atual()->codigo_ibge_padrao ?: '2709152';

            // 1. Carrega dicionários de UBSs e Equipes em memória
            $dicionarios = $this->carregarDicionariosUbsEquipes($pdo, $diagnostico);
            $mapaUbs = $dicionarios['ubs'];
            $mapaEquipes = $dicionarios['equipes'];

            // Keyset Pagination: retoma somente se houve interrupção prévia não concluída
            $retomar = ! $reiniciar
                && $config->ultimo_checkpoint_id
                && in_array($config->ultima_sincronizacao_status, ['PARCIAL', 'INTERROMPIDO', 'ERRO', 'EM_ANDAMENTO'], true);

            $ultimoIdChave = $retomar ? (int) $config->ultimo_checkpoint_id : 0;

            $selectSql = $this->montarProjecaoColunas($diagnostico['colunas_origem'], null, $colunaSequencial);

            // Contagem estimada
            $sqlCount = $colunaSequencial && $ultimoIdChave > 0
                ? "SELECT COUNT(*) FROM {$tabelaOrigem} WHERE {$colunaSequencial} > {$ultimoIdChave};"
                : "SELECT COUNT(*) FROM {$tabelaOrigem};";
            $stmtCount = $pdo->query($sqlCount);
            $totalEstimado = $stmtCount ? (int) $stmtCount->fetchColumn() : 0;

            $limite = $tamanhoLote !== null ? max(1, min(5000, $tamanhoLote)) : 2000;
            $offset = 0;
            $totalConsultado = 0;
            $numeroLote = 0;

            $contadores = [
                'consultados' => 0,
                'localizados_local' => 0,
                'ubs_complementadas' => 0,
                'acs_complementados' => 0,
                'pacientes_atualizados' => 0,
                'sem_alteracoes' => 0,
                'nao_cadastrados_local' => 0,
            ];

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

                if ($colunaSequencial) {
                    $sql = "SELECT {$selectSql} FROM {$tabelaOrigem} WHERE {$colunaSequencial} > :ultimo_id ORDER BY {$colunaSequencial} ASC LIMIT {$limiteAtual};";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':ultimo_id' => $ultimoIdChave]);
                } else {
                    $sql = "SELECT {$selectSql} FROM {$tabelaOrigem} LIMIT {$limiteAtual} OFFSET {$offset};";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                }

                $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $duracaoConsultaMs += (int) round((microtime(true) - $tConsultaLoteInicio) * 1000);

                if (empty($linhas)) {
                    break;
                }

                $numeroLote++;
                $totalConsultado += count($linhas);
                $contadores['consultados'] += count($linhas);

                // Atualiza chave de paginação
                if ($colunaSequencial) {
                    $ultimaLinha = end($linhas);
                    if (isset($ultimaLinha[$colunaSequencial])) {
                        $ultimoIdChave = (int) $ultimaLinha[$colunaSequencial];
                    }
                }
                $offset += $limiteAtual;

                // Extrai co_fat_cidadao_pec para resolver ACS do lote
                $idsCidadaosLote = [];
                foreach ($linhas as $linha) {
                    $idCid = $linha['co_fat_cidadao_pec'] ?? ($linha['co_seq_fat_cidadao_pec'] ?? null);
                    if ($idCid) {
                        $idsCidadaosLote[] = (int) $idCid;
                    }
                }

                // Resolve ACS do lote em consulta única filtrada por CBO
                $mapaAcsLote = $this->resolverAcsParaLote($pdo, $idsCidadaosLote, $diagnostico);

                // Processa complementação no banco local
                $tGravLoteInicio = microtime(true);
                $metricasLote = $this->processarLoteComplementacao(
                    $linhas,
                    $ibgePadrao,
                    $mapaUbs,
                    $mapaEquipes,
                    $mapaAcsLote,
                    $dryRun
                );
                $duracaoGravacaoMs += (int) round((microtime(true) - $tGravLoteInicio) * 1000);

                $contadores['localizados_local'] += $metricasLote['localizados_local'];
                $contadores['ubs_complementadas'] += $metricasLote['ubs_complementadas'];
                $contadores['acs_complementados'] += $metricasLote['acs_complementados'];
                $contadores['pacientes_atualizados'] += $metricasLote['pacientes_atualizados'];
                $contadores['sem_alteracoes'] += $metricasLote['sem_alteracoes'];
                $contadores['nao_cadastrados_local'] += $metricasLote['nao_cadastrados_local'];

                $duracaoLote = round(microtime(true) - $tConsultaLoteInicio, 2);
                $metricasLote['duracao_segundos'] = $duracaoLote;
                $metricasLote['ultimo_id_chave'] = $ultimoIdChave;

                if (! $dryRun && $ultimoIdChave > 0) {
                    $config->update(['ultimo_checkpoint_id' => $ultimoIdChave]);
                }

                if ($onLoteConcluido !== null) {
                    $onLoteConcluido($numeroLote, $metricasLote, $contadores, $totalConsultado, $totalEstimado);
                }
            }

            $duracaoTotal = (int) round(microtime(true) - $inicio);
            $statusFinal = $dryRun ? 'DRY_RUN' : 'SUCESSO';

            $detalhesFinais = [
                'etapa' => 'Concluído',
                'modo_executado' => 'APENAS_VINCULOS',
                'dry_run' => $dryRun,
                'total_consultado' => $totalConsultado,
                'localizados_local' => $contadores['localizados_local'],
                'ubs_complementadas' => $contadores['ubs_complementadas'],
                'acs_complementados' => $contadores['acs_complementados'],
                'pacientes_atualizados' => $contadores['pacientes_atualizados'],
                'pacientes_sem_alteracoes' => $contadores['sem_alteracoes'],
                'nao_cadastrados_local' => $contadores['nao_cadastrados_local'],
                'duracao_consulta_ms' => $duracaoConsultaMs,
                'duracao_gravacao_ms' => $duracaoGravacaoMs,
                'ultimo_id_processado' => $ultimoIdChave,
            ];

            $historico->update([
                'status' => $statusFinal,
                'finalizado_em' => now(),
                'duracao_segundos' => $duracaoTotal,
                'total_consultado' => $totalConsultado,
                'novos_pacientes' => 0, // GARANTIA ABSOLUTA: 0 novos pacientes em modo complementação
                'pacientes_atualizados' => $contadores['pacientes_atualizados'],
                'registros_ignorados' => $contadores['nao_cadastrados_local'],
                'detalhes' => $detalhesFinais,
            ]);

            if (! $dryRun) {
                $config->update([
                    'ultima_sincronizacao_em' => now(),
                    'ultima_sincronizacao_status' => $statusFinal,
                    'ultimo_checkpoint_em' => now(),
                    'ultimo_checkpoint_id' => $ultimoIdChave ?: $config->ultimo_checkpoint_id,
                ]);
            }

            return $historico;
        } finally {
            $lock->release();
        }
    }

    /**
     * Processa a complementação de um lote no banco local.
     *
     * @param  array<int, array<string, mixed>>  $linhas
     * @param  array<string, string>  $mapaUbs
     * @param  array<string, array{no_equipe: ?string, nu_cnes: ?string, no_unidade_saude: ?string}>  $mapaEquipes
     * @param  array<int, string>  $mapaAcs
     * @return array{
     *     consultados: int,
     *     localizados_local: int,
     *     ubs_complementadas: int,
     *     acs_complementados: int,
     *     pacientes_atualizados: int,
     *     sem_alteracoes: int,
     *     nao_cadastrados_local: int,
     *     duracao_segundos?: float,
     *     ultimo_id_chave?: int
     * }
     */
    protected function processarLoteComplementacao(
        array $linhas,
        string $ibgePadrao,
        array $mapaUbs,
        array $mapaEquipes,
        array $mapaAcs,
        bool $dryRun
    ): array {
        $metricas = [
            'consultados' => count($linhas),
            'localizados_local' => 0,
            'ubs_complementadas' => 0,
            'acs_complementados' => 0,
            'pacientes_atualizados' => 0,
            'sem_alteracoes' => 0,
            'nao_cadastrados_local' => 0,
        ];

        $mapeados = [];
        $idsExternos = [];
        $cpfs = [];
        $cnss = [];

        foreach ($linhas as $linha) {
            $dados = $this->mapearCidadaoParaPaciente($linha, $ibgePadrao, $mapaUbs, $mapaEquipes, $mapaAcs);
            if (empty($dados['id_externo_esus']) && empty($dados['cpf']) && empty($dados['cns'])) {
                $metricas['nao_cadastrados_local']++;

                continue;
            }

            $mapeados[] = $dados;
            if (! empty($dados['id_externo_esus'])) {
                $idsExternos[] = $dados['id_externo_esus'];
            }
            if (! empty($dados['cpf'])) {
                $cpfs[] = $dados['cpf'];
            }
            if (! empty($dados['cns'])) {
                $cnss[] = $dados['cns'];
            }
        }

        if (empty($mapeados)) {
            return $metricas;
        }

        // Busca pacientes locais existentes no CER Digital
        $pacientesLocais = Paciente::where(function ($query) use ($idsExternos, $cpfs, $cnss) {
            if (! empty($idsExternos)) {
                $query->orWhereIn('id_externo_esus', $idsExternos);
            }
            if (! empty($cpfs)) {
                $query->orWhereIn('cpf', $cpfs);
            }
            if (! empty($cnss)) {
                $query->orWhereIn('cns', $cnss);
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
                $mapaCpf[$pac->cpf] = $pac;
            }
            if (! empty($pac->cns)) {
                $mapaCns[$pac->cns] = $pac;
            }
        }

        $callbackGravacao = function () use ($mapeados, $mapaId, $mapaCpf, $mapaCns, &$metricas, $dryRun): void {
            foreach ($mapeados as $dados) {
                $idExt = $dados['id_externo_esus'] ?? null;
                $cpf = $dados['cpf'] ?? null;
                $cns = $dados['cns'] ?? null;

                $paciente = ($idExt && isset($mapaId[$idExt]))
                    ? $mapaId[$idExt]
                    : (($cpf && isset($mapaCpf[$cpf]))
                        ? $mapaCpf[$cpf]
                        : (($cns && isset($mapaCns[$cns])) ? $mapaCns[$cns] : null));

                if (! $paciente) {
                    // Em modo apenas-vínculos, NUNCA cria novo paciente!
                    $metricas['nao_cadastrados_local']++;

                    continue;
                }

                $metricas['localizados_local']++;
                $camposParaAtualizar = [];
                $houveAlteracao = false;

                // Caso 1: Paciente sem UBS (nem textual nem id) e dados do e-SUS trazem UBS de referência
                if (empty($paciente->ubs_referencia) && empty($paciente->ubs_id) && ! empty($dados['ubs_referencia'])) {
                    $camposParaAtualizar['ubs_referencia'] = $dados['ubs_referencia'];
                    $ubsModel = $this->resolverOuCriarUbs(
                        $dados['ubs_referencia'],
                        $dados['cnes_ubs'] ?? null,
                        $dryRun,
                        $paciente->municipio_ibge ?: ($dados['municipio_ibge'] ?? $ibgePadrao)
                    );
                    if ($ubsModel && $ubsModel->id > 0) {
                        $camposParaAtualizar['ubs_id'] = $ubsModel->id;
                    }
                    $metricas['ubs_complementadas']++;
                    $houveAlteracao = true;
                }
                // Caso 2: Paciente JÁ possui ubs_referencia textual, mas ubs_id está vazio (reconciliação sem sobrescrever o nome manual)
                elseif (empty($paciente->ubs_id) && ! empty($paciente->ubs_referencia)) {
                    $ubsModel = $this->localizarUbsExistente(
                        $paciente->ubs_referencia,
                        $dados['cnes_ubs'] ?? null,
                        $paciente->municipio_ibge ?: ($dados['municipio_ibge'] ?? $ibgePadrao)
                    );
                    if ($ubsModel && $ubsModel->id > 0) {
                        if (! $dryRun) {
                            $camposParaAtualizar['ubs_id'] = $ubsModel->id;
                        }
                        $metricas['ubs_complementadas']++;
                        $houveAlteracao = true;
                    }
                }

                // Complementa ACS somente se estiver vazio no registro local
                if (empty($paciente->nome_acs) && ! empty($dados['nome_acs'])) {
                    $camposParaAtualizar['nome_acs'] = $dados['nome_acs'];
                    $metricas['acs_complementados']++;
                    $houveAlteracao = true;
                }

                if ($houveAlteracao) {
                    $metricas['pacientes_atualizados']++;
                    if (! $dryRun) {
                        $camposParaAtualizar['data_ultima_sincronizacao_esus'] = now();
                        $paciente->update($camposParaAtualizar);
                    }
                } else {
                    $metricas['sem_alteracoes']++;
                }
            }
        };

        if ($dryRun) {
            $callbackGravacao();
        } else {
            DB::transaction($callbackGravacao);
        }

        return $metricas;
    }

    /**
     * Executa diagnóstico de somente-leitura da estrutura e vínculos no PostgreSQL do e-SUS PEC.
     *
     * @return array<string, mixed>
     */
    public function executarDiagnosticoVps(?PDO $pdoInjetado = null): array
    {
        $config = ConfiguracaoEsusPec::atual();
        $inicio = microtime(true);
        $pdo = $pdoInjetado ?? $this->criarConexaoPdo($config);
        $latencia = round((microtime(true) - $inicio) * 1000, 2);

        $diagnosticoSchema = $this->descobrirSchema($pdo);
        $aux = $diagnosticoSchema['tabelas_auxiliares'] ?? [];

        $tabelasVerificar = [
            'tb_acomp_cidadaos_vinculados',
            'tb_cidadao',
            'tb_unidade_saude',
            'tb_dim_unidade_saude',
            'tb_equipe',
            'tb_dim_equipe',
            'tb_dim_profissional',
            'tb_dim_cbo',
            'tb_dim_tempo',
            'tb_fat_cad_individual',
            'tb_fat_visita_domiciliar',
        ];

        $tabelasStatus = [];
        foreach ($tabelasVerificar as $tab) {
            $existe = ($tab === ($diagnosticoSchema['tabela_origem'] ?? null))
                || ! empty($aux[$tab])
                || in_array($tab, $diagnosticoSchema['tabelas_descobertas'], true);
            $total = null;
            if ($existe) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM {$tab};");
                    $total = $stmt ? (int) $stmt->fetchColumn() : null;
                } catch (Throwable) {
                    $total = null;
                }
            }
            $tabelasStatus[$tab] = [
                'existe' => $existe,
                'total_registros' => $total,
            ];
        }

        $colunasDw = $diagnosticoSchema['colunas_origem'] ?? [];
        $colunasVinculosInteresse = [
            'nu_cnes_vinc_equipe',
            'nu_cnes_vinc_unidade',
            'nu_cnes',
            'no_unidade_vinc',
            'no_unidade_saude',
            'nu_ine_vinc_equipe',
            'nu_ine',
            'no_equipe_vinc_equipe',
            'no_equipe',
            'nu_micro_area_domicilio',
            'nu_micro_area_tb_cidadao',
            'no_profissional_acs',
            'no_acs',
        ];

        $statusColunas = [];
        foreach ($colunasVinculosInteresse as $col) {
            $statusColunas[$col] = in_array($col, $colunasDw, true);
        }

        // Métricas de vínculos agregadas (ZERO PII de cidadãos)
        $totalCidadaosDw = $tabelasStatus['tb_acomp_cidadaos_vinculados']['total_registros'] ?? 0;
        $comCnes = 0;
        $comIne = 0;
        $comNomeUbs = 0;
        $comAcsDireto = 0;
        $topUnidades = [];
        $topEquipes = [];

        if (! empty($diagnosticoSchema['tabela_origem'])) {
            $tab = $diagnosticoSchema['tabela_origem'];

            if (! empty($statusColunas['nu_cnes_vinc_equipe'])) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM {$tab} WHERE nu_cnes_vinc_equipe IS NOT NULL AND TRIM(nu_cnes_vinc_equipe) != '';");
                    $comCnes = $stmt ? (int) $stmt->fetchColumn() : 0;
                } catch (Throwable) {
                }
            } elseif (! empty($statusColunas['nu_cnes'])) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM {$tab} WHERE nu_cnes IS NOT NULL AND TRIM(nu_cnes) != '';");
                    $comCnes = $stmt ? (int) $stmt->fetchColumn() : 0;
                } catch (Throwable) {
                }
            }

            if (! empty($statusColunas['nu_ine_vinc_equipe'])) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM {$tab} WHERE nu_ine_vinc_equipe IS NOT NULL AND TRIM(nu_ine_vinc_equipe) != '';");
                    $comIne = $stmt ? (int) $stmt->fetchColumn() : 0;
                } catch (Throwable) {
                }
            } elseif (! empty($statusColunas['nu_ine'])) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM {$tab} WHERE nu_ine IS NOT NULL AND TRIM(nu_ine) != '';");
                    $comIne = $stmt ? (int) $stmt->fetchColumn() : 0;
                } catch (Throwable) {
                }
            }

            if (! empty($statusColunas['no_unidade_vinc'])) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM {$tab} WHERE no_unidade_vinc IS NOT NULL AND TRIM(no_unidade_vinc) != '';");
                    $comNomeUbs = $stmt ? (int) $stmt->fetchColumn() : 0;
                } catch (Throwable) {
                }
            } elseif (! empty($statusColunas['no_unidade_saude'])) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM {$tab} WHERE no_unidade_saude IS NOT NULL AND TRIM(no_unidade_saude) != '';");
                    $comNomeUbs = $stmt ? (int) $stmt->fetchColumn() : 0;
                } catch (Throwable) {
                }
            }

            if (! empty($statusColunas['no_profissional_acs'])) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM {$tab} WHERE no_profissional_acs IS NOT NULL AND TRIM(no_profissional_acs) != '';");
                    $comAcsDireto = $stmt ? (int) $stmt->fetchColumn() : 0;
                } catch (Throwable) {
                }
            }

            // Top 5 Unidades por CNES
            $colCnes = ! empty($statusColunas['nu_cnes_vinc_equipe']) ? 'nu_cnes_vinc_equipe' : (! empty($statusColunas['nu_cnes']) ? 'nu_cnes' : null);
            if ($colCnes) {
                try {
                    $sql = "SELECT {$colCnes} as cnes, COUNT(*) as total FROM {$tab} WHERE {$colCnes} IS NOT NULL AND TRIM({$colCnes}) != '' GROUP BY {$colCnes} ORDER BY total DESC LIMIT 5;";
                    $stmt = $pdo->query($sql);
                    if ($stmt) {
                        $topUnidades = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    }
                } catch (Throwable) {
                }
            }

            // Top 5 Equipes por INE
            $colIne = ! empty($statusColunas['nu_ine_vinc_equipe']) ? 'nu_ine_vinc_equipe' : (! empty($statusColunas['nu_ine']) ? 'nu_ine' : null);
            $colNoEq = ! empty($statusColunas['no_equipe_vinc_equipe']) ? 'no_equipe_vinc_equipe' : (! empty($statusColunas['no_equipe']) ? 'no_equipe' : null);
            if ($colIne) {
                try {
                    $selectEq = $colNoEq ? "{$colIne} as ine, {$colNoEq} as nome_equipe" : "{$colIne} as ine, '' as nome_equipe";
                    $groupEq = $colNoEq ? "{$colIne}, {$colNoEq}" : "{$colIne}";
                    $sql = "SELECT {$selectEq}, COUNT(*) as total FROM {$tab} WHERE {$colIne} IS NOT NULL AND TRIM({$colIne}) != '' GROUP BY {$groupEq} ORDER BY total DESC LIMIT 5;";
                    $stmt = $pdo->query($sql);
                    if ($stmt) {
                        $topEquipes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    }
                } catch (Throwable) {
                }
            }
        }

        // Cruzamento dos nomes das UBSs do Top 5
        $dicionarios = $this->carregarDicionariosUbsEquipes($pdo, $diagnosticoSchema);
        $mapaUbs = $dicionarios['ubs'];
        foreach ($topUnidades as &$u) {
            $c = (string) ($u['cnes'] ?? '');
            $u['nome_unidade'] = $mapaUbs[$c] ?? 'Nome não mapeado';
        }
        unset($u);

        // Contagem de profissionais ACS no PEC (vinculados por CBO em tb_fat_cad_individual ou tb_fat_visita_domiciliar)
        $totalAcsPec = 0;
        if (! empty($aux['tb_fat_cad_individual']) && ! empty($aux['tb_dim_profissional']) && ! empty($aux['tb_dim_cbo'])) {
            try {
                $stmt = $pdo->query("
                    SELECT COUNT(DISTINCT fci.co_dim_profissional)
                    FROM tb_fat_cad_individual fci
                    JOIN tb_dim_cbo c ON c.co_seq_dim_cbo = fci.co_dim_cbo
                    WHERE REPLACE(REPLACE(c.nu_cbo, '-', ''), ' ', '') IN ('515105', '322255')
                ");
                $totalAcsPec = $stmt ? (int) $stmt->fetchColumn() : 0;
            } catch (Throwable) {
            }
        }

        // Situação atual no CER Digital
        $totalPacientesLocal = Paciente::count();
        $comUbsLocal = Paciente::whereNotNull('ubs_referencia')->where('ubs_referencia', '!=', '')->count();
        $comAcsLocal = Paciente::whereNotNull('nome_acs')->where('nome_acs', '!=', '')->count();

        return [
            'sucesso' => true,
            'latencia_ms' => $latencia,
            'diagnostico_schema' => $diagnosticoSchema,
            'tabelas_status' => $tabelasStatus,
            'colunas_vinculos' => $statusColunas,
            'metricas_vinculos' => [
                'total_cidadaos_dw' => $totalCidadaosDw,
                'com_cnes_vinculado' => $comCnes,
                'com_ine_vinculado' => $comIne,
                'com_nome_unidade_direto' => $comNomeUbs,
                'com_acs_direto' => $comAcsDireto,
                'total_acs_cadastrados_pec' => $totalAcsPec,
                'top_unidades' => $topUnidades,
                'top_equipes' => $topEquipes,
            ],
            'pacientes_cer' => [
                'total_locais' => $totalPacientesLocal,
                'com_ubs_preenchida' => $comUbsLocal,
                'sem_ubs_preenchida' => max(0, $totalPacientesLocal - $comUbsLocal),
                'com_acs_preenchido' => $comAcsLocal,
                'sem_acs_preenchido' => max(0, $totalPacientesLocal - $comAcsLocal),
            ],
        ];
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

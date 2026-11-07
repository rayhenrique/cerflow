<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ConfiguracaoEstabelecimento;
use App\Models\ConfiguracaoEsusPec;
use App\Models\HistoricoSincronizacaoEsus;
use App\Models\Paciente;
use App\Rules\CnsValido;
use Carbon\Carbon;
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
    public function sincronizarPacientes(string $tipoExecucao = 'MANUAL', ?PDO $pdoInjetado = null): HistoricoSincronizacaoEsus
    {
        $inicio = microtime(true);
        $config = ConfiguracaoEsusPec::atual();

        $historico = HistoricoSincronizacaoEsus::create([
            'iniciado_em' => now(),
            'tipo_execucao' => $tipoExecucao === 'AGENDADA' ? 'AGENDADA' : 'MANUAL',
            'status' => 'EM_ANDAMENTO',
            'total_consultado' => 0,
            'novos_pacientes' => 0,
            'pacientes_atualizados' => 0,
            'registros_ignorados' => 0,
            'duplicidades_identificadas' => 0,
        ]);

        if (! $config->ativo && $pdoInjetado === null) {
            $historico->update([
                'status' => 'INTERROMPIDO',
                'finalizado_em' => now(),
                'duracao_segundos' => 0,
                'mensagem_erro' => 'A sincronização não foi executada porque a integração e-SUS APS PEC está inativa nas configurações.',
            ]);

            return $historico;
        }

        try {
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

            // Consulta paginada em lotes (chunking) para não estourar memória
            $limite = 200;
            $offset = 0;
            $totalConsultado = 0;
            $novosPacientes = 0;
            $pacientesAtualizados = 0;
            $registrosIgnorados = 0;
            $duplicidades = 0;

            while (true) {
                $stmt = $pdo->query("SELECT * FROM {$tabelaOrigem} LIMIT {$limite} OFFSET {$offset};");
                $linhas = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

                if (empty($linhas)) {
                    break;
                }

                foreach ($linhas as $linha) {
                    $totalConsultado++;
                    $dadosMapeados = $this->mapearCidadaoParaPaciente($linha, $ibgePadrao);

                    if (empty($dadosMapeados['nome']) || empty($dadosMapeados['data_nascimento'])) {
                        $registrosIgnorados++;

                        continue;
                    }

                    $resultado = $this->processarRegistroPaciente($dadosMapeados);

                    if ($resultado === 'NOVO') {
                        $novosPacientes++;
                    } elseif ($resultado === 'ATUALIZADO') {
                        $pacientesAtualizados++;
                    } elseif ($resultado === 'DUPLICIDADE') {
                        $duplicidades++;
                    } else {
                        $registrosIgnorados++;
                    }
                }

                $offset += $limite;
            }

            $duracao = (int) round(microtime(true) - $inicio);

            $historico->update([
                'status' => 'SUCESSO',
                'finalizado_em' => now(),
                'duracao_segundos' => $duracao,
                'total_consultado' => $totalConsultado,
                'novos_pacientes' => $novosPacientes,
                'pacientes_atualizados' => $pacientesAtualizados,
                'registros_ignorados' => $registrosIgnorados,
                'duplicidades_identificadas' => $duplicidades,
                'detalhes' => [
                    'tipo_banco' => $diagnostico['tipo_banco'],
                    'tabela' => $tabelaOrigem,
                    'versao_pec' => $diagnostico['versao_pec'],
                ],
            ]);

            $config->update([
                'ultima_sincronizacao_em' => now(),
                'ultima_sincronizacao_status' => 'SUCESSO',
            ]);

            return $historico;
        } catch (Throwable $e) {
            $duracao = (int) round(microtime(true) - $inicio);
            $msgErro = $e->getMessage();

            $historico->update([
                'status' => 'ERRO',
                'finalizado_em' => now(),
                'duracao_segundos' => $duracao,
                'mensagem_erro' => $msgErro,
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

        // Identificador externo estável do cidadão no PEC
        $idExterno = $resolver(['co_seq_fat_cidadao_vinculado', 'co_cidadao', 'co_seq_cidadao', 'id_cidadao', 'id']);

        // Nome completo
        $nome = $resolver(['no_cidadao', 'nome', 'nm_cidadao', 'nome_cidadao']);

        // CPF (apenas números)
        $cpfCru = $resolver(['nu_cpf', 'cpf', 'num_cpf']);
        $cpfLimpo = $cpfCru ? (preg_replace('/\D/', '', $cpfCru) ?: null) : null;
        if ($cpfLimpo !== null && strlen($cpfLimpo) !== 11) {
            $cpfLimpo = null;
        }

        // CNS (apenas números)
        $cnsCru = $resolver(['nu_cns', 'cns', 'num_cns']);
        $cnsLimpo = $cnsCru ? (preg_replace('/\D/', '', $cnsCru) ?: null) : null;
        if ($cnsLimpo !== null && (! CnsValido::validar($cnsLimpo) || strlen($cnsLimpo) !== 15)) {
            $cnsLimpo = null;
        }

        // Data de nascimento
        $dtCru = $resolver(['dt_nascimento', 'data_nascimento', 'dt_nasc']);
        $dtNascimento = null;
        if ($dtCru !== null) {
            try {
                $dtNascimento = Carbon::parse($dtCru)->format('Y-m-d');
            } catch (Throwable) {
                $dtNascimento = null;
            }
        }

        // Sexo
        $sexoCru = $resolver(['no_sexo', 'sg_sexo', 'co_sexo', 'sexo']);
        $sexo = 'M';
        if ($sexoCru !== null) {
            $primeiroChar = strtoupper(substr($sexoCru, 0, 1));
            $sexo = $primeiroChar === 'F' ? 'F' : 'M';
        }

        // Nome da mãe
        $nomeMae = $resolver(['no_mae', 'nome_mae', 'nm_mae']);

        // Telefone
        $telefone = $resolver(['nu_telefone_celular', 'nu_telefone_contato', 'nu_telefone', 'telefone', 'celular']);

        // Endereço e Bairro
        $endereco = $resolver(['ds_logradouro', 'ds_endereco', 'endereco', 'ds_logradouro_completo']);
        $bairro = $resolver(['no_bairro', 'bairro', 'nm_bairro']);

        // Município IBGE
        $ibge = $resolver(['co_ibge', 'co_municipio_ibge', 'municipio_ibge']) ?: $ibgePadrao;
        $ibgeLimpo = preg_replace('/\D/', '', $ibge) ?: $ibgePadrao;
        $ibgeLimpo = substr($ibgeLimpo, 0, 6);

        // UBS de referência
        $ubs = $resolver(['no_unidade_saude', 'no_ubs', 'no_equipe', 'ubs_referencia']);
        $acs = $resolver(['no_profissional_acs', 'no_acs', 'nome_acs']);

        return [
            'id_externo_esus' => $idExterno,
            'nome' => $nome,
            'cpf' => $cpfLimpo,
            'cns' => $cnsLimpo,
            'data_nascimento' => $dtNascimento,
            'sexo' => $sexo,
            'nome_mae' => $nomeMae,
            'telefone_contato' => $telefone,
            'endereco' => $endereco,
            'bairro' => $bairro,
            'municipio_ibge' => $ibgeLimpo,
            'ubs_referencia' => $ubs,
            'nome_acs' => $acs,
        ];
    }

    /**
     * Aplica regras de desduplicação do SUS e persistência unidirecional.
     *
     * @param  array<string, mixed>  $dados
     * @return 'NOVO'|'ATUALIZADO'|'DUPLICIDADE'|'IGNORADO'
     */
    public function processarRegistroPaciente(array $dados): string
    {
        $idExterno = $dados['id_externo_esus'];
        $cpf = $dados['cpf'];
        $cns = $dados['cns'];
        $nome = $dados['nome'];
        $dtNascimento = $dados['data_nascimento'];

        // 1. Identificar primeiro pelo identificador externo estável do PEC
        if (! empty($idExterno)) {
            $pacientePorIdExterno = Paciente::where('id_externo_esus', $idExterno)->first();
            if ($pacientePorIdExterno) {
                $this->atualizarPacienteLocalSemSobrescrever($pacientePorIdExterno, $dados);

                return 'ATUALIZADO';
            }
        }

        // 2. Busca por CPF exato
        $pacienteCpf = null;
        if (! empty($cpf)) {
            $candidatosCpf = Paciente::where('cpf', $cpf)->get();
            if ($candidatosCpf->count() > 1) {
                return 'DUPLICIDADE'; // Múltiplas ocorrências locais para o mesmo CPF requerem revisão manual
            }
            if ($candidatosCpf->count() === 1) {
                $pacienteCpf = $candidatosCpf->first();
            }
        }

        // 3. Busca por CNS exato
        $pacienteCns = null;
        if (! empty($cns)) {
            $candidatosCns = Paciente::where('cns', $cns)->get();
            if ($candidatosCns->count() > 1) {
                return 'DUPLICIDADE'; // Múltiplos registros com mesmo CNS
            }
            if ($candidatosCns->count() === 1) {
                $pacienteCns = $candidatosCns->first();
            }
        }

        // Conflito cruzado: CPF aponta para um paciente e CNS aponta para outro
        if ($pacienteCpf && $pacienteCns && $pacienteCpf->id !== $pacienteCns->id) {
            return 'DUPLICIDADE';
        }

        $pacienteExistente = $pacienteCpf ?: $pacienteCns;

        // Se encontrou correspondência unívoca por documento, vincula e atualiza
        if ($pacienteExistente) {
            $this->atualizarPacienteLocalSemSobrescrever($pacienteExistente, $dados);

            return 'ATUALIZADO';
        }

        // 4. Sem CPF e sem CNS: verificar se já existe paciente com mesmo Nome + Data de Nascimento
        if (empty($cpf) && empty($cns)) {
            $duplicadoNomeData = Paciente::where('nome', trim($nome))
                ->whereDate('data_nascimento', $dtNascimento)
                ->first();

            if ($duplicadoNomeData) {
                $this->atualizarPacienteLocalSemSobrescrever($duplicadoNomeData, $dados);

                return 'ATUALIZADO';
            }
        }

        // 5. Inserção de novo paciente com origem e-SUS APS
        Paciente::create([
            'nome' => trim($nome),
            'cpf' => $cpf,
            'cns' => $cns,
            'data_nascimento' => $dtNascimento,
            'sexo' => $dados['sexo'],
            'nome_mae' => $dados['nome_mae'],
            'municipio_ibge' => $dados['municipio_ibge'],
            'ubs_referencia' => $dados['ubs_referencia'],
            'nome_acs' => $dados['nome_acs'],
            'telefone_contato' => $dados['telefone_contato'],
            'endereco' => $dados['endereco'],
            'bairro' => $dados['bairro'],
            'origem_cadastro' => 'ESUS_APS',
            'id_externo_esus' => $idExterno,
            'data_ultima_sincronizacao_esus' => now(),
            'ativo' => true,
        ]);

        return 'NOVO';
    }

    /**
     * Atualiza dados locais preservando valores preenchidos contra valores nulos da origem
     * e protegendo diagnósticos e modalidades clínicas.
     *
     * @param  array<string, mixed>  $dadosNovos
     */
    private function atualizarPacienteLocalSemSobrescrever(Paciente $paciente, array $dadosNovos): void
    {
        $camposParaAtualizar = [];

        // Atualiza vínculo externo se não estava preenchido
        if (empty($paciente->id_externo_esus) && ! empty($dadosNovos['id_externo_esus'])) {
            $camposParaAtualizar['id_externo_esus'] = $dadosNovos['id_externo_esus'];
        }

        // Se local não tiver CPF e a origem tiver, atualiza
        if (empty($paciente->cpf) && ! empty($dadosNovos['cpf'])) {
            $camposParaAtualizar['cpf'] = $dadosNovos['cpf'];
        }

        // Se local não tiver CNS e a origem tiver, atualiza
        if (empty($paciente->cns) && ! empty($dadosNovos['cns'])) {
            $camposParaAtualizar['cns'] = $dadosNovos['cns'];
        }

        // Atualiza dados cadastrais se local estiver vazio
        $camposOpcionais = [
            'nome_mae',
            'telefone_contato',
            'endereco',
            'bairro',
            'ubs_referencia',
            'nome_acs',
        ];

        foreach ($camposOpcionais as $campo) {
            if (empty($paciente->{$campo}) && ! empty($dadosNovos[$campo])) {
                $camposParaAtualizar[$campo] = $dadosNovos[$campo];
            }
        }

        $camposParaAtualizar['data_ultima_sincronizacao_esus'] = now();

        $paciente->update($camposParaAtualizar);
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

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ConfiguracaoEsusPec;
use App\Models\HistoricoSincronizacaoEsus;
use App\Models\Paciente;
use App\Models\UnidadeBasicaSaude;
use App\Services\EsusPecIntegrationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PDO;
use Tests\TestCase;

class ComplementarVinculosUbsAcsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $config = ConfiguracaoEsusPec::atual();
        $config->update(['ativo' => true]);
    }

    protected function tearDown(): void
    {
        EsusPecIntegrationService::definirPdoTeste(null);
        Cache::forget(EsusPecIntegrationService::LOCK_NAME);
        parent::tearDown();
    }

    /**
     * Cria um banco SQLite em memória simulando todas as tabelas dimensionais e operacionais do PEC.
     */
    protected function criarPdoPecCompleto(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. Tabela oficial de acompanhamento do DW
        $pdo->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE,
                no_sexo_cidadao VARCHAR(20),
                no_mae_cidadao VARCHAR(255),
                nu_telefone_celular VARCHAR(20),
                ds_logradouro_domicilio VARCHAR(255),
                no_bairro_domicilio VARCHAR(255),
                co_ibge_domicilio VARCHAR(6),
                nu_cnes_vinc_equipe VARCHAR(20),
                nu_cnes_vinc_unidade VARCHAR(20),
                no_unidade_vinc VARCHAR(255),
                nu_ine_vinc_equipe VARCHAR(20),
                no_equipe_vinc_equipe VARCHAR(255),
                nu_micro_area_domicilio VARCHAR(10)
            );
        ');

        // 2. Dimensão Unidade de Saúde (DW)
        $pdo->exec('
            CREATE TABLE tb_dim_unidade_saude (
                co_seq_dim_unidade_saude INTEGER PRIMARY KEY,
                nu_cnes VARCHAR(20),
                no_unidade_saude VARCHAR(255)
            );
        ');

        // 3. Tabela Operacional Unidade de Saúde
        $pdo->exec('
            CREATE TABLE tb_unidade_saude (
                co_seq_unidade_saude INTEGER PRIMARY KEY,
                nu_cnes VARCHAR(20),
                no_unidade_saude VARCHAR(255)
            );
        ');

        // 4. Tabela Operacional Equipe
        $pdo->exec('
            CREATE TABLE tb_equipe (
                co_seq_equipe INTEGER PRIMARY KEY,
                nu_ine VARCHAR(20),
                no_equipe VARCHAR(255),
                co_unidade_saude INTEGER
            );
        ');

        // 5. Dimensão CBO
        $pdo->exec('
            CREATE TABLE tb_dim_cbo (
                co_seq_dim_cbo INTEGER PRIMARY KEY,
                nu_cbo VARCHAR(20),
                no_cbo VARCHAR(255)
            );
        ');

        // 6. Dimensão Profissional
        $pdo->exec('
            CREATE TABLE tb_dim_profissional (
                co_seq_dim_profissional INTEGER PRIMARY KEY,
                nu_cns VARCHAR(15),
                no_profissional VARCHAR(255)
            );
        ');

        // 7. Dimensão Tempo
        $pdo->exec('
            CREATE TABLE tb_dim_tempo (
                co_seq_dim_tempo INTEGER PRIMARY KEY,
                dt_registro DATE
            );
        ');

        // 8. Tabela de Fato: Cadastro Individual (MICI)
        $pdo->exec('
            CREATE TABLE tb_fat_cad_individual (
                co_seq_fat_cad_individual INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                co_dim_tempo INTEGER,
                co_dim_profissional INTEGER,
                co_dim_cbo INTEGER,
                st_ficha_inativa INTEGER DEFAULT 0,
                st_recusa_cadastro INTEGER DEFAULT 0
            );
        ');

        // 9. Tabela de Fato: Visita Domiciliar
        $pdo->exec('
            CREATE TABLE tb_fat_visita_domiciliar (
                co_seq_fat_visita_domiciliar INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                co_dim_tempo INTEGER,
                co_dim_profissional INTEGER,
                co_dim_cbo INTEGER
            );
        ');

        // Carga de Dicionários Auxiliares
        // Unidades
        $pdo->exec("INSERT INTO tb_dim_unidade_saude VALUES (1, '2001', 'UBS CENTRAL DW');");
        $pdo->exec("INSERT INTO tb_unidade_saude VALUES (10, '2002', 'CENTRO DE SAUDE OPERACIONAL');");

        // Equipe vinculada à unidade 10
        $pdo->exec("INSERT INTO tb_equipe VALUES (100, '0001234567', 'EQUIPE DA FAMILIA AZUL', 10);");

        // CBOs: ACS (5151-05), TACS (3222-55), Médico (2251-25)
        $pdo->exec("INSERT INTO tb_dim_cbo VALUES (1, '5151-05', 'AGENTE COMUNITARIO DE SAUDE');");
        $pdo->exec("INSERT INTO tb_dim_cbo VALUES (2, '3222-55', 'TECNICO DE AGENTE COMUNITARIO DE SAUDE');");
        $pdo->exec("INSERT INTO tb_dim_cbo VALUES (3, '2251-25', 'MEDICO CLINICO');");

        // Profissionais
        $pdo->exec("INSERT INTO tb_dim_profissional VALUES (1, '700000000000001', 'MARIA DA SILVA ACS');");
        $pdo->exec("INSERT INTO tb_dim_profissional VALUES (2, '700000000000002', 'JOAO VISITA TACS');");
        $pdo->exec("INSERT INTO tb_dim_profissional VALUES (3, '700000000000003', 'DR. ROBERTO MEDICO');");

        // Tempos
        $pdo->exec("INSERT INTO tb_dim_tempo VALUES (1, '2026-01-10');");
        $pdo->exec("INSERT INTO tb_dim_tempo VALUES (2, '2026-05-20');");

        // Cidadãos no DW:
        // Cidadão 1001: Tem CNES 2001 -> Deve resolver UBS CENTRAL DW. Tem Cadastro Individual por MARIA DA SILVA ACS (CBO 5151-05).
        $pdo->exec("
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (10, 1001, 'Cidadao Um', '11111111101', '898001149470878', '1990-01-01', 'M', 'Mae Um', '8299990001', 'Rua A', 'Centro', '270430', '2001', '2001', '', '0009999999', 'EQUIPE OUTRA', '01');
        ");
        $pdo->exec('INSERT INTO tb_fat_cad_individual VALUES (1, 1001, 1, 1, 1, 0, 0);');

        // Cidadão 1002: CNES nulo, mas tem INE 0001234567 -> Deve resolver CENTRO DE SAUDE OPERACIONAL.
        // Tem cadastro apenas por DR. ROBERTO MEDICO (CBO 2251-25) -> ACS deve ser NULL (Médico NUNCA é ACS!).
        $pdo->exec("
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (20, 1002, 'Cidadao Dois', '11111111102', '898001149470886', '1992-02-02', 'F', 'Mae Dois', '8299990002', 'Rua B', 'Bairro', '270430', NULL, NULL, '', '0001234567', 'EQUIPE DA FAMILIA AZUL', '02');
        ");
        $pdo->exec('INSERT INTO tb_fat_cad_individual VALUES (2, 1002, 1, 3, 3, 0, 0);');

        // Cidadão 1003: Sem cadastro individual, mas tem visita domiciliar por JOAO VISITA TACS (CBO 3222-55).
        // CNES 2002 diretamente no registro.
        $pdo->exec("
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (30, 1003, 'Cidadao Tres', '11111111103', '898001149470894', '1993-03-03', 'M', 'Mae Tres', '8299990003', 'Rua C', 'Bairro', '270430', '2002', '2002', '', '0001234567', 'EQUIPE DA FAMILIA AZUL', '03');
        ");
        $pdo->exec('INSERT INTO tb_fat_visita_domiciliar VALUES (1, 1003, 2, 2, 2);');

        // Cidadão 1004: Sem vínculos de UBS nem ACS.
        $pdo->exec("
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (40, 1004, 'Cidadao Quatro', '11111111104', '898001149470908', '1994-04-04', 'F', 'Mae Quatro', '8299990004', 'Rua D', 'Bairro', '270430', NULL, NULL, '', NULL, '', '00');
        ");

        // Cidadão 1005: Tem CNES 9999 desconhecido -> Fallback para 'UBS CNES 9999'. Sem ACS.
        $pdo->exec("
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (50, 1005, 'Cidadao Cinco', '11111111105', '898001149470916', '1995-05-05', 'M', 'Mae Cinco', '8299990005', 'Rua E', 'Bairro', '270430', '9999', '9999', '', NULL, '', '00');
        ");

        // Cidadãos 1006 e 1007: Estão no PEC mas NÃO existem no CER Digital (para testar que o modo complementação não os cria).
        $pdo->exec("
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (60, 1006, 'Cidadao Seis Extra', '11111111106', '898001149470924', '1996-06-06', 'M', 'Mae Seis', '8299990006', 'Rua F', 'Bairro', '270430', '2001', '2001', '', NULL, '', '00');
        ");
        $pdo->exec("
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (70, 1007, 'Cidadao Sete Extra', '11111111107', '898001149470932', '1997-07-07', 'F', 'Mae Sete', '8299990007', 'Rua G', 'Bairro', '270430', '2001', '2001', '', NULL, '', '00');
        ");

        return $pdo;
    }

    public function test_mapeamento_ubs_e_acs_individual_corretamente(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        $service = new EsusPecIntegrationService;
        $diagnostico = $service->descobrirSchema($pdo);
        $dicionarios = $service->carregarDicionariosUbsEquipes($pdo, $diagnostico);

        $stmt = $pdo->query('SELECT * FROM tb_acomp_cidadaos_vinculados WHERE co_fat_cidadao_pec = 1001');
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        $mapaAcs = $service->resolverAcsParaLote($pdo, [1001], $diagnostico);

        $pacienteMapeado = $service->mapearCidadaoParaPaciente(
            $linha,
            '270430',
            $dicionarios['ubs'],
            $dicionarios['equipes'],
            $mapaAcs
        );

        $this->assertSame('UBS CENTRAL DW', $pacienteMapeado['ubs_referencia']);
        $this->assertSame('MARIA DA SILVA ACS', $pacienteMapeado['nome_acs']);
    }

    public function test_ubs_atraves_do_ine_da_equipe_sem_confundir_com_nome_da_equipe(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        $service = new EsusPecIntegrationService;
        $diagnostico = $service->descobrirSchema($pdo);
        $dicionarios = $service->carregarDicionariosUbsEquipes($pdo, $diagnostico);

        $stmt = $pdo->query('SELECT * FROM tb_acomp_cidadaos_vinculados WHERE co_fat_cidadao_pec = 1002');
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        $mapaAcs = $service->resolverAcsParaLote($pdo, [1002], $diagnostico);

        $pacienteMapeado = $service->mapearCidadaoParaPaciente(
            $linha,
            '270430',
            $dicionarios['ubs'],
            $dicionarios['equipes'],
            $mapaAcs
        );

        // O nome da UBS deve ser o resolvido da unidade vinculada à equipe (CENTRO DE SAUDE OPERACIONAL)
        // e NUNCA o nome da equipe (EQUIPE DA FAMILIA AZUL)
        $this->assertSame('CENTRO DE SAUDE OPERACIONAL', $pacienteMapeado['ubs_referencia']);
        $this->assertNotSame('EQUIPE DA FAMILIA AZUL', $pacienteMapeado['ubs_referencia']);
    }

    public function test_profissional_medico_ou_enfermeiro_nunca_e_atribuido_como_acs(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        $service = new EsusPecIntegrationService;
        $diagnostico = $service->descobrirSchema($pdo);

        // Cidadão 1002 tem cadastro apenas pelo DR. ROBERTO MEDICO (CBO 2251-25)
        $mapaAcs = $service->resolverAcsParaLote($pdo, [1002], $diagnostico);

        $this->assertArrayNotHasKey(1002, $mapaAcs, 'Médico não deve ser considerado ACS');
    }

    public function test_acs_obtido_por_visita_domiciliar_quando_sem_cadastro_individual(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        $service = new EsusPecIntegrationService;
        $diagnostico = $service->descobrirSchema($pdo);

        // Cidadão 1003 tem visita domiciliar com CBO 3222-55 (TACS)
        $mapaAcs = $service->resolverAcsParaLote($pdo, [1003], $diagnostico);

        $this->assertArrayHasKey(1003, $mapaAcs);
        $this->assertSame('JOAO VISITA TACS', $mapaAcs[1003]);
    }

    public function test_cidadao_sem_vinculos_permanece_com_ubs_e_acs_nulos(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        $service = new EsusPecIntegrationService;
        $diagnostico = $service->descobrirSchema($pdo);
        $dicionarios = $service->carregarDicionariosUbsEquipes($pdo, $diagnostico);

        $stmt = $pdo->query('SELECT * FROM tb_acomp_cidadaos_vinculados WHERE co_fat_cidadao_pec = 1004');
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        $mapaAcs = $service->resolverAcsParaLote($pdo, [1004], $diagnostico);

        $pacienteMapeado = $service->mapearCidadaoParaPaciente(
            $linha,
            '270430',
            $dicionarios['ubs'],
            $dicionarios['equipes'],
            $mapaAcs
        );

        $this->assertNull($pacienteMapeado['ubs_referencia']);
        $this->assertNull($pacienteMapeado['nome_acs']);
    }

    public function test_cnes_desconhecido_gera_identificacao_formal_sem_erro(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        $service = new EsusPecIntegrationService;
        $diagnostico = $service->descobrirSchema($pdo);
        $dicionarios = $service->carregarDicionariosUbsEquipes($pdo, $diagnostico);

        $stmt = $pdo->query('SELECT * FROM tb_acomp_cidadaos_vinculados WHERE co_fat_cidadao_pec = 1005');
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        $pacienteMapeado = $service->mapearCidadaoParaPaciente(
            $linha,
            '270430',
            $dicionarios['ubs'],
            $dicionarios['equipes'],
            []
        );

        $this->assertSame('UBS CNES 9999', $pacienteMapeado['ubs_referencia']);
        $this->assertNull($pacienteMapeado['nome_acs']);
    }

    public function test_modo_apenas_vinculos_complementa_pacientes_sem_criar_novos(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        EsusPecIntegrationService::definirPdoTeste($pdo);

        // Cria previamente os 5 pacientes locais no banco do CER Digital
        // Todos com ubs_referencia = null e nome_acs = null (situação real pós-primeira sincronização)
        $cnss = [
            1 => '898001149470878',
            2 => '898001149470886',
            3 => '898001149470894',
            4 => '898001149470908',
            5 => '898001149470916',
        ];

        for ($i = 1; $i <= 5; $i++) {
            Paciente::create([
                'id_externo_esus' => (string) (1000 + $i),
                'nome' => "Paciente Local {$i}",
                'cpf' => sprintf('111111111%02d', $i),
                'cns' => $cnss[$i],
                'data_nascimento' => '1990-01-01',
                'sexo' => 'M',
                'municipio_ibge' => '270430',
                'endereco' => 'Endereco Original Local',
                'telefone_contato' => '82999990000',
                'ubs_referencia' => null,
                'nome_acs' => null,
                'ativo' => true,
            ]);
        }

        $this->assertSame(5, Paciente::count());

        // Executa o comando em modo --apenas-vinculos
        $this->artisan('esus:sincronizar', ['--apenas-vinculos' => true, '--force' => true, '--reiniciar' => true])
            ->expectsOutputToContain('CER DIGITAL — COMPLEMENTAÇÃO EXCLUSIVA DE VÍNCULOS UBS/ACS (CLI)')
            ->expectsOutputToContain('RESUMO CONSOLIDADO DA COMPLEMENTAÇÃO DE VÍNCULOS')
            ->expectsOutputToContain('[SUCESSO] Vínculos de UBS e ACS complementados com êxito!')
            ->assertSuccessful();

        // GARANTIA 1: A contagem total de pacientes NÃO aumentou (nenhum paciente extra foi criado)
        $this->assertSame(5, Paciente::count(), 'O modo apenas-vinculos jamais deve criar novos registros.');

        // GARANTIA 2: Dados cadastrais e pessoais originais permanecem intactos
        $paciente1 = Paciente::where('id_externo_esus', '1001')->first();
        $this->assertNotNull($paciente1);
        $this->assertSame('Paciente Local 1', $paciente1->nome);
        $this->assertSame('Endereco Original Local', $paciente1->endereco);
        $this->assertSame('82999990000', $paciente1->telefone_contato);

        // GARANTIA 3: Vínculos foram complementados com sucesso
        $this->assertSame('UBS CENTRAL DW', $paciente1->ubs_referencia);
        $this->assertSame('MARIA DA SILVA ACS', $paciente1->nome_acs);

        $paciente2 = Paciente::where('id_externo_esus', '1002')->first();
        $this->assertSame('CENTRO DE SAUDE OPERACIONAL', $paciente2->ubs_referencia);
        $this->assertNull($paciente2->nome_acs, 'Médico não deve ser atribuído como ACS');

        $paciente3 = Paciente::where('id_externo_esus', '1003')->first();
        $this->assertSame('CENTRO DE SAUDE OPERACIONAL', $paciente3->ubs_referencia);
        $this->assertSame('JOAO VISITA TACS', $paciente3->nome_acs);

        // Registros extras do PEC (1006 e 1007) foram ignorados e não cadastrados
        $this->assertNull(Paciente::where('id_externo_esus', '1006')->first());
        $this->assertNull(Paciente::where('id_externo_esus', '1007')->first());

        // Histórico registrado corretamente
        $historico = HistoricoSincronizacaoEsus::latest('id')->first();
        $this->assertNotNull($historico);
        $this->assertSame('SUCESSO', $historico->status);
        $this->assertSame(0, $historico->novos_pacientes);
        $this->assertSame('APENAS_VINCULOS', $historico->detalhes['modo_executado']);
    }

    public function test_modo_apenas_vinculos_preserva_valores_manuais_existentes(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        EsusPecIntegrationService::definirPdoTeste($pdo);

        $paciente = Paciente::create([
            'id_externo_esus' => '1001',
            'nome' => 'Paciente Com Dado Manual',
            'cpf' => '11111111101',
            'cns' => '898001149470878',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'ubs_referencia' => 'UBS DIGITADA MANUALMENTE',
            'nome_acs' => 'ACS DIGITADO MANUALMENTE',
            'ativo' => true,
        ]);

        $this->artisan('esus:sincronizar', ['--apenas-vinculos' => true, '--force' => true, '--reiniciar' => true])
            ->assertSuccessful();

        $paciente->refresh();
        $this->assertSame('UBS DIGITADA MANUALMENTE', $paciente->ubs_referencia);
        $this->assertSame('ACS DIGITADO MANUALMENTE', $paciente->nome_acs);
    }

    public function test_modo_apenas_vinculos_dry_run_nao_grava_alteracoes(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        EsusPecIntegrationService::definirPdoTeste($pdo);

        $paciente = Paciente::create([
            'id_externo_esus' => '1001',
            'nome' => 'Paciente Dry Run',
            'cpf' => '11111111101',
            'cns' => '898001149470878',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'ubs_referencia' => null,
            'nome_acs' => null,
            'ativo' => true,
        ]);

        $this->artisan('esus:sincronizar', ['--apenas-vinculos' => true, '--dry-run' => true, '--force' => true])
            ->expectsOutputToContain('[MODO SIMULAÇÃO CONCLUÍDO]')
            ->assertSuccessful();

        $paciente->refresh();
        $this->assertNull($paciente->ubs_referencia, 'Modo dry-run não deve gravar ubs');
        $this->assertNull($paciente->nome_acs, 'Modo dry-run não deve gravar acs');
    }

    public function test_comando_diagnostico_executa_e_exibe_relatorio(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        EsusPecIntegrationService::definirPdoTeste($pdo);

        Paciente::create([
            'id_externo_esus' => '1001',
            'nome' => 'Paciente Diagnostico',
            'cpf' => '11111111101',
            'cns' => '898001149470878',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'ubs_referencia' => null,
            'nome_acs' => null,
            'ativo' => true,
        ]);

        $this->artisan('esus:diagnostico')
            ->expectsOutputToContain('CER DIGITAL — DIAGNÓSTICO ESTRUTURAL E-SUS APS PEC (SOMENTE LEITURA)')
            ->expectsOutputToContain('tb_acomp_cidadaos_vinculados')
            ->expectsOutputToContain('tb_dim_unidade_saude')
            ->expectsOutputToContain('tb_fat_cad_individual')
            ->expectsOutputToContain('RECOMENDAÇÃO OPERACIONAL')
            ->assertSuccessful();
    }

    public function test_opcao_diagnostico_no_comando_sincronizar(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        EsusPecIntegrationService::definirPdoTeste($pdo);

        $this->artisan('esus:sincronizar', ['--diagnostico' => true])
            ->expectsOutputToContain('CER DIGITAL — DIAGNÓSTICO ESTRUTURAL E-SUS APS PEC (SOMENTE LEITURA)')
            ->assertSuccessful();
    }

    public function test_dry_run_absoluto_zero_gravacoes_em_pacientes_e_ubs(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        EsusPecIntegrationService::definirPdoTeste($pdo);

        Paciente::create([
            'id_externo_esus' => '1001',
            'nome' => 'Paciente Para Dry Run',
            'cpf' => '11111111101',
            'cns' => '898001149470878',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'ubs_referencia' => null,
            'ubs_id' => null,
            'nome_acs' => null,
            'ativo' => true,
        ]);

        $contagemPacientesAntes = Paciente::count();
        $contagemUbsAntes = UnidadeBasicaSaude::count();
        $configAntes = ConfiguracaoEsusPec::atual()->replicate();

        $this->artisan('esus:sincronizar', [
            '--apenas-vinculos' => true,
            '--dry-run' => true,
            '--reiniciar' => true,
        ])
            ->expectsOutputToContain('[MODO SIMULAÇÃO CONCLUÍDO]')
            ->assertSuccessful();

        $this->assertSame($contagemPacientesAntes, Paciente::count(), 'Dry-run não deve criar nem deletar pacientes.');
        $this->assertSame($contagemUbsAntes, UnidadeBasicaSaude::count(), 'Dry-run NÃO PODE criar nem modificar UBS no MySQL.');

        $paciente = Paciente::where('id_externo_esus', '1001')->first();
        $this->assertNull($paciente->ubs_referencia, 'Dry-run não deve alterar ubs_referencia');
        $this->assertNull($paciente->ubs_id, 'Dry-run não deve alterar ubs_id');
        $this->assertNull($paciente->nome_acs, 'Dry-run não deve alterar nome_acs');

        $configDepois = ConfiguracaoEsusPec::atual();
        $this->assertSame($configAntes->ultimo_checkpoint_id, $configDepois->ultimo_checkpoint_id, 'Dry-run não deve alterar checkpoints');
    }

    public function test_reconciliacao_ubs_id_quando_ubs_referencia_preenchida_sem_sobrescrever(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        EsusPecIntegrationService::definirPdoTeste($pdo);

        // Cria a UBS existente no banco local
        $ubsExistente = UnidadeBasicaSaude::create([
            'nome' => 'UBS CENTRAL DW',
            'cnes' => '2001',
            'municipio_ibge' => '270430',
            'origem' => 'MANUAL',
            'ativo' => true,
        ]);

        // Cria paciente que já possui ubs_referencia textual preenchida, mas ubs_id está NULL
        $paciente = Paciente::create([
            'id_externo_esus' => '1001',
            'nome' => 'Paciente Com Nome Textual de UBS',
            'cpf' => '11111111101',
            'cns' => '898001149470878',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'ubs_referencia' => 'UBS CENTRAL DW',
            'ubs_id' => null, // PENDENTE DE RECONCILIAÇÃO!
            'nome_acs' => null,
            'ativo' => true,
        ]);

        $this->artisan('esus:sincronizar', [
            '--apenas-vinculos' => true,
            '--force' => true,
            '--reiniciar' => true,
        ])->assertSuccessful();

        $paciente->refresh();
        $this->assertSame($ubsExistente->id, $paciente->ubs_id, 'O ubs_id deve ser preenchido pela reconciliação com a UBS existente.');
        $this->assertSame('UBS CENTRAL DW', $paciente->ubs_referencia, 'O ubs_referencia original deve ser estritamente preservado.');
        $this->assertSame('MARIA DA SILVA ACS', $paciente->nome_acs, 'O ACS deve ser complementado.');
    }

    public function test_nao_cria_ubs_com_cnes_invalido_ou_nome_inconclusivo(): void
    {
        $service = new EsusPecIntegrationService;

        $contagemUbsAntes = UnidadeBasicaSaude::count();

        // Nomes inconclusivos
        $this->assertTrue($service->nomeUbsInconclusivo('SEM UBS'));
        $this->assertTrue($service->nomeUbsInconclusivo('IGNORADO'));
        $this->assertTrue($service->nomeUbsInconclusivo('NÃO INFORMADO'));
        $this->assertTrue($service->nomeUbsInconclusivo('AB'));
        $this->assertTrue($service->nomeUbsInconclusivo('UBS CNES 12'));

        // Tenta resolver com nome inconclusivo e CNES inválido
        $ubs = $service->resolverOuCriarUbs('SEM UBS', '12', false, '270430');
        $this->assertNull($ubs, 'Não deve criar nem resolver UBS com nome inconclusivo e CNES inválido.');
        $this->assertSame($contagemUbsAntes, UnidadeBasicaSaude::count());
    }

    public function test_nao_vincula_ubs_homonimas_em_municipios_diferentes_sem_conferencia(): void
    {
        $service = new EsusPecIntegrationService;

        // Cria duas UBSs com mesmo nome em municípios distintos
        UnidadeBasicaSaude::create([
            'nome' => 'UBS SANTA RITA',
            'cnes' => '7111111',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        UnidadeBasicaSaude::create([
            'nome' => 'UBS SANTA RITA',
            'cnes' => '7222222',
            'municipio_ibge' => '270915',
            'ativo' => true,
        ]);

        // Tentativa de localização sem especificar município deve retornar NULL por ambiguidade
        $resultadoAmbiguo = $service->localizarUbsExistente('UBS SANTA RITA', null, null);
        $this->assertNull($resultadoAmbiguo, 'Não deve resolver quando existirem homônimas em municípios diferentes sem filtro.');

        // Com município inequívoco, deve resolver a do município correto
        $resultadoCorreto = $service->localizarUbsExistente('UBS SANTA RITA', null, '270430');
        $this->assertNotNull($resultadoCorreto);
        $this->assertSame('7111111', $resultadoCorreto->cnes);
    }

    public function test_reexecucao_idempotente_sem_duplicidades(): void
    {
        $pdo = $this->criarPdoPecCompleto();
        EsusPecIntegrationService::definirPdoTeste($pdo);

        Paciente::create([
            'id_externo_esus' => '1001',
            'nome' => 'Paciente Idempotente',
            'cpf' => '11111111101',
            'cns' => '898001149470878',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'ubs_referencia' => null,
            'ubs_id' => null,
            'nome_acs' => null,
            'ativo' => true,
        ]);

        // Primeira execução: complementa
        $this->artisan('esus:sincronizar', [
            '--apenas-vinculos' => true,
            '--force' => true,
            '--reiniciar' => true,
        ])->assertSuccessful();

        $totalPacientesAposPrimeira = Paciente::count();
        $totalUbsAposPrimeira = UnidadeBasicaSaude::count();

        // Segunda execução: tudo já complementado, nada deve mudar nem duplicar
        $this->artisan('esus:sincronizar', [
            '--apenas-vinculos' => true,
            '--force' => true,
            '--reiniciar' => true,
        ])
            ->expectsOutputToContain('Total de Pacientes Atualizados')
            ->assertSuccessful();

        $this->assertSame($totalPacientesAposPrimeira, Paciente::count(), 'Total de pacientes deve ser estritamente o mesmo.');
        $this->assertSame($totalUbsAposPrimeira, UnidadeBasicaSaude::count(), 'Total de UBS deve ser estritamente o mesmo.');
    }
}

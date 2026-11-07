<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SincronizarEsusPecJob;
use App\Livewire\ConfiguracoesSistema;
use App\Models\ConfiguracaoEsusPec;
use App\Models\HistoricoSincronizacaoEsus;
use App\Models\Paciente;
use App\Models\User;
use App\Rules\CnsValido;
use App\Services\EsusPecIntegrationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;
use Livewire\Livewire;
use PDO;
use Tests\TestCase;

class IntegracaoEsusPecTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_apenas_coordenacao_pode_acessar_configuracoes_esus_pec(): void
    {
        $recepcionista = User::factory()->create(['ativo' => true]);
        $recepcionista->assignRole('recepcao');

        $this->actingAs($recepcionista)
            ->get('/configuracoes/esus')
            ->assertForbidden();

        $terapeuta = User::factory()->create(['ativo' => true]);
        $terapeuta->assignRole('terapeuta');

        $this->actingAs($terapeuta)
            ->get('/configuracoes/esus')
            ->assertForbidden();

        $faturamento = User::factory()->create(['ativo' => true]);
        $faturamento->assignRole('faturamento');

        $this->actingAs($faturamento)
            ->get('/configuracoes/esus')
            ->assertForbidden();

        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $this->actingAs($coordenador)
            ->get('/configuracoes/esus')
            ->assertOk()
            ->assertSee('Integração e-SUS APS PEC')
            ->assertSee('Parâmetros de Conexão PostgreSQL');
    }

    public function test_credenciais_sao_armazenadas_criptografadas_e_nunca_exibidas_descriptografadas(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $senhaSecreta = 'SenhaSuperSecreta@PEC2026';

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'esus'])
            ->set('esus_ativo', true)
            ->set('esus_host', '192.168.10.50')
            ->set('esus_porta', 5433)
            ->set('esus_banco', 'esus')
            ->set('esus_usuario', 'esus_leitura')
            ->set('esus_senha', $senhaSecreta)
            ->set('esus_endereco_web', 'http://189.89.61.50:8181')
            ->call('salvarConfiguracaoEsus')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $config = ConfiguracaoEsusPec::atual();
        $this->assertTrue($config->ativo);
        $this->assertSame('192.168.10.50', $config->host);
        $this->assertSame(5433, $config->porta);
        $this->assertSame('esus_leitura', $config->usuario);
        $this->assertSame($senhaSecreta, $config->senha);

        // Ao renderizar a view novamente, a senha NÃO é retornada como texto plano no formulário
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'esus'])
            ->assertSet('esus_senha', '')
            ->assertSet('esus_tem_senha', true)
            ->assertDontSee($senhaSecreta);

        // Salvar com o campo de senha vazio preserva a credencial existente
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'esus'])
            ->set('esus_host', '192.168.10.60')
            ->set('esus_senha', '')
            ->call('salvarConfiguracaoEsus')
            ->assertHasNoErrors();

        $config->refresh();
        $this->assertSame('192.168.10.60', $config->host);
        $this->assertSame($senhaSecreta, $config->senha);
    }

    public function test_teste_conexao_falha_de_forma_segura_sem_expor_segredos(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'esus'])
            ->set('esus_host', '127.0.0.1')
            ->set('esus_porta', 5433)
            ->set('esus_banco', 'esus')
            ->set('esus_usuario', 'esus_leitura')
            ->set('esus_senha', 'senha_para_teste_timeout')
            ->call('testarConexaoEsus')
            ->assertDispatched('toast', function ($event, $params) {
                return $params['tipo'] === 'erro'
                    && ! str_contains($params['mensagem'], 'senha_para_teste_timeout');
            });

        $config = ConfiguracaoEsusPec::atual();
        $this->assertSame('ERRO', $config->ultimo_status_conexao);
        $this->assertNotEmpty($config->mensagem_status_conexao);
        $this->assertStringNotContainsString('senha_para_teste_timeout', $config->mensagem_status_conexao);
    }

    public function test_descoberta_de_schema_com_banco_simulado_dw_pec(): void
    {
        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdoSimulado->exec('
            CREATE TABLE tb_versao_sistema (
                co_seq_versao_sistema INTEGER PRIMARY KEY,
                nu_versao VARCHAR(20)
            );
            INSERT INTO tb_versao_sistema VALUES (1, "5.3.18");

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
                no_bairro_domicilio VARCHAR(100),
                co_ibge_domicilio VARCHAR(6),
                no_unidade_saude VARCHAR(255),
                no_profissional_acs VARCHAR(255)
            );
        ');

        $service = new EsusPecIntegrationService;
        $diagnostico = $service->descobrirSchema($pdoSimulado);

        $this->assertSame('DW_PEC', $diagnostico['tipo_banco']);
        $this->assertSame('tb_acomp_cidadaos_vinculados', $diagnostico['tabela_origem']);
        $this->assertTrue($diagnostico['compativel_importacao']);
        $this->assertSame('5.3.18', $diagnostico['versao_pec']);
        $this->assertContains('no_cidadao', $diagnostico['colunas_origem']);
        $this->assertContains('nu_cpf_cidadao', $diagnostico['colunas_origem']);
        $this->assertContains('nu_cns_cidadao', $diagnostico['colunas_origem']);
        $this->assertContains('dt_nascimento_cidadao', $diagnostico['colunas_origem']);
    }

    public function test_descoberta_de_schema_identifica_pec_operacional_e_interrompe_importacao(): void
    {
        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdoSimulado->exec('
            CREATE TABLE tb_cidadao (
                co_seq_cidadao INTEGER PRIMARY KEY,
                no_cidadao VARCHAR(255)
            );
        ');

        $service = new EsusPecIntegrationService;
        $diagnostico = $service->descobrirSchema($pdoSimulado);

        $this->assertSame('PEC_OPERACIONAL', $diagnostico['tipo_banco']);
        $this->assertFalse($diagnostico['compativel_importacao']);

        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame('INTERROMPIDO', $historico->status);
        $this->assertStringContainsString('tb_acomp_cidadaos_vinculados', $historico->mensagem_erro);
        $this->assertSame(0, $historico->novos_pacientes);
    }

    public function test_importacao_de_paciente_novo_com_schema_real_pec_e_colunas_oficiais(): void
    {
        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $cns1 = CnsValido::gerarDefinitivo('10020030040');
        $cns2 = CnsValido::gerarDefinitivo('50060070080');

        $pdoSimulado->exec('
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
                nu_numero_domicilio VARCHAR(10),
                no_bairro_domicilio VARCHAR(100),
                co_ibge_domicilio VARCHAR(6),
                no_unidade_saude VARCHAR(255),
                no_profissional_acs VARCHAR(255)
            );
        ');

        $stmt = $pdoSimulado->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (1, 9001, "Lucas Vinicius de Oliveira", "11122233344", ?, "2013-05-10", "MASCULINO", "Tereza Cristina", "(82) 99888-0001", "Rua A", "10", "Centro", "270430", "UBS Central", "ACS Carlos"),
            (2, 9002, "Mariana Fernanda Costa", "55566677788", ?, "2018-09-22", "FEMININO", "Silvia Costa", "(82) 99888-0002", "Rua B", "20", "Bela Vista", "270430", "UBS Bela Vista", "ACS Sonia");
        ');
        $stmt->execute([$cns1, $cns2]);

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame('SUCESSO', $historico->status);
        $this->assertSame(2, $historico->total_consultado);
        $this->assertSame(2, $historico->novos_pacientes);
        $this->assertSame(0, $historico->pacientes_atualizados);

        $this->assertDatabaseHas('pacientes', [
            'nome' => 'Lucas Vinicius de Oliveira',
            'cpf' => '11122233344',
            'cns' => $cns1,
            'sexo' => 'M',
            'origem_cadastro' => 'ESUS_APS',
            'id_externo_esus' => '9001',
            'endereco' => 'Rua A, 10',
            'bairro' => 'Centro',
            'ubs_referencia' => 'UBS Central',
            'nome_acs' => 'ACS Carlos',
        ]);

        $this->assertDatabaseHas('pacientes', [
            'nome' => 'Mariana Fernanda Costa',
            'cpf' => '55566677788',
            'cns' => $cns2,
            'sexo' => 'F',
            'origem_cadastro' => 'ESUS_APS',
            'id_externo_esus' => '9002',
            'endereco' => 'Rua B, 20',
            'bairro' => 'Bela Vista',
            'ubs_referencia' => 'UBS Bela Vista',
            'nome_acs' => 'ACS Sonia',
        ]);
    }

    public function test_atualizacao_de_paciente_existente_preservando_dados_preenchidos_e_clinicos(): void
    {
        $cnsValido = CnsValido::gerarDefinitivo('88899900011');

        $pacienteLocal = Paciente::create([
            'nome' => 'Arthur Gabriel Ramos',
            'cpf' => '99988877766',
            'cns' => $cnsValido,
            'data_nascimento' => '2015-02-14',
            'sexo' => 'M',
            'nome_mae' => 'Renata Ramos',
            'telefone_contato' => '(82) 99999-1234',
            'endereco' => 'Rua Local Preservada, 50',
            'municipio_ibge' => '270430',
            'cid_principal' => 'F840',
            'cid_secundario' => 'F900',
            'modalidades_reabilitacao' => ['INTELECTUAL'],
            'origem_cadastro' => 'MANUAL',
            'id_externo_esus' => null,
        ]);

        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdoSimulado->exec('
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
                no_bairro_domicilio VARCHAR(100),
                co_ibge_domicilio VARCHAR(6),
                no_unidade_saude VARCHAR(255),
                no_profissional_acs VARCHAR(255)
            );
        ');

        $stmt = $pdoSimulado->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (1, 9501, "Arthur Gabriel Ramos", "99988877766", ?, "2015-02-14", "M", "Renata Ramos", NULL, NULL, "Bairro Novo", "270430", "UBS Santo Antonio", "ACS Joao");
        ');
        $stmt->execute([$cnsValido]);

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame(1, $historico->pacientes_atualizados);
        $this->assertSame(0, $historico->novos_pacientes);

        $pacienteLocal->refresh();

        $this->assertSame('9501', $pacienteLocal->id_externo_esus);
        $this->assertSame('Bairro Novo', $pacienteLocal->bairro);
        $this->assertSame('UBS Santo Antonio', $pacienteLocal->ubs_referencia);
        $this->assertSame('ACS Joao', $pacienteLocal->nome_acs);
        // Não sobrescreveu campos preenchidos com valores nulos da origem
        $this->assertSame('(82) 99999-1234', $pacienteLocal->telefone_contato);
        $this->assertSame('Rua Local Preservada, 50', $pacienteLocal->endereco);
        // Preservou integralmente os dados clínicos
        $this->assertSame('F840', $pacienteLocal->cid_principal);
        $this->assertSame('F900', $pacienteLocal->cid_secundario);
        $this->assertSame(['INTELECTUAL'], $pacienteLocal->modalidades_reabilitacao);
    }

    public function test_identificacao_prioritaria_por_id_externo_estavel(): void
    {
        $pacienteLocal = Paciente::create([
            'nome' => 'Carlos Eduardo Lima',
            'cpf' => '12345678901',
            'cns' => null,
            'data_nascimento' => '2012-04-10',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'id_externo_esus' => '88801',
        ]);

        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdoSimulado->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE,
                no_sexo_cidadao VARCHAR(20),
                nu_telefone_celular VARCHAR(20)
            );
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (99, 88801, "Carlos Eduardo Lima", "12345678901", NULL, "2012-04-10", "M", "(82) 99111-2222");
        ');

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame(1, $historico->pacientes_atualizados);
        $this->assertSame(0, $historico->novos_pacientes);

        $pacienteLocal->refresh();
        $this->assertSame('(82) 99111-2222', $pacienteLocal->telefone_contato);
    }

    public function test_identificacao_por_cpf(): void
    {
        $pacienteLocal = Paciente::create([
            'nome' => 'Fabiana Menezes',
            'cpf' => '32165498700',
            'cns' => null,
            'data_nascimento' => '2014-08-15',
            'sexo' => 'F',
            'municipio_ibge' => '270430',
            'id_externo_esus' => null,
        ]);

        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdoSimulado->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE,
                no_sexo_cidadao VARCHAR(20)
            );
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (1, 55555, "Fabiana Menezes", "32165498700", NULL, "2014-08-15", "F");
        ');

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame(1, $historico->pacientes_atualizados);
        $this->assertSame(0, $historico->novos_pacientes);

        $pacienteLocal->refresh();
        $this->assertSame('55555', $pacienteLocal->id_externo_esus);
    }

    public function test_identificacao_por_cns(): void
    {
        $cns = CnsValido::gerarDefinitivo('77711122233');

        $pacienteLocal = Paciente::create([
            'nome' => 'Guilherme Santos',
            'cpf' => null,
            'cns' => $cns,
            'data_nascimento' => '2016-12-05',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'id_externo_esus' => null,
        ]);

        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdoSimulado->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE,
                no_sexo_cidadao VARCHAR(20)
            );
        ');

        $stmt = $pdoSimulado->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (1, 77777, "Guilherme Santos", NULL, ?, "2016-12-05", "M");
        ');
        $stmt->execute([$cns]);

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame(1, $historico->pacientes_atualizados);
        $this->assertSame(0, $historico->novos_pacientes);

        $pacienteLocal->refresh();
        $this->assertSame('77777', $pacienteLocal->id_externo_esus);
    }

    public function test_registro_sem_identificador_minimo_e_ignorado_e_contabilizado(): void
    {
        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Registro possui nome e data de nascimento, mas NÃO possui id_externo_esus, nem CPF, nem CNS
        $pdoSimulado->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE,
                no_sexo_cidadao VARCHAR(20)
            );
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (1, NULL, "Cidadao Sem Documento Algum", NULL, NULL, "2015-05-05", "M");
        ');

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        // Não deve criar paciente sem identificação mínima confiável
        $this->assertSame(0, $historico->novos_pacientes);
        $this->assertSame(1, $historico->total_consultado);
        $this->assertSame(1, $historico->registros_ignorados);
        $this->assertSame(1, $historico->sem_identificador);
        $this->assertDatabaseMissing('pacientes', ['nome' => 'Cidadao Sem Documento Algum']);
    }

    public function test_prevencao_de_duplicidade_e_conflito_cruzado(): void
    {
        $cnsValido1 = CnsValido::gerarDefinitivo('11122233344');
        $cnsValido2 = CnsValido::gerarDefinitivo('55566677788');

        Paciente::create([
            'nome' => 'Paciente A',
            'cpf' => '77788899900',
            'cns' => $cnsValido1,
            'data_nascimento' => '2010-01-01',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
        ]);
        Paciente::create([
            'nome' => 'Paciente B',
            'cpf' => '88899900011',
            'cns' => $cnsValido2,
            'data_nascimento' => '2012-02-02',
            'sexo' => 'F',
            'municipio_ibge' => '270430',
        ]);

        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdoSimulado->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE,
                no_sexo_cidadao VARCHAR(20)
            );
        ');

        $stmt = $pdoSimulado->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (801, NULL, "Cidadao Conflitante", "77788899900", ?, "2010-01-01", "M");
        ');
        $stmt->execute([$cnsValido2]);

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame(1, $historico->duplicidades_identificadas);
        $this->assertSame(0, $historico->novos_pacientes);
        $this->assertSame(0, $historico->pacientes_atualizados);
    }

    public function test_reexecucao_sem_duplicacao_e_contabilizacao_sem_alteracoes(): void
    {
        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $cns = CnsValido::gerarDefinitivo('33322211100');

        $pdoSimulado->exec('
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
                no_bairro_domicilio VARCHAR(100),
                co_ibge_domicilio VARCHAR(6),
                no_unidade_saude VARCHAR(255),
                no_profissional_acs VARCHAR(255)
            );
        ');

        $stmt = $pdoSimulado->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (1, 7001, "Danielle Marques Souza", "44433322211", ?, "2011-11-11", "F", "Marise Souza", "(82) 98888-7777", "Rua Teste, 1", "Centro", "270430", "UBS Centro", NULL);
        ');
        $stmt->execute([$cns]);

        $service = new EsusPecIntegrationService;

        // 1ª execução: cria novo paciente
        $h1 = $service->sincronizarPacientes('MANUAL', $pdoSimulado);
        $this->assertSame(1, $h1->novos_pacientes);
        $this->assertSame(0, $h1->pacientes_sem_alteracoes);
        $totalPacientes = Paciente::count();

        // 2ª execução com exatamente os mesmos dados: identifica registro idêntico sem alterações
        $h2 = $service->sincronizarPacientes('MANUAL', $pdoSimulado);
        $this->assertSame(0, $h2->novos_pacientes);
        $this->assertSame(0, $h2->pacientes_atualizados);
        $this->assertSame(1, $h2->pacientes_sem_alteracoes);
        $this->assertSame($totalPacientes, Paciente::count());
    }

    public function test_status_interrompido_quando_nenhum_paciente_e_persistido_em_consulta_nao_vazia(): void
    {
        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Insere 2 registros inválidos (sem nome ou data inválida)
        $pdoSimulado->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE
            );
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (1, 100, "", "11122233344", NULL, "2010-01-01"),
            (2, 101, "Nome Valido", "55566677788", NULL, "1850-01-01");
        ');

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        // Status NÃO deve ser SUCESSO quando nada foi importado/atualizado
        $this->assertSame('INTERROMPIDO', $historico->status);
        $this->assertSame(2, $historico->total_consultado);
        $this->assertSame(0, $historico->novos_pacientes);
        $this->assertSame(2, $historico->registros_invalidos);
        $this->assertNotEmpty($historico->mensagem_erro);
    }

    public function test_lote_com_multiplos_registros_e_performance_em_bloco(): void
    {
        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdoSimulado->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE,
                no_sexo_cidadao VARCHAR(20)
            );
        ');

        $stmt = $pdoSimulado->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados (co_seq_acomp_cidadaos_vinc, co_fat_cidadao_pec, no_cidadao, nu_cpf_cidadao, nu_cns_cidadao, dt_nascimento_cidadao, no_sexo_cidadao)
            VALUES (?, ?, ?, ?, ?, ?, "M")
        ');

        for ($i = 1; $i <= 10; $i++) {
            $cpf = str_pad((string) (10000000000 + $i), 11, '0', STR_PAD_LEFT);
            $cns = CnsValido::gerarDefinitivo(str_pad((string) (20000000000 + $i), 11, '0', STR_PAD_LEFT));
            $stmt->execute([$i, 1000 + $i, "Paciente Teste Lote {$i}", $cpf, $cns, '2010-01-01']);
        }

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame('SUCESSO', $historico->status);
        $this->assertSame(10, $historico->total_consultado);
        $this->assertSame(10, $historico->novos_pacientes);
        $this->assertSame(10, Paciente::count());
    }

    public function test_job_agendado_registrado_no_scheduler_as_02h(): void
    {
        $events = collect(Schedule::events());

        $jobEvent = $events->first(function ($event) {
            return str_contains($event->description ?? '', SincronizarEsusPecJob::class)
                || str_contains($event->command ?? '', SincronizarEsusPecJob::class);
        });

        $this->assertNotNull($jobEvent, 'O job SincronizarEsusPecJob deve estar registrado no agendador de tarefas.');
        $this->assertSame('0 2 * * *', $jobEvent->expression);
        $this->assertSame('America/Sao_Paulo', $jobEvent->timezone);
    }

    public function test_contabilizacao_correta_de_todos_os_contadores(): void
    {
        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdoSimulado->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE,
                no_sexo_cidadao VARCHAR(20)
            );
        ');

        $cnsValido1 = CnsValido::gerarDefinitivo('11111111111');
        $cnsValido2 = CnsValido::gerarDefinitivo('22222222222');

        // Cria 1 paciente local existente para gerar 'atualizado'
        Paciente::create([
            'nome' => 'Paciente Existente Local',
            'cpf' => '11122233344',
            'cns' => null,
            'data_nascimento' => '2010-01-01',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'id_externo_esus' => null,
        ]);

        $stmt = $pdoSimulado->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (1, 1001, "Novo Paciente 1", "99988877711", ?, "2015-05-10", "M"),
            (2, 1002, "Paciente Existente Local", "11122233344", ?, "2010-01-01", "M"),
            (3, NULL, "Sem Identificador", NULL, NULL, "2014-04-04", "F"),
            (4, 1004, "", "88877766655", NULL, "2012-02-02", "M");
        ');
        $stmt->execute([$cnsValido1, $cnsValido2]);

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame(4, $historico->total_consultado);
        $this->assertSame(1, $historico->novos_pacientes);
        $this->assertSame(1, $historico->pacientes_atualizados);
        $this->assertSame(0, $historico->pacientes_sem_alteracoes);
        $this->assertSame(2, $historico->registros_ignorados);
        $this->assertSame(1, $historico->sem_identificador);
        $this->assertSame(1, $historico->registros_invalidos);
        $this->assertSame(2, $historico->registros_validos);
        $this->assertSame(0, $historico->falhas_gravacao);
    }

    public function test_livewire_sincronizacao_manual_com_progresso(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        ConfiguracaoEsusPec::atual()->update([
            'ativo' => true,
            'host' => 'localhost',
            'porta' => 5433,
            'banco' => 'esus',
            'usuario' => 'esus_leitura',
        ]);

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'esus'])
            ->call('sincronizarEsusAgora')
            ->assertDispatched('toast');

        $historico = HistoricoSincronizacaoEsus::latest('id')->first();
        $this->assertNotNull($historico);
    }

    public function test_diagnostico_schema_detecta_presenca_e_ausencia_de_coluna_atualizacao(): void
    {
        $service = new EsusPecIntegrationService;

        // Caso 1: Tabela DW padrão oficial sem coluna de atualização
        $pdoSemTimestamp = new PDO('sqlite::memory:');
        $pdoSemTimestamp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdoSemTimestamp->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                dt_nascimento_cidadao DATE
            );
        ');

        $diag1 = $service->descobrirSchema($pdoSemTimestamp);
        $this->assertTrue($diag1['compativel_importacao']);
        $this->assertFalse($diag1['suporta_incremental']);
        $this->assertNull($diag1['coluna_atualizacao']);
        $this->assertSame('co_seq_acomp_cidadaos_vinc', $diag1['coluna_sequencial']);
        $this->assertStringContainsString('Reconciliação Completa Otimizada via Keyset Pagination', $diag1['diagnostico_texto']);

        // Caso 2: Tabela com coluna de atualização cadastral adicionada (dt_atualizacao)
        $pdoComTimestamp = new PDO('sqlite::memory:');
        $pdoComTimestamp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdoComTimestamp->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                dt_nascimento_cidadao DATE,
                dt_atualizacao TIMESTAMP
            );
        ');

        $diag2 = $service->descobrirSchema($pdoComTimestamp);
        $this->assertTrue($diag2['compativel_importacao']);
        $this->assertTrue($diag2['suporta_incremental']);
        $this->assertSame('dt_atualizacao', $diag2['coluna_atualizacao']);
        $this->assertSame('co_seq_acomp_cidadaos_vinc', $diag2['coluna_sequencial']);
        $this->assertStringContainsString('Sincronização incremental ativa', $diag2['diagnostico_texto']);
    }

    public function test_sincronizacao_incremental_com_coluna_temporal_e_janela_seguranca(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE,
                dt_atualizacao TIMESTAMP
            );
        ');

        $cns1 = CnsValido::gerarDefinitivo('33333333333');
        $cns2 = CnsValido::gerarDefinitivo('44444444444');

        // Registro 1: atualizado há 5 dias (fora da janela de segurança de 24h a partir de ontem)
        // Registro 2: atualizado há 2 horas (dentro da janela)
        $stmt = $pdo->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (1, 2001, "Cidadao Antigo Fora Janela", "11122233301", ?, "1990-01-01", ?),
            (2, 2002, "Cidadao Recente Dentro Janela", "11122233302", ?, "1992-02-02", ?);
        ');
        $stmt->execute([
            $cns1,
            now()->subDays(5)->format('Y-m-d H:i:s'),
            $cns2,
            now()->subHours(2)->format('Y-m-d H:i:s'),
        ]);

        $config = ConfiguracaoEsusPec::atual();
        $config->update([
            'ativo' => true,
            'modo_sincronizacao' => 'INCREMENTAL',
            'janela_seguranca_minutos' => 1440, // 24 horas
            'ultimo_checkpoint_em' => now()->subDay(), // Checkpoint de 1 dia atrás
        ]);

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdo);

        $this->assertSame('SUCESSO', $historico->status);
        $this->assertSame('INCREMENTAL', $historico->detalhes['modo_executado']);
        $this->assertSame(1, $historico->total_consultado);
        $this->assertSame(1, $historico->novos_pacientes);

        $pacienteImportado = Paciente::where('id_externo_esus', '2002')->first();
        $this->assertNotNull($pacienteImportado);
        $this->assertSame('Cidadao Recente Dentro Janela', $pacienteImportado->nome);

        // O paciente com data anterior à janela não foi consultado
        $this->assertNull(Paciente::where('id_externo_esus', '2001')->first());

        // Confirma atualização do checkpoint
        $config->refresh();
        $this->assertNotNull($config->ultimo_checkpoint_em);
        $this->assertTrue($config->ultimo_checkpoint_em->isToday());
    }

    public function test_sincronizacao_sem_coluna_temporal_utiliza_reconciliacao_completa_e_keyset(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE
            );
        ');

        $cns1 = CnsValido::gerarDefinitivo('55555555551');
        $cns2 = CnsValido::gerarDefinitivo('55555555552');

        $stmt = $pdo->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (10, 3001, "Cidadao Keyset 1", "22233344401", ?, "1985-03-15"),
            (20, 3002, "Cidadao Keyset 2", "22233344402", ?, "1988-06-20");
        ');
        $stmt->execute([$cns1, $cns2]);

        $config = ConfiguracaoEsusPec::atual();
        $config->update([
            'ativo' => true,
            'modo_sincronizacao' => 'AUTO',
        ]);

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdo);

        $this->assertSame('SUCESSO', $historico->status);
        $this->assertSame('RECONCILIACAO_COMPLETA', $historico->detalhes['modo_executado']);
        $this->assertSame('KEYSET_PAGINATION', $historico->detalhes['paginacao']);
        $this->assertSame('co_seq_acomp_cidadaos_vinc', $historico->detalhes['coluna_sequencial']);
        $this->assertSame(2, $historico->total_consultado);
        $this->assertSame(2, $historico->novos_pacientes);
        $this->assertSame(20, $historico->detalhes['ultimo_id_processado']);
    }

    public function test_keyset_pagination_processa_lotes_corretamente_e_persiste_checkpoint_id(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE
            );
        ');

        // Insere 12 registros para processamento em lotes de 5 (deverá processar 3 páginas)
        for ($i = 1; $i <= 12; $i++) {
            $cpf = sprintf('333444%05d', $i);
            $cns = CnsValido::gerarDefinitivo(sprintf('6666%07d', $i));
            $nome = "Cidadao Lote {$i}";
            $stmt = $pdo->prepare('INSERT INTO tb_acomp_cidadaos_vinculados VALUES (?, ?, ?, ?, ?, "1995-01-01");');
            $stmt->execute([$i * 10, 4000 + $i, $nome, $cpf, $cns]);
        }

        $config = ConfiguracaoEsusPec::atual();
        $config->update([
            'ativo' => true,
            'tamanho_lote' => 5,
        ]);

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdo);

        $this->assertSame('SUCESSO', $historico->status);
        $this->assertSame(12, $historico->total_consultado);
        $this->assertSame(12, $historico->novos_pacientes);
        $this->assertSame(120, $historico->detalhes['ultimo_id_processado']);

        // Checkpoint do último ID deve ser exatamente 120
        $config->refresh();
        $this->assertSame(120, $config->ultimo_checkpoint_id);
    }

    public function test_metricas_de_tempo_consulta_processamento_e_gravacao_sao_registradas_separadamente(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE
            );
        ');

        $cns = CnsValido::gerarDefinitivo('77777777777');
        $stmt = $pdo->prepare('INSERT INTO tb_acomp_cidadaos_vinculados VALUES (1, 5001, "Cidadao Metricas", "44455566601", ?, "1990-05-15");');
        $stmt->execute([$cns]);

        $config = ConfiguracaoEsusPec::atual();
        $config->update(['ativo' => true]);

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdo);

        $this->assertArrayHasKey('duracao_consulta_ms', $historico->detalhes);
        $this->assertArrayHasKey('duracao_processamento_ms', $historico->detalhes);
        $this->assertArrayHasKey('duracao_gravacao_ms', $historico->detalhes);
        $this->assertArrayHasKey('duracao_total_ms', $historico->detalhes);

        $this->assertGreaterThanOrEqual(0, $historico->duracao_consulta_ms);
        $this->assertGreaterThanOrEqual(0, $historico->duracao_gravacao_ms);
        $this->assertGreaterThanOrEqual(0, $historico->duracao_processamento_ms);

        $this->assertIsString($historico->duracao_consulta_formatada);
        $this->assertIsString($historico->duracao_gravacao_formatada);
        $this->assertIsString($historico->duracao_processamento_formatada);
    }

    public function test_livewire_salva_configuracao_de_modo_e_tamanho_lote(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'esus'])
            ->set('esus_ativo', true)
            ->set('esus_host', '10.0.0.100')
            ->set('esus_porta', 5433)
            ->set('esus_banco', 'esus')
            ->set('esus_usuario', 'esus_leitura')
            ->set('esus_modo_sincronizacao', 'INCREMENTAL')
            ->set('esus_tamanho_lote', 1000)
            ->call('salvarConfiguracaoEsus')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $config = ConfiguracaoEsusPec::atual();
        $this->assertSame('INCREMENTAL', $config->modo_sincronizacao);
        $this->assertSame(1000, $config->tamanho_lote);
    }

    public function test_forcar_carga_completa_executa_reconciliacao_integral(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        ConfiguracaoEsusPec::atual()->update([
            'ativo' => true,
            'modo_sincronizacao' => 'INCREMENTAL',
            'ultimo_checkpoint_em' => now()->subDay(),
        ]);

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'esus'])
            ->call('sincronizarEsusAgora', 'COMPLETA')
            ->assertDispatched('toast');

        $historico = HistoricoSincronizacaoEsus::latest('id')->first();
        $this->assertNotNull($historico);
        $this->assertSame('COMPLETA', $historico->detalhes['modo_executado']);
    }
}

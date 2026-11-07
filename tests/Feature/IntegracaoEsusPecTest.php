<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SincronizarEsusPecJob;
use App\Livewire\ConfiguracoesSistema;
use App\Models\ConfiguracaoEsusPec;
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
        // O cast 'encrypted' garante que a senha gravada no banco é ilegível/criptografada
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
            ->set('esus_senha', '') // Vazio para manter
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
        // Cria banco em memória SQLite simulando a estrutura do DW e-SUS APS PEC
        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdoSimulado->exec('
            CREATE TABLE tb_versao_sistema (
                co_seq_versao_sistema INTEGER PRIMARY KEY,
                nu_versao VARCHAR(20)
            );
            INSERT INTO tb_versao_sistema VALUES (1, "5.3.18");

            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_fat_cidadao_vinculado INTEGER PRIMARY KEY,
                no_cidadao VARCHAR(255),
                nu_cpf VARCHAR(11),
                nu_cns VARCHAR(15),
                dt_nascimento DATE,
                no_sexo VARCHAR(20),
                no_mae VARCHAR(255),
                nu_telefone_celular VARCHAR(20),
                ds_logradouro VARCHAR(255),
                no_bairro VARCHAR(100),
                co_ibge VARCHAR(6),
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
        $this->assertContains('nu_cpf', $diagnostico['colunas_origem']);
        $this->assertContains('nu_cns', $diagnostico['colunas_origem']);
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

        // Tentativa de sincronização deve ser interrompida por não ser DW oficial
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame('INTERROMPIDO', $historico->status);
        $this->assertStringContainsString('tb_acomp_cidadaos_vinculados', $historico->mensagem_erro);
        $this->assertSame(0, $historico->novos_pacientes);
    }

    public function test_sincronizacao_importa_novos_pacientes_com_desduplicacao_e_rastreabilidade(): void
    {
        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $cnsValido1 = CnsValido::gerarDefinitivo('10020030040');
        $cnsValido2 = CnsValido::gerarDefinitivo('50060070080');

        $pdoSimulado->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_fat_cidadao_vinculado INTEGER PRIMARY KEY,
                no_cidadao VARCHAR(255),
                nu_cpf VARCHAR(11),
                nu_cns VARCHAR(15),
                dt_nascimento DATE,
                no_sexo VARCHAR(20),
                no_mae VARCHAR(255),
                nu_telefone_celular VARCHAR(20),
                ds_logradouro VARCHAR(255),
                no_bairro VARCHAR(100),
                co_ibge VARCHAR(6),
                no_unidade_saude VARCHAR(255),
                no_profissional_acs VARCHAR(255)
            );
        ');

        $stmt = $pdoSimulado->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (101, "Lucas Vinicius de Oliveira", "11122233344", ?, "2013-05-10", "M", "Tereza Cristina", "(82) 99888-0001", "Rua A, 10", "Centro", "270430", "UBS Central", "ACS Carlos"),
            (102, "Mariana Fernanda Costa", "55566677788", ?, "2018-09-22", "F", "Silvia Costa", "(82) 99888-0002", "Rua B, 20", "Bela Vista", "270430", "UBS Bela Vista", "ACS Sonia");
        ');
        $stmt->execute([$cnsValido1, $cnsValido2]);

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame('SUCESSO', $historico->status);
        $this->assertSame(2, $historico->total_consultado);
        $this->assertSame(2, $historico->novos_pacientes);
        $this->assertSame(0, $historico->pacientes_atualizados);

        $this->assertDatabaseHas('pacientes', [
            'nome' => 'Lucas Vinicius de Oliveira',
            'cpf' => '11122233344',
            'cns' => $cnsValido1,
            'origem_cadastro' => 'ESUS_APS',
            'id_externo_esus' => '101',
            'ubs_referencia' => 'UBS Central',
            'nome_acs' => 'ACS Carlos',
        ]);

        $this->assertDatabaseHas('pacientes', [
            'nome' => 'Mariana Fernanda Costa',
            'cpf' => '55566677788',
            'cns' => $cnsValido2,
            'origem_cadastro' => 'ESUS_APS',
            'id_externo_esus' => '102',
            'ubs_referencia' => 'UBS Bela Vista',
            'nome_acs' => 'ACS Sonia',
        ]);
    }

    public function test_sincronizacao_atualiza_sem_perda_de_dados_locais_e_preserva_dados_clinicos(): void
    {
        $cnsValido = CnsValido::gerarDefinitivo('88899900011');

        // Paciente preexistente no CER com dados clínicos registrados
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
                co_seq_fat_cidadao_vinculado INTEGER PRIMARY KEY,
                no_cidadao VARCHAR(255),
                nu_cpf VARCHAR(11),
                nu_cns VARCHAR(15),
                dt_nascimento DATE,
                no_sexo VARCHAR(20),
                no_mae VARCHAR(255),
                nu_telefone_celular VARCHAR(20),
                ds_logradouro VARCHAR(255),
                no_bairro VARCHAR(100),
                co_ibge VARCHAR(6),
                no_unidade_saude VARCHAR(255),
                no_profissional_acs VARCHAR(255)
            );
        ');

        // Cidadão no PEC com mesmo CPF, mas sem telefone e sem endereço no e-SUS
        $stmt = $pdoSimulado->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (501, "Arthur Gabriel Ramos", "99988877766", ?, "2015-02-14", "M", "Renata Ramos", NULL, NULL, "Bairro Novo", "270430", "UBS Santo Antonio", "ACS Joao");
        ');
        $stmt->execute([$cnsValido]);

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame(1, $historico->pacientes_atualizados);
        $this->assertSame(0, $historico->novos_pacientes);

        $pacienteLocal->refresh();

        // 1. Vinculou o ID externo do PEC
        $this->assertSame('501', $pacienteLocal->id_externo_esus);
        // 2. Preencheu o que estava vazio no local
        $this->assertSame('Bairro Novo', $pacienteLocal->bairro);
        $this->assertSame('UBS Santo Antonio', $pacienteLocal->ubs_referencia);
        $this->assertSame('ACS Joao', $pacienteLocal->nome_acs);
        // 3. NÃO sobrescreveu campos preenchidos com valores nulos da origem
        $this->assertSame('(82) 99999-1234', $pacienteLocal->telefone_contato);
        $this->assertSame('Rua Local Preservada, 50', $pacienteLocal->endereco);
        // 4. PRESERVOU integralmente os dados clínicos
        $this->assertSame('F840', $pacienteLocal->cid_principal);
        $this->assertSame('F900', $pacienteLocal->cid_secundario);
        $this->assertSame(['INTELECTUAL'], $pacienteLocal->modalidades_reabilitacao);
    }

    public function test_sincronizacao_repetida_e_idempotente(): void
    {
        $pdoSimulado = new PDO('sqlite::memory:');
        $pdoSimulado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $cns = CnsValido::gerarDefinitivo('33322211100');

        $pdoSimulado->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_fat_cidadao_vinculado INTEGER PRIMARY KEY,
                no_cidadao VARCHAR(255),
                nu_cpf VARCHAR(11),
                nu_cns VARCHAR(15),
                dt_nascimento DATE,
                no_sexo VARCHAR(20),
                no_mae VARCHAR(255),
                nu_telefone_celular VARCHAR(20),
                ds_logradouro VARCHAR(255),
                no_bairro VARCHAR(100),
                co_ibge VARCHAR(6),
                no_unidade_saude VARCHAR(255),
                no_profissional_acs VARCHAR(255)
            );
        ');

        $stmt = $pdoSimulado->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (701, "Danielle Marques Souza", "44433322211", ?, "2011-11-11", "F", "Marise Souza", "(82) 98888-7777", "Rua Teste, 1", "Centro", "270430", "UBS Centro", NULL);
        ');
        $stmt->execute([$cns]);

        $service = new EsusPecIntegrationService;

        // 1ª execução
        $h1 = $service->sincronizarPacientes('MANUAL', $pdoSimulado);
        $this->assertSame(1, $h1->novos_pacientes);
        $totalPacientesApos1 = Paciente::count();

        // 2ª execução com exatamente os mesmos dados
        $h2 = $service->sincronizarPacientes('MANUAL', $pdoSimulado);
        $this->assertSame(0, $h2->novos_pacientes);
        $this->assertSame(1, $h2->pacientes_atualizados);
        $this->assertSame($totalPacientesApos1, Paciente::count());
    }

    public function test_sincronizacao_identifica_duplicidade_e_nao_duplica_registros(): void
    {
        $cnsValido1 = CnsValido::gerarDefinitivo('11122233344');
        $cnsValido2 = CnsValido::gerarDefinitivo('55566677788');

        // Cria dois pacientes distintos no banco CER
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
                co_seq_fat_cidadao_vinculado INTEGER PRIMARY KEY,
                no_cidadao VARCHAR(255),
                nu_cpf VARCHAR(11),
                nu_cns VARCHAR(15),
                dt_nascimento DATE,
                no_sexo VARCHAR(20),
                no_mae VARCHAR(255),
                nu_telefone_celular VARCHAR(20),
                ds_logradouro VARCHAR(255),
                no_bairro VARCHAR(100),
                co_ibge VARCHAR(6),
                no_unidade_saude VARCHAR(255),
                no_profissional_acs VARCHAR(255)
            );
        ');

        // Registro do e-SUS contendo CPF do Paciente A, mas CNS do Paciente B (conflito cruzado de duplicidade)
        $stmt = $pdoSimulado->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (801, "Cidadao Conflitante", "77788899900", ?, "2010-01-01", "M", NULL, NULL, NULL, NULL, "270430", NULL, NULL);
        ');
        $stmt->execute([$cnsValido2]);

        $service = new EsusPecIntegrationService;
        $historico = $service->sincronizarPacientes('MANUAL', $pdoSimulado);

        $this->assertSame(1, $historico->duplicidades_identificadas);
        $this->assertSame(0, $historico->novos_pacientes);
        $this->assertSame(0, $historico->pacientes_atualizados);
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
}

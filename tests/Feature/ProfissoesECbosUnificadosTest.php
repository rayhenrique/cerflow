<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
use App\Models\Cbo;
use App\Models\LogAuditoria;
use App\Models\ProcedimentoSigtap;
use App\Models\ProducaoBpai;
use App\Models\Profissional;
use App\Models\User;
use App\Rules\CnsValido;
use Database\Seeders\DadosFicticiosLocaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfissoesECbosUnificadosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DadosFicticiosLocaisSeeder::class);
    }

    public function test_novo_profissional_pode_ser_cadastrado_apenas_com_cbo_sem_especialidade(): void
    {
        $coordenacao = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();

        $cboFono = Cbo::firstOrCreate(
            ['codigo' => '223810'],
            [
                'titulo_oficial' => 'Fonoaudiólogo geral',
                'ativo' => true,
                'fonte_referencia' => 'Ministério do Trabalho e Emprego (MTE)',
                'verificado_em' => now(),
            ]
        );

        $cnsValido = CnsValido::gerarDefinitivo('11144477733');

        Livewire::actingAs($coordenacao)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('abrirModalNovoProfissional')
            ->set('usr_name', 'Novo Terapeuta CBO Puro')
            ->set('usr_email', 'terapeuta.cbo@cerflow.gov.br')
            ->set('usr_password', 'senha123')
            ->set('usr_role', 'terapeuta')
            ->set('vincular_profissional', true)
            ->set('prof_cpf', '12345678901')
            ->set('prof_cns', $cnsValido)
            ->set('prof_conselho', 'CRFa 9999-RJ')
            ->set('prof_cbo_id', $cboFono->id)
            ->set('prof_cbo', '223810')
            ->set('prof_pin', '123456')
            ->call('salvarUsuarioProfissional')
            ->assertHasNoErrors();

        $novoUser = User::where('email', 'terapeuta.cbo@cerflow.gov.br')->first();
        $this->assertNotNull($novoUser);
        $this->assertNotNull($novoUser->profissional);

        $prof = $novoUser->profissional;
        $this->assertSame($cboFono->id, $prof->cbo_id);
        $this->assertSame('223810', $prof->cbo_padrao);
        $this->assertNull($prof->especialidade);
        $this->assertSame('Fonoaudiólogo geral', $prof->profissao_ou_especialidade);
    }

    public function test_edicao_de_profissional_antigo_preserva_especialidade_historica(): void
    {
        $coordenacao = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();

        $userAntigo = User::factory()->create();
        $cboFisio = Cbo::firstOrCreate(
            ['codigo' => '223605'],
            ['titulo_oficial' => 'Fisioterapeuta geral', 'ativo' => true]
        );

        $cnsValido = CnsValido::gerarDefinitivo('22255588844');

        $profAntigo = Profissional::create([
            'user_id' => $userAntigo->id,
            'nome' => 'Dra. Fisioterapia Histórica',
            'cpf' => '98765432100',
            'cns' => $cnsValido,
            'registro_conselho' => 'CREFITO 1111-F',
            'cbo_id' => $cboFisio->id,
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia Respiratória Avançada',
            'pin_assinatura_hash' => bcrypt('123456'),
            'ativo' => true,
        ]);

        Livewire::actingAs($coordenacao)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('editarUsuarioProfissional', $userAntigo->id)
            ->set('usr_name', 'Dra. Fisioterapia Histórica Atualizada')
            ->call('salvarUsuarioProfissional')
            ->assertHasNoErrors();

        $profAntigo->refresh();
        $this->assertSame('Dra. Fisioterapia Histórica Atualizada', $profAntigo->nome);
        $this->assertSame('Fisioterapia Respiratória Avançada', $profAntigo->especialidade);
        $this->assertSame('Fisioterapia Respiratória Avançada', $profAntigo->profissao_ou_especialidade);
    }

    public function test_cadastro_e_pesquisa_de_neuropsicologo_com_cbo_251545(): void
    {
        $coordenacao = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();

        $cboNeuro = Cbo::firstOrCreate(
            ['codigo' => '251545'],
            [
                'titulo_oficial' => 'Neuropsicólogo',
                'ativo' => true,
                'fonte_referencia' => 'Ministério do Trabalho e Emprego (MTE)',
                'verificado_em' => now(),
            ]
        );

        $cnsValido = CnsValido::gerarDefinitivo('33366699911');

        Livewire::actingAs($coordenacao)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('abrirModalNovoProfissional')
            ->set('buscaCboModal', 'Neuropsicólogo')
            ->assertSee('251545')
            ->set('prof_cbo_id', $cboNeuro->id)
            ->set('usr_name', 'Psicólogo Especialista em Neuro')
            ->set('usr_email', 'neuro.psico@cerflow.gov.br')
            ->set('usr_password', 'senha123')
            ->set('usr_role', 'terapeuta')
            ->set('prof_cpf', '55566677788')
            ->set('prof_cns', $cnsValido)
            ->set('prof_conselho', 'CRP 06/99999')
            ->set('prof_pin', '123456')
            ->call('salvarUsuarioProfissional')
            ->assertHasNoErrors();

        $novoUser = User::where('email', 'neuro.psico@cerflow.gov.br')->firstOrFail();
        $this->assertSame('251545', $novoUser->profissional->cbo_padrao);
        $this->assertSame('Neuropsicólogo', $novoUser->profissional->profissao_ou_especialidade);
    }

    public function test_modulo_profissoes_e_cbos_permite_cadastro_edicao_e_inativacao_por_coordenacao(): void
    {
        $coordenacao = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();

        Livewire::actingAs($coordenacao)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'cbos')
            ->call('abrirModalNovoCbo')
            ->set('cbo_codigo', '223905')
            ->set('cbo_titulo_oficial', 'Terapeuta ocupacional')
            ->set('cbo_fonte_referencia', 'MTE Teste')
            ->set('cbo_ativo', true)
            ->set('cbo_verificado', true)
            ->call('salvarCbo')
            ->assertHasNoErrors();

        $cbo = Cbo::where('codigo', '223905')->firstOrFail();
        $this->assertSame('Terapeuta ocupacional', $cbo->titulo_oficial);
        $this->assertTrue($cbo->ativo);
        $this->assertTrue($cbo->estaVerificado());

        // Inativação
        Livewire::actingAs($coordenacao)
            ->test(ConfiguracoesSistema::class)
            ->call('alternarStatusCbo', $cbo->id);

        $cbo->refresh();
        $this->assertFalse($cbo->ativo);

        // Verifica registro de auditoria
        $this->assertTrue(
            LogAuditoria::where('tabela_afetada', 'cbos')
                ->where('registro_id', $cbo->id)
                ->exists()
        );
    }

    public function test_perfil_sem_autorizacao_nao_pode_gerenciar_cbos(): void
    {
        $fisioterapeuta = User::where('email', 'fisioterapeuta@cerflow.gov.br')->firstOrFail();

        Livewire::actingAs($fisioterapeuta)
            ->test(ConfiguracoesSistema::class)
            ->call('abrirModalNovoCbo')
            ->assertForbidden();
    }

    public function test_protecao_estrita_do_bpai_e_matriz_sigtap_permanecem_inalteradas(): void
    {
        // Cria procedimento SIGTAP com CBOs específicos autorizados
        $proc = ProcedimentoSigtap::firstOrCreate(
            ['codigo' => '0301070016'],
            [
                'descricao' => 'Consulta de Profissionais de Nível Superior',
                'sexo_permitido' => 'AMBOS',
                'idade_minima' => 0,
                'idade_maxima' => 1200,
                'cbos_permitidos' => ['223605', '223810'],
                'ativo' => true,
            ]
        );

        // CBO 251545 (Neuropsicólogo) NÃO está em cbos_permitidos
        $this->assertTrue($proc->permiteCbo('223605'));
        $this->assertTrue($proc->permiteCbo('223810'));
        $this->assertFalse($proc->permiteCbo('251545'));

        // Importação de CBOs NÃO deve alterar os cbos_permitidos do procedimento
        $this->artisan('cer:importar-cbos --dry-run')->assertSuccessful();

        $proc->refresh();
        $this->assertSame(['223605', '223810'], $proc->cbos_permitidos);
        $this->assertFalse($proc->permiteCbo('251545'));

        // Registros históricos de produção BPA-i existentes permanecem inalterados
        $totalProducoes = ProducaoBpai::count();
        $this->artisan('cer:recuperar-cbos-legados --dry-run')->assertSuccessful();
        $this->assertSame($totalProducoes, ProducaoBpai::count());
    }
}

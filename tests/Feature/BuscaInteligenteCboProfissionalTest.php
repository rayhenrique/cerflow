<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
use App\Models\Cbo;
use App\Models\Profissional;
use App\Models\User;
use App\Rules\CnsValido;
use Database\Seeders\DadosFicticiosLocaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class BuscaInteligenteCboProfissionalTest extends TestCase
{
    use RefreshDatabase;

    private User $coordenador;

    private Cbo $cboPsico;

    private Cbo $cboNeuro;

    private Cbo $cboFono;

    private Cbo $cboInativo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DadosFicticiosLocaisSeeder::class);

        $this->coordenador = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();

        $this->cboPsico = Cbo::firstOrCreate(
            ['codigo' => '251510'],
            [
                'titulo_oficial' => 'Psicólogo Clínico',
                'ativo' => true,
                'fonte_referencia' => 'Ministério do Trabalho e Emprego (MTE)',
                'verificado_em' => now(),
            ]
        );

        $this->cboNeuro = Cbo::firstOrCreate(
            ['codigo' => '251545'],
            [
                'titulo_oficial' => 'Neuropsicólogo',
                'ativo' => true,
                'fonte_referencia' => 'Ministério do Trabalho e Emprego (MTE)',
                'verificado_em' => now(),
            ]
        );

        $this->cboFono = Cbo::firstOrCreate(
            ['codigo' => '223810'],
            [
                'titulo_oficial' => 'Fonoaudiólogo geral',
                'ativo' => true,
                'fonte_referencia' => 'Ministério do Trabalho e Emprego (MTE)',
                'verificado_em' => now(),
            ]
        );

        $this->cboInativo = Cbo::firstOrCreate(
            ['codigo' => '999999'],
            [
                'titulo_oficial' => 'Ocupação Desativada Teste',
                'ativo' => false,
                'fonte_referencia' => 'MTE',
                'verificado_em' => null,
            ]
        );
    }

    public function test_modal_novo_profissional_abre_sem_cbo_selecionado(): void
    {
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('abrirModalNovoProfissional')
            ->assertSet('modalProfissionalAberto', true)
            ->assertSet('prof_cbo_id', null)
            ->assertSet('prof_cbo', '')
            ->assertSet('buscaCboModal', '')
            ->assertSeeHtml('placeholder="Digite o nome da profissão ou código CBO..."')
            ->assertDontSeeHtml('selected="selected"')
            ->assertDontSeeHtml('value="223605"');
    }

    public function test_pesquisa_por_codigo_completo_e_parcial_e_por_nome(): void
    {
        // 1. Código completo
        $resCompleto = Cbo::buscarParaAutocomplete('251545', 10);
        $this->assertTrue($resCompleto->contains('codigo', '251545'));

        // 2. Código parcial
        $resParcial = Cbo::buscarParaAutocomplete('2515', 10);
        $this->assertTrue($resParcial->contains('codigo', '251545'));
        $this->assertTrue($resParcial->contains('codigo', '251510'));

        // 3. Nome parcial (case-insensitive)
        $resNome = Cbo::buscarParaAutocomplete('Neuro', 10);
        $this->assertTrue($resNome->contains('codigo', '251545'));

        $resLower = Cbo::buscarParaAutocomplete('neuropsicólogo', 10);
        $this->assertTrue($resLower->contains('codigo', '251545'));

        $resUpper = Cbo::buscarParaAutocomplete('FONOAUDIÓLOGO', 10);
        $this->assertTrue($resUpper->contains('codigo', '223810'));
    }

    public function test_pesquisa_com_menos_de_2_caracteres_retorna_vazio(): void
    {
        $this->assertCount(0, Cbo::buscarParaAutocomplete('2', 10));
        $this->assertCount(0, Cbo::buscarParaAutocomplete('N', 10));
        $this->assertCount(0, Cbo::buscarParaAutocomplete('', 10));
    }

    public function test_ordenacao_por_relevancia(): void
    {
        // Quando busca '251510', o código exato '251510' deve ser o primeiro item
        $resultados = Cbo::buscarParaAutocomplete('251510', 10);
        $this->assertSame('251510', $resultados->first()->codigo);

        // Quando busca '2515', ambos começam com 2515 e devem vir antes de outros
        $resultados2515 = Cbo::buscarParaAutocomplete('2515', 10);
        $codigos = $resultados2515->pluck('codigo')->all();
        $this->assertContains('251510', $codigos);
        $this->assertContains('251545', $codigos);
    }

    public function test_selecao_de_neuropsicologo_251545(): void
    {
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('abrirModalNovoProfissional')
            ->set('buscaCboModal', '2515')
            ->assertSeeHtml('251545')
            ->assertSeeHtml('Neuropsicólogo')
            ->call('selecionarCbo', $this->cboNeuro->id)
            ->assertSet('prof_cbo_id', $this->cboNeuro->id)
            ->assertSet('prof_cbo', '251545')
            ->assertSet('buscaCboModal', '251545 — Neuropsicólogo')
            ->assertHasNoErrors(['prof_cbo_id', 'prof_cbo']);
    }

    public function test_nenhum_resultado_encontrado_exibe_mensagem(): void
    {
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('abrirModalNovoProfissional')
            ->set('buscaCboModal', 'XYZ999Inexistente')
            ->assertSeeHtml('Nenhum resultado encontrado para')
            ->assertSeeHtml('XYZ999Inexistente');
    }

    public function test_digitar_apos_selecionar_invalida_selecao_anterior(): void
    {
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('abrirModalNovoProfissional')
            ->call('selecionarCbo', $this->cboNeuro->id)
            ->assertSet('prof_cbo_id', $this->cboNeuro->id)
            ->assertSet('prof_cbo', '251545')
            // Usuário apaga ou altera texto
            ->set('buscaCboModal', '251545 — Neuropsicólogo alterado')
            ->assertSet('prof_cbo_id', null)
            ->assertSet('prof_cbo', '');
    }

    public function test_nao_permite_salvar_sem_cbo_valido(): void
    {
        $cnsValido = CnsValido::gerarDefinitivo('88899911122');

        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('abrirModalNovoProfissional')
            ->set('usr_name', 'Profissional Sem CBO')
            ->set('usr_email', 'semcbo@cerflow.gov.br')
            ->set('usr_password', 'senha123')
            ->set('usr_role', 'terapeuta')
            ->set('vincular_profissional', true)
            ->set('prof_cpf', '88899911122')
            ->set('prof_cns', $cnsValido)
            ->set('prof_conselho', 'CRP 06/111')
            ->set('prof_pin', '123456')
            // Usuário digitou no campo mas NÃO selecionou uma opção
            ->set('buscaCboModal', 'Psicólogo')
            ->call('salvarUsuarioProfissional')
            ->assertHasErrors(['prof_cbo_id' => 'required']);

        $this->assertDatabaseMissing('users', ['email' => 'semcbo@cerflow.gov.br']);
    }

    public function test_nao_aceita_cbo_inativo_para_novos_vinculos(): void
    {
        $cnsValido = CnsValido::gerarDefinitivo('88899911133');

        // Tentar selecionar CBO inativo deve adicionar erro
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('abrirModalNovoProfissional')
            ->call('selecionarCbo', $this->cboInativo->id)
            ->assertHasErrors(['prof_cbo_id'])
            ->assertSet('prof_cbo_id', null);

        // Tentar forçar prof_cbo_id inativo no salvamento deve ser rejeitado pela validação
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('abrirModalNovoProfissional')
            ->set('usr_name', 'Tentativa Inativo')
            ->set('usr_email', 'inativo@cerflow.gov.br')
            ->set('usr_password', 'senha123')
            ->set('usr_role', 'terapeuta')
            ->set('vincular_profissional', true)
            ->set('prof_cpf', '88899911133')
            ->set('prof_cns', $cnsValido)
            ->set('prof_conselho', 'CRP 06/222')
            ->set('prof_pin', '123456')
            ->set('prof_cbo_id', $this->cboInativo->id)
            ->call('salvarUsuarioProfissional')
            ->assertHasErrors(['prof_cbo_id']);

        $this->assertDatabaseMissing('users', ['email' => 'inativo@cerflow.gov.br']);
    }

    public function test_cadastro_completo_com_cbo_valido_salva_corretamente(): void
    {
        $cnsValido = CnsValido::gerarDefinitivo('88899911144');

        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('abrirModalNovoProfissional')
            ->set('usr_name', 'Dra. Luiza Neuropsicóloga')
            ->set('usr_email', 'luiza.neuro@cerflow.gov.br')
            ->set('usr_password', 'senha123')
            ->set('usr_role', 'terapeuta')
            ->set('vincular_profissional', true)
            ->set('prof_cpf', '88899911144')
            ->set('prof_cns', $cnsValido)
            ->set('prof_conselho', 'CRP 06/33333')
            ->set('prof_pin', '123456')
            ->call('selecionarCbo', $this->cboNeuro->id)
            ->call('salvarUsuarioProfissional')
            ->assertHasNoErrors()
            ->assertSet('modalProfissionalAberto', false);

        $usuario = User::where('email', 'luiza.neuro@cerflow.gov.br')->firstOrFail();
        $this->assertNotNull($usuario->profissional);
        $this->assertSame($this->cboNeuro->id, $usuario->profissional->cbo_id);
        $this->assertSame('251545', $usuario->profissional->cbo_padrao);
    }

    public function test_edicao_de_profissional_existente_carrega_cbo_e_preserva_vinculo(): void
    {
        $terapeutaUser = User::where('email', 'fisioterapeuta@cerflow.gov.br')->firstOrFail();
        $profissional = $terapeutaUser->profissional;
        $this->assertNotNull($profissional);

        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('editarUsuarioProfissional', $terapeutaUser->id)
            ->assertSet('modalProfissionalAberto', true)
            ->assertSet('prof_cbo', $profissional->cbo_padrao)
            ->assertSeeHtml($profissional->cbo_padrao);
    }

    public function test_edicao_de_profissional_legado_sem_cbo_id_preserva_cbo_padrao(): void
    {
        // Cria usuário legado com apenas cbo_padrao não conciliado
        $usuarioLegado = User::factory()->create(['email' => 'legado.antigo@cerflow.gov.br']);
        $usuarioLegado->assignRole('terapeuta');
        $cnsValido = CnsValido::gerarDefinitivo('88899911155');

        $profLegado = Profissional::create([
            'user_id' => $usuarioLegado->id,
            'nome' => 'Terapeuta Histórico',
            'cpf' => '88899911155',
            'cns' => $cnsValido,
            'registro_conselho' => 'CREFITO 999',
            'cbo_id' => null,
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia Geral',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);

        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('editarUsuarioProfissional', $usuarioLegado->id)
            ->assertSet('modalProfissionalAberto', true)
            ->assertSet('prof_cbo', '223605')
            // Salva sem mexer no CBO -> deve preservar
            ->call('salvarUsuarioProfissional')
            ->assertHasNoErrors();

        $this->assertSame('223605', $profLegado->fresh()->cbo_padrao);
    }

    public function test_performance_nao_executa_consulta_de_sugestoes_quando_modal_fechado(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais');

        $queries = DB::getQueryLog();
        $consultouCboAutocomplete = false;
        foreach ($queries as $q) {
            if (str_contains($q['query'], 'CASE') && str_contains($q['query'], 'titulo_oficial')) {
                $consultouCboAutocomplete = true;
                break;
            }
        }

        $this->assertFalse($consultouCboAutocomplete, 'Não deve consultar sugestões de CBO com o modal fechado.');
    }

    public function test_maximo_de_10_sugestoes_retornadas(): void
    {
        // Cria 15 CBOs com prefixo comum
        for ($i = 1; $i <= 15; $i++) {
            $codigo = sprintf('77%04d', $i);
            Cbo::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'titulo_oficial' => "Ocupação Teste Limite {$i}",
                    'ativo' => true,
                    'fonte_referencia' => 'MTE',
                    'verificado_em' => now(),
                ]
            );
        }

        $resultados = Cbo::buscarParaAutocomplete('77', 10);
        $this->assertCount(10, $resultados);
    }
}

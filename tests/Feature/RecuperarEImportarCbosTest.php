<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
use App\Models\Cbo;
use App\Models\Especialidade;
use App\Models\Profissional;
use App\Models\User;
use Database\Seeders\DadosFicticiosLocaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecuperarEImportarCbosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DadosFicticiosLocaisSeeder::class);
    }

    public function test_recuperacao_de_cbos_legados_com_dry_run_nao_persiste_alteracoes(): void
    {
        $user = User::factory()->create();

        // Cria profissional com CBO legado que ainda não existe no catálogo
        $prof = Profissional::create([
            'user_id' => $user->id,
            'nome' => 'Dr. Legado Simulação',
            'cpf' => '11122233344',
            'cns' => '711122233344556',
            'registro_conselho' => 'CRM 99999',
            'cbo_padrao' => '225125',
            'especialidade' => 'Clínica Geral Legada',
            'pin_assinatura_hash' => bcrypt('123456'),
            'ativo' => true,
        ]);

        $this->artisan('cer:recuperar-cbos-legados', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('MODO DE SIMULAÇÃO ATIVO (--dry-run)');

        // Em dry-run, o CBO e a Especialidade não devem ter sido criados
        $this->assertDatabaseMissing('cbos', [
            'codigo' => '225125',
        ]);
        $this->assertDatabaseMissing('especialidades', [
            'nome' => 'Clínica Geral Legada',
        ]);

        $prof->refresh();
        $this->assertNull($prof->cbo_id);
        $this->assertNull($prof->especialidade_id);
        $this->assertSame('225125', $prof->cbo_padrao);
        $this->assertSame('Clínica Geral Legada', $prof->especialidade);
    }

    public function test_recuperacao_de_cbos_legados_cria_registros_concilia_vinculos_e_preserva_strings_historicas(): void
    {
        $user = User::factory()->create();

        $prof = Profissional::create([
            'user_id' => $user->id,
            'nome' => 'Dra. Reabilitação Fono',
            'cpf' => '22233344455',
            'cns' => '722233344455667',
            'registro_conselho' => 'CRFa 8888',
            'cbo_padrao' => '223810',
            'especialidade' => 'Fonoaudiologia Hospitalar',
            'pin_assinatura_hash' => bcrypt('123456'),
            'ativo' => true,
        ]);

        $this->artisan('cer:recuperar-cbos-legados')
            ->assertSuccessful()
            ->expectsOutputToContain('Recuperação e conciliação de dados legados concluída com sucesso!');

        // Verifica criação do CBO com metadados de recuperação e verificado_em nulo
        $cbo = Cbo::where('codigo', '223810')->first();
        $this->assertNotNull($cbo);
        $this->assertSame('Fonoaudiólogo geral', $cbo->titulo_oficial);
        $this->assertSame('Recuperação Legada de Profissionais', $cbo->fonte_referencia);
        $this->assertNull($cbo->verificado_em);

        // Verifica criação da Especialidade
        $esp = Especialidade::where('nome', 'Fonoaudiologia Hospitalar')->first();
        $this->assertNotNull($esp);

        // Verifica vínculo no profissional
        $prof->refresh();
        $this->assertSame($cbo->id, $prof->cbo_id);
        $this->assertSame($esp->id, $prof->especialidade_id);

        // Strings históricas devem permanecer intactas
        $this->assertSame('223810', $prof->cbo_padrao);
        $this->assertSame('Fonoaudiologia Hospitalar', $prof->especialidade);

        // Verifica criação e vínculo obrigatório da Neuropsicologia ao CBO 251545
        $cboNeuro = Cbo::where('codigo', '251545')->first();
        $this->assertNotNull($cboNeuro);
        $this->assertTrue(
            $cboNeuro->especialidades()->where('nome', 'Neuropsicologia')->exists()
        );
    }

    public function test_recuperacao_de_cbos_legados_e_idempotente(): void
    {
        $user = User::factory()->create();

        Profissional::create([
            'user_id' => $user->id,
            'nome' => 'Dr. Teste Idempotência',
            'cpf' => '33344455566',
            'cns' => '733344455566778',
            'registro_conselho' => 'CREFITO 7777',
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia Neurofuncional',
            'pin_assinatura_hash' => bcrypt('123456'),
            'ativo' => true,
        ]);

        // Primeira execução
        $this->artisan('cer:recuperar-cbos-legados')->assertSuccessful();
        $cbosCount1 = Cbo::count();
        $espCount1 = Especialidade::count();

        // Segunda execução
        $this->artisan('cer:recuperar-cbos-legados')->assertSuccessful();
        $cbosCount2 = Cbo::count();
        $espCount2 = Especialidade::count();

        $this->assertSame($cbosCount1, $cbosCount2);
        $this->assertSame($espCount1, $espCount2);
    }

    public function test_importacao_oficial_de_cbos_via_csv_com_arquivo_padrao(): void
    {
        $this->artisan('cer:importar-cbos')
            ->assertSuccessful()
            ->expectsOutputToContain('Importação oficial concluída com sucesso!');

        // Verifica se preservou zeros à esquerda de ocupações que iniciam com 0
        $cboOficialZero = Cbo::where('codigo', '010105')->first();
        $this->assertNotNull($cboOficialZero);
        $this->assertSame('Oficial general da aeronáutica', $cboOficialZero->titulo_oficial);
        $this->assertNotNull($cboOficialZero->verificado_em);

        // Verifica CBO da Neuropsicologia
        $cboNeuro = Cbo::where('codigo', '251545')->first();
        $this->assertNotNull($cboNeuro);
        $this->assertSame('Neuropsicólogo', $cboNeuro->titulo_oficial);
        $this->assertNotNull($cboNeuro->verificado_em);
    }

    public function test_importacao_oficial_com_dry_run_nao_persiste_cbos_no_banco(): void
    {
        $this->artisan('cer:importar-cbos --dry-run')
            ->assertSuccessful()
            ->expectsOutputToContain('MODO DE SIMULAÇÃO ATIVO (--dry-run)');

        // Como foi simulação, o código de aeronáutica não deve existir no banco
        $this->assertDatabaseMissing('cbos', [
            'codigo' => '010105',
        ]);
    }

    public function test_importacao_oficial_rejeita_linhas_corrompidas_sem_interromper(): void
    {
        $tempCsv = tempnam(sys_get_temp_dir(), 'cbo_teste_');
        file_put_contents($tempCsv, implode("\n", [
            'codigo;titulo',
            '010205;Oficial general do exército',
            'XYZ;Código com letras inválido',
            '99;Código curto demais',
            '223605;Fisioterapeuta geral',
            '223810;', // sem título
        ]));

        $this->artisan('cer:importar-cbos', ['caminho' => $tempCsv])
            ->assertSuccessful()
            ->expectsOutputToContain('Amostra de linhas rejeitadas');

        $this->assertDatabaseHas('cbos', ['codigo' => '010205']);
        $this->assertDatabaseHas('cbos', ['codigo' => '223605']);
        $this->assertDatabaseMissing('cbos', ['codigo' => 'XYZ']);
        $this->assertDatabaseMissing('cbos', ['codigo' => '99']);

        @unlink($tempCsv);
    }

    public function test_busca_cbo_modal_em_configuracoes_sistema_com_preservacao_de_selecao(): void
    {
        $coordenacao = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();

        // Cria CBOs de teste
        $cboA = Cbo::create([
            'codigo' => '223605',
            'titulo_oficial' => 'Fisioterapeuta geral',
            'ativo' => true,
        ]);

        $cboB = Cbo::create([
            'codigo' => '251510',
            'titulo_oficial' => 'Psicólogo clínico',
            'ativo' => true,
        ]);

        $component = Livewire::actingAs($coordenacao)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'profissionais')
            ->call('abrirModalNovoProfissional')
            ->assertSet('buscaCboModal', '')
            ->set('buscaCboModal', '2515')
            ->assertSee('Psicólogo clínico');

        // Preservação da seleção: mesmo com busca que não traz o cboA, se o usuário selecionou cboA ele deve ser preservado na lista
        $component->set('prof_cbo_id', $cboA->id)
            ->set('buscaCboModal', 'Psicólogo')
            ->assertSee($cboA->codigo);
    }
}

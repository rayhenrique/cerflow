<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
use App\Models\ConfiguracaoEstabelecimento;
use App\Models\Paciente;
use App\Models\UnidadeBasicaSaude;
use App\Models\User;
use Database\Seeders\ProcedimentoSigtapSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnificarSelecaoUbsPacientesTest extends TestCase
{
    use RefreshDatabase;

    private User $coordenador;

    private User $recepcionista;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            ProcedimentoSigtapSeeder::class,
            SalaSeeder::class,
        ]);

        $this->coordenador = User::factory()->create(['ativo' => true]);
        $this->coordenador->assignRole('coordenacao');

        $this->recepcionista = User::factory()->create(['ativo' => true]);
        $this->recepcionista->assignRole('recepcao');

        ConfiguracaoEstabelecimento::create([
            'nome_fantasia' => 'CER Digital Teste',
            'razao_social' => 'CER Digital Teste LTDA',
            'cnes' => '2005432',
            'cnpj' => '12345678000199',
            'codigo_ibge_padrao' => '270430',
            'municipio' => 'Maceió',
            'uf' => 'AL',
        ]);
    }

    public function test_view_nao_contem_campo_duplicado_nem_datalist_nem_sugestoes_mockadas(): void
    {
        $ubsReal = UnidadeBasicaSaude::create([
            'nome' => 'UBS Centro Real',
            'cnes' => '2719111',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $mockPadroes = [
            'UBS Centro / PSF Central',
            'UBS Dr. José Ferreira',
            'UBS Santa Luzia',
            'UBS Bela Vista',
            'UBS São José',
            'USF Esperança',
            'UBS Zona Rural',
        ];

        $component = Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->assertViewMissing('listaUbs')
            ->assertDontSeeHtml('id="ubs-list"')
            ->assertDontSeeHtml('placeholder="Ou digite o nome da UBS de referência..."');

        // Garante que nenhuma das 7 sugestões mockadas fixas aparece no HTML se não existir no banco
        foreach ($mockPadroes as $mock) {
            $component->assertDontSeeHtml("<option value=\"{$mock}\">");
        }

        // Garante que a UBS real cadastrada está presente no select
        $component->assertSeeHtml("<option value=\"{$ubsReal->id}\">");
    }

    public function test_selecionar_ubs_sincroniza_nome_e_persiste_corretamente(): void
    {
        $ubs = UnidadeBasicaSaude::create([
            'nome' => 'Unidade de Saude da Familia 01',
            'cnes' => '2719222',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        Livewire::actingAs($this->recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->set('pac_ubs_id', $ubs->id)
            ->assertSet('pac_ubs_referencia', 'Unidade de Saude da Familia 01')
            ->set('pac_nome', 'Paciente Com UBS Sincronizada')
            ->set('pac_data_nascimento', '1995-04-10')
            ->set('pac_sexo', 'F')
            ->set('pac_municipio_ibge', '270430')
            ->call('salvarPaciente')
            ->assertHasNoErrors();

        $paciente = Paciente::where('nome', 'Paciente Com UBS Sincronizada')->first();
        $this->assertNotNull($paciente);
        $this->assertSame($ubs->id, $paciente->ubs_id);
        $this->assertSame('Unidade de Saude da Familia 01', $paciente->ubs_referencia);
    }

    public function test_paciente_sem_ubs_salva_com_campos_nulos_sem_criar_ubs_implicita(): void
    {
        $totalUbsAntes = UnidadeBasicaSaude::count();

        Livewire::actingAs($this->recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->set('pac_ubs_id', null)
            ->set('pac_nome', 'Paciente Sem UBS Alguma')
            ->set('pac_data_nascimento', '1988-08-08')
            ->set('pac_sexo', 'M')
            ->set('pac_municipio_ibge', '270430')
            ->call('salvarPaciente')
            ->assertHasNoErrors();

        $paciente = Paciente::where('nome', 'Paciente Sem UBS Alguma')->first();
        $this->assertNotNull($paciente);
        $this->assertNull($paciente->ubs_id);
        $this->assertNull($paciente->ubs_referencia);

        // Nenhuma UBS nova criada implicitamente
        $this->assertSame($totalUbsAntes, UnidadeBasicaSaude::count());
    }

    public function test_edicao_de_paciente_pre_seleciona_ubs_e_preserva_vinculo(): void
    {
        $ubs = UnidadeBasicaSaude::create([
            'nome' => 'UBS Sao Sebastiao',
            'cnes' => '2719333',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $paciente = Paciente::create([
            'nome' => 'Paciente Em Edicao',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'F',
            'municipio_ibge' => '270430',
            'ubs_id' => $ubs->id,
            'ubs_referencia' => 'UBS Sao Sebastiao',
            'ativo' => true,
        ]);

        Livewire::actingAs($this->recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('editarPaciente', $paciente->id)
            ->assertSet('pac_ubs_id', $ubs->id)
            ->assertSet('pac_ubs_referencia', 'UBS Sao Sebastiao')
            ->set('pac_nome', 'Paciente Em Edicao Nome Alterado')
            ->call('salvarPaciente')
            ->assertHasNoErrors();

        $paciente->refresh();
        $this->assertSame('Paciente Em Edicao Nome Alterado', $paciente->nome);
        $this->assertSame($ubs->id, $paciente->ubs_id);
        $this->assertSame('UBS Sao Sebastiao', $paciente->ubs_referencia);
    }

    public function test_preserva_ubs_textual_historica_legada_na_edicao_quando_ubs_id_nulo(): void
    {
        $pacienteLegado = Paciente::create([
            'nome' => 'Paciente Legado Historico',
            'data_nascimento' => '1980-05-20',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'ubs_id' => null, // Histórico sem ID
            'ubs_referencia' => 'UBS Historica Desconhecida',
            'ativo' => true,
        ]);

        Livewire::actingAs($this->recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('editarPaciente', $pacienteLegado->id)
            ->assertSet('pac_ubs_id', null)
            ->assertSet('pac_ubs_referencia', 'UBS Historica Desconhecida')
            // Salva sem selecionar pac_ubs_id: deve manter a ubs_referencia histórica intacta
            ->call('salvarPaciente')
            ->assertHasNoErrors();

        $pacienteLegado->refresh();
        $this->assertNull($pacienteLegado->ubs_id);
        $this->assertSame('UBS Historica Desconhecida', $pacienteLegado->ubs_referencia);
    }

    public function test_acs_nao_identificado_nao_e_gravado_como_nome_de_acs(): void
    {
        Livewire::actingAs($this->recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->set('pac_nome', 'Paciente Teste ACS Vazio')
            ->set('pac_data_nascimento', '1999-12-31')
            ->set('pac_sexo', 'M')
            ->set('pac_municipio_ibge', '270430')
            ->set('pac_nome_acs', 'ACS Não Identificado')
            ->call('salvarPaciente')
            ->assertHasNoErrors();

        $paciente = Paciente::where('nome', 'Paciente Teste ACS Vazio')->first();
        $this->assertNotNull($paciente);
        $this->assertNull($paciente->nome_acs, 'O texto "ACS Não Identificado" não deve ser salvo como nome no banco');
    }

    public function test_apenas_coordenacao_pode_abrir_gerenciador_ubs(): void
    {
        // Recepcionista não pode
        Livewire::actingAs($this->recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirGerenciadorUbs')
            ->assertForbidden();

        // Coordenação pode
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirGerenciadorUbs')
            ->assertSet('modalPacienteAberto', false)
            ->assertSet('abaAtiva', 'ubs');
    }

    public function test_comando_conciliar_pacientes_ubs_reconcilia_candidatos_com_seguranca(): void
    {
        $ubsReal = UnidadeBasicaSaude::create([
            'nome' => 'UBS Centro Maceio',
            'cnes' => '2719444',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        // Paciente 1: candidato a conciliação (ubs_referencia preenchida e ubs_id vazio)
        $pac1 = Paciente::create([
            'nome' => 'Paciente Candidato 1',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'ubs_id' => null,
            'ubs_referencia' => 'UBS Centro Maceio',
            'ativo' => true,
        ]);

        // Paciente 2: sem UBS alguma
        $pac2 = Paciente::create([
            'nome' => 'Paciente Sem UBS',
            'data_nascimento' => '1991-01-01',
            'sexo' => 'F',
            'municipio_ibge' => '270430',
            'ubs_id' => null,
            'ubs_referencia' => null,
            'ativo' => true,
        ]);

        // 1. Execução com --dry-run: ZERO gravações
        $this->artisan('esus:conciliar-pacientes', ['--dry-run' => true])
            ->expectsOutputToContain('[MODO SIMULAÇÃO CONCLUÍDO]')
            ->expectsOutputToContain('Vínculos Conciliados com Sucesso (ubs_id associado)')
            ->assertSuccessful();

        $pac1->refresh();
        $this->assertNull($pac1->ubs_id, 'Dry-run não pode gravar no banco');

        // 2. Execução real
        $this->artisan('esus:conciliar-pacientes')
            ->expectsOutputToContain('[SUCESSO] Conciliação de vínculos finalizada!')
            ->assertSuccessful();

        $pac1->refresh();
        $this->assertSame($ubsReal->id, $pac1->ubs_id, 'Deve associar o ubs_id correto');
        $this->assertSame('UBS Centro Maceio', $pac1->ubs_referencia, 'ubs_referencia original deve ser mantido');

        $pac2->refresh();
        $this->assertNull($pac2->ubs_id);
    }
}

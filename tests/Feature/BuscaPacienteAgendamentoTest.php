<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\GradeSemanal;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class BuscaPacienteAgendamentoTest extends TestCase
{
    use RefreshDatabase;

    private User $coordenador;

    private Sala $sala;

    private Profissional $profissional;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            SalaSeeder::class,
        ]);

        $this->coordenador = User::factory()->create(['ativo' => true]);
        $this->coordenador->assignRole('coordenacao');

        $this->sala = Sala::firstOrFail();

        $userProfissional = User::factory()->create(['ativo' => true]);
        $userProfissional->assignRole('terapeuta');

        $this->profissional = Profissional::create([
            'user_id' => $userProfissional->id,
            'nome' => 'Dra. Maria Fisioterapeuta',
            'cpf' => '11122233344',
            'cns' => CnsValido::gerarDefinitivo('11122233344'),
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia',
            'registro_conselho' => 'CREFITO 12345/AL',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);
    }

    public function test_modal_abre_sem_carregar_pacientes_antecipadamente(): void
    {
        // Cria múltiplos pacientes ativos
        for ($i = 1; $i <= 30; $i++) {
            Paciente::create([
                'nome' => sprintf('Paciente Teste %02d', $i),
                'cpf' => sprintf('100000000%02d', $i),
                'cns' => CnsValido::gerarDefinitivo(sprintf('100000000%02d', $i)),
                'data_nascimento' => '1990-01-01',
                'sexo' => 'M',
                'nome_mae' => 'Mãe Teste',
                'municipio_ibge' => '270430',
                'ativo' => true,
            ]);
        }

        // Garante que, ao abrir o modal, nenhum paciente é listado previamente
        Livewire::actingAs($this->coordenador)
            ->test(GradeSemanal::class)
            ->call('abrirModalSlot', $this->sala->id, '08:00')
            ->assertSet('modalAberto', true)
            ->assertSet('paciente_id', null)
            ->assertSet('pacienteSelecionado', null)
            ->assertSet('pesquisaPaciente', '')
            ->assertViewHas('pacientesResultados', [])
            ->assertSee('Digite o nome, CPF ou Cartão SUS do paciente')
            ->assertSee('Pesquise um paciente para iniciar')
            ->assertDontSee('Paciente Teste 01');
    }

    public function test_pesquisa_com_menos_de_tres_caracteres_nao_executa_busca(): void
    {
        Paciente::create([
            'nome' => 'Ana Beatriz Santos',
            'cpf' => '20000000001',
            'cns' => CnsValido::gerarDefinitivo('20000000001'),
            'data_nascimento' => '1995-05-10',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Ana',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        Livewire::actingAs($this->coordenador)
            ->test(GradeSemanal::class)
            ->call('abrirModalSlot', $this->sala->id, '08:00')
            ->set('pesquisaPaciente', 'An')
            ->assertViewHas('pacientesResultados', [])
            ->assertSee('Pesquise um paciente para iniciar')
            ->assertDontSee('Ana Beatriz Santos');
    }

    public function test_busca_por_nome_com_tres_ou_mais_caracteres_retorna_resultados(): void
    {
        $paciente = Paciente::create([
            'nome' => 'Carlos Eduardo Lima',
            'cpf' => '30000000001',
            'cns' => CnsValido::gerarDefinitivo('30000000001'),
            'data_nascimento' => '1988-08-15',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Carlos',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        Livewire::actingAs($this->coordenador)
            ->test(GradeSemanal::class)
            ->call('abrirModalSlot', $this->sala->id, '08:00')
            ->set('pesquisaPaciente', 'Carlos')
            ->assertViewHas('pacientesResultados', function (array $resultados) use ($paciente): bool {
                return count($resultados) === 1
                    && $resultados[0]['id'] === $paciente->id
                    && $resultados[0]['nome'] === 'Carlos Eduardo Lima'
                    && $resultados[0]['data_nascimento'] === '15/08/1988';
            })
            ->assertSee('Carlos Eduardo Lima')
            ->assertSee('15/08/1988');
    }

    public function test_busca_por_cpf_formatado_e_desformatado(): void
    {
        $paciente = Paciente::create([
            'nome' => 'Roberto Fernandes Costa',
            'cpf' => '40012345678',
            'cns' => CnsValido::gerarDefinitivo('40012345678'),
            'data_nascimento' => '1975-03-20',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Roberto',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        // Busca com formatação de CPF
        Livewire::actingAs($this->coordenador)
            ->test(GradeSemanal::class)
            ->call('abrirModalSlot', $this->sala->id, '08:00')
            ->set('pesquisaPaciente', '400.123')
            ->assertViewHas('pacientesResultados', function (array $resultados) use ($paciente): bool {
                return count($resultados) === 1 && $resultados[0]['id'] === $paciente->id;
            })
            ->assertSee('Roberto Fernandes Costa');

        // Busca sem pontuação
        Livewire::actingAs($this->coordenador)
            ->test(GradeSemanal::class)
            ->call('abrirModalSlot', $this->sala->id, '08:00')
            ->set('pesquisaPaciente', '400123')
            ->assertViewHas('pacientesResultados', function (array $resultados) use ($paciente): bool {
                return count($resultados) === 1 && $resultados[0]['id'] === $paciente->id;
            });
    }

    public function test_busca_por_cns_com_digitos(): void
    {
        $cns = CnsValido::gerarDefinitivo('50012345678');
        $paciente = Paciente::create([
            'nome' => 'Fernanda Oliveira Rocha',
            'cpf' => '50012345678',
            'cns' => $cns,
            'data_nascimento' => '2001-12-05',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Fernanda',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $trechoCns = substr($cns, 0, 7);

        Livewire::actingAs($this->coordenador)
            ->test(GradeSemanal::class)
            ->call('abrirModalSlot', $this->sala->id, '08:00')
            ->set('pesquisaPaciente', $trechoCns)
            ->assertViewHas('pacientesResultados', function (array $resultados) use ($paciente): bool {
                return count($resultados) === 1 && $resultados[0]['id'] === $paciente->id;
            })
            ->assertSee('Fernanda Oliveira Rocha');
    }

    public function test_busca_ignora_pacientes_inativos_e_excluidos(): void
    {
        Paciente::create([
            'nome' => 'Juliana Inativa Silva',
            'cpf' => '60000000001',
            'cns' => CnsValido::gerarDefinitivo('60000000001'),
            'data_nascimento' => '1990-01-01',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Juliana',
            'municipio_ibge' => '270430',
            'ativo' => false,
        ]);

        $excluido = Paciente::create([
            'nome' => 'Juliana Excluida Silva',
            'cpf' => '60000000002',
            'cns' => CnsValido::gerarDefinitivo('60000000002'),
            'data_nascimento' => '1990-01-01',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Juliana Excluida',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);
        $excluido->delete();

        Livewire::actingAs($this->coordenador)
            ->test(GradeSemanal::class)
            ->call('abrirModalSlot', $this->sala->id, '08:00')
            ->set('pesquisaPaciente', 'Juliana')
            ->assertViewHas('pacientesResultados', [])
            ->assertSee('Nenhum paciente encontrado');
    }

    public function test_limite_maximo_de_20_resultados(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Paciente::create([
                'nome' => sprintf('Busca Limite Silva %02d', $i),
                'cpf' => sprintf('700000000%02d', $i),
                'cns' => CnsValido::gerarDefinitivo(sprintf('700000000%02d', $i)),
                'data_nascimento' => '1992-02-02',
                'sexo' => 'M',
                'nome_mae' => 'Mãe Silva',
                'municipio_ibge' => '270430',
                'ativo' => true,
            ]);
        }

        Livewire::actingAs($this->coordenador)
            ->test(GradeSemanal::class)
            ->call('abrirModalSlot', $this->sala->id, '08:00')
            ->set('pesquisaPaciente', 'Busca Limite')
            ->assertViewHas('pacientesResultados', function (array $resultados): bool {
                return count($resultados) === 20;
            });
    }

    public function test_selecionar_paciente_atualiza_estado_e_exibe_card_com_opcao_de_troca(): void
    {
        $paciente = Paciente::create([
            'nome' => 'Marcos Vinicius de Souza',
            'cpf' => '80011122233',
            'cns' => CnsValido::gerarDefinitivo('80011122233'),
            'data_nascimento' => '1985-07-22',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Marcos',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $component = Livewire::actingAs($this->coordenador)
            ->test(GradeSemanal::class)
            ->call('abrirModalSlot', $this->sala->id, '08:00')
            ->set('pesquisaPaciente', 'Marcos')
            ->call('selecionarPaciente', $paciente->id);

        $component->assertSet('paciente_id', $paciente->id)
            ->assertSet('pacienteSelecionado.id', $paciente->id)
            ->assertSet('pacienteSelecionado.nome', 'Marcos Vinicius de Souza')
            ->assertSet('pesquisaPaciente', '')
            ->assertSee('Marcos Vinicius de Souza')
            ->assertSee('800.111.222-33')
            ->assertSee('22/07/1985')
            ->assertSee('Trocar');

        // Testa limpar/trocar a seleção
        $component->call('limparPacienteSelecionado')
            ->assertSet('paciente_id', null)
            ->assertSet('pacienteSelecionado', null)
            ->assertSet('pesquisaPaciente', '')
            ->assertSee('Digite o nome, CPF ou Cartão SUS do paciente');
    }

    public function test_validacao_exige_paciente_ao_salvar(): void
    {
        Livewire::actingAs($this->coordenador)
            ->test(GradeSemanal::class)
            ->call('abrirModalSlot', $this->sala->id, '08:00')
            ->set('paciente_id', null)
            ->set('sala_id', $this->sala->id)
            ->set('profissionaisSelecionados', [$this->profissional->id])
            ->call('salvarAgendamento')
            ->assertHasErrors(['paciente_id' => 'required']);
    }

    public function test_fluxo_completo_de_agendamento_com_paciente_selecionado(): void
    {
        $paciente = Paciente::create([
            'nome' => 'Patricia Helena Guimarães',
            'cpf' => '90022233344',
            'cns' => CnsValido::gerarDefinitivo('90022233344'),
            'data_nascimento' => '1998-11-12',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Patricia',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        Livewire::actingAs($this->coordenador)
            ->test(GradeSemanal::class)
            ->call('abrirModalSlot', $this->sala->id, '08:00')
            ->call('selecionarPaciente', $paciente->id)
            ->set('sala_id', $this->sala->id)
            ->set('profissionaisSelecionados', [$this->profissional->id])
            ->set('tipo_atendimento', 'INDIVIDUAL')
            ->set('dataReferencia', '2026-11-16')
            ->set('hora_inicio', '08:00')
            ->set('hora_fim', '08:30')
            ->set('diaSemana', 'SEGUNDA')
            ->call('salvarAgendamento')
            ->assertHasNoErrors()
            ->assertSet('modalAberto', false)
            ->assertSet('paciente_id', null)
            ->assertSet('pacienteSelecionado', null);

        $agendamento = Agendamento::query()->where('paciente_id', $paciente->id)->first();
        $this->assertNotNull($agendamento);
        $this->assertSame($this->sala->id, $agendamento->sala_id);
        $this->assertSame('INDIVIDUAL', $agendamento->tipo_atendimento);
        $this->assertSame('08:00', substr((string) $agendamento->hora_inicio, 0, 5));
        $this->assertSame('08:30', substr((string) $agendamento->hora_fim, 0, 5));
        $this->assertTrue($agendamento->profissionais->contains($this->profissional->id));
    }
}

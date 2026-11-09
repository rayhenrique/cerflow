<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\AgendaOperacional;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use Database\Seeders\ProcedimentoSigtapSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AgendaGradeNavegacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioRecepcao;

    private User $usuarioTerapeuta1;

    private User $usuarioTerapeuta2;

    private Profissional $terapeuta1;

    private Profissional $terapeuta2;

    private Sala $sala1;

    private Sala $sala2;

    private Paciente $paciente1;

    private Paciente $paciente2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            ProcedimentoSigtapSeeder::class,
            SalaSeeder::class,
        ]);

        $this->sala1 = Sala::first();
        $this->sala2 = Sala::skip(1)->first() ?? $this->sala1;

        $this->usuarioRecepcao = User::factory()->create(['name' => 'Recepcionista Grade', 'ativo' => true]);
        $this->usuarioRecepcao->assignRole('recepcao');

        $this->usuarioTerapeuta1 = User::factory()->create(['name' => 'Dra. Julia Terapeuta', 'ativo' => true]);
        $this->usuarioTerapeuta1->assignRole('terapeuta');
        $this->terapeuta1 = Profissional::create([
            'user_id' => $this->usuarioTerapeuta1->id,
            'nome' => 'Dra. Julia Terapeuta',
            'cpf' => '11144477733',
            'cns' => CnsValido::gerarDefinitivo('11144477733'),
            'registro_conselho' => 'CREFITO 5555/AL',
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia Neurofuncional',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);

        $this->usuarioTerapeuta2 = User::factory()->create(['name' => 'Dr. Bruno Fono', 'ativo' => true]);
        $this->usuarioTerapeuta2->assignRole('terapeuta');
        $this->terapeuta2 = Profissional::create([
            'user_id' => $this->usuarioTerapeuta2->id,
            'nome' => 'Dr. Bruno Fono',
            'cpf' => '22255588844',
            'cns' => CnsValido::gerarDefinitivo('22255588844'),
            'registro_conselho' => 'CRFA 8888/AL',
            'cbo_padrao' => '223810',
            'especialidade' => 'Fonoaudiologia Geral',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);

        $this->paciente1 = Paciente::create([
            'nome' => 'Carlos Alberto da Silva',
            'cns' => CnsValido::gerarDefinitivo('12345678901'),
            'cpf' => '12345678901',
            'data_nascimento' => '2010-04-12',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $this->paciente2 = Paciente::create([
            'nome' => 'Mariana Oliveira Ramos',
            'cns' => CnsValido::gerarDefinitivo('98765432100'),
            'cpf' => '98765432100',
            'data_nascimento' => '2015-08-20',
            'sexo' => 'F',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);
    }

    /**
     * TESTE 1: Abrir grade na segunda-feira.
     */
    public function test_1_abrir_grade_na_segunda_feira(): void
    {
        Livewire::actingAs($this->usuarioRecepcao)
            ->test(AgendaOperacional::class, ['aba' => 'grade', 'data' => '2026-11-09'])
            ->assertSet('aba', 'grade')
            ->assertSet('diaSemana', 'SEGUNDA')
            ->assertSet('data', '2026-11-09')
            ->assertSet('semanaInicio', '2026-11-09')
            ->assertSee('Segunda-feira, 09/11/2026')
            ->assertSee('Semana de 09/11/2026 a 13/11/2026');
    }

    /**
     * TESTE 2: Selecionar terça e validar sincronização da data e campos de formulário.
     */
    public function test_2_selecionar_terca_e_validar_data(): void
    {
        Livewire::actingAs($this->usuarioRecepcao)
            ->test(AgendaOperacional::class, ['aba' => 'grade', 'data' => '2026-11-09'])
            ->call('mudarDiaSemana', 'TERCA')
            ->assertSet('diaSemana', 'TERCA')
            ->assertSet('data', '2026-11-10')
            ->assertSet('novo_data', '2026-11-10')
            ->assertSet('novo_dia_semana', 'TERCA')
            ->assertSee('Terça-feira, 10/11/2026')
            ->assertSee('Semana de 09/11/2026 a 13/11/2026');
    }

    /**
     * TESTE 3: Selecionar quarta e validar agendamentos na grade.
     */
    public function test_3_selecionar_quarta_e_validar_agendamentos(): void
    {
        // Cria agendamento na Quarta-feira 11/11/2026
        $ag = Agendamento::create([
            'paciente_id' => $this->paciente1->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => '2026-11-11',
            'hora_inicio' => '09:00',
            'hora_fim' => '09:30',
            'dia_semana' => 'QUARTA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
            'recorrente' => true,
        ]);
        $ag->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        Livewire::actingAs($this->usuarioRecepcao)
            ->test(AgendaOperacional::class, ['aba' => 'grade', 'data' => '2026-11-09'])
            ->call('mudarDiaSemana', 'QUARTA')
            ->assertSet('diaSemana', 'QUARTA')
            ->assertSet('data', '2026-11-11')
            ->assertSee('Quarta-feira, 11/11/2026')
            ->assertSee($this->paciente1->nome);
    }

    /**
     * TESTE 4: Selecionar quinta e sexta com cálculo correto de datas.
     */
    public function test_4_selecionar_quinta_e_sexta(): void
    {
        $componente = Livewire::actingAs($this->usuarioRecepcao)
            ->test(AgendaOperacional::class, ['aba' => 'grade', 'data' => '2026-11-09']);

        // Quinta-feira (12/11/2026)
        $componente->call('mudarDiaSemana', 'QUINTA')
            ->assertSet('diaSemana', 'QUINTA')
            ->assertSet('data', '2026-11-12')
            ->assertSee('Quinta-feira, 12/11/2026');

        // Sexta-feira (13/11/2026)
        $componente->call('mudarDiaSemana', 'SEXTA')
            ->assertSet('diaSemana', 'SEXTA')
            ->assertSet('data', '2026-11-13')
            ->assertSee('Sexta-feira, 13/11/2026');
    }

    /**
     * TESTE 5: Avançar e voltar semanas preservando o dia da semana selecionado.
     */
    public function test_5_avancar_e_voltar_semanas(): void
    {
        $componente = Livewire::actingAs($this->usuarioRecepcao)
            ->test(AgendaOperacional::class, ['aba' => 'grade', 'data' => '2026-11-09'])
            ->call('mudarDiaSemana', 'QUARTA');

        $componente->assertSet('data', '2026-11-11');
        $componente->assertSet('semanaInicio', '2026-11-09');

        // Avança uma semana: deve ir para a quarta-feira seguinte (18/11/2026)
        $componente->call('proximaSemana')
            ->assertSet('diaSemana', 'QUARTA')
            ->assertSet('semanaInicio', '2026-11-16')
            ->assertSet('data', '2026-11-18')
            ->assertSee('Quarta-feira, 18/11/2026');

        // Volta uma semana: retorna para quarta-feira anterior (11/11/2026)
        $componente->call('semanaAnterior')
            ->assertSet('diaSemana', 'QUARTA')
            ->assertSet('semanaInicio', '2026-11-09')
            ->assertSet('data', '2026-11-11')
            ->assertSee('Quarta-feira, 11/11/2026');
    }

    /**
     * TESTE 6: Virada de mês e ano na navegação semanal.
     */
    public function test_6_virada_de_mes_e_ano(): void
    {
        // Semana do fim de ano: 28/12/2026 (Segunda) a 01/01/2027 (Sexta)
        $componente = Livewire::actingAs($this->usuarioRecepcao)
            ->test(AgendaOperacional::class, ['aba' => 'grade', 'data' => '2026-12-28'])
            ->call('mudarDiaSemana', 'QUARTA')
            ->assertSet('data', '2026-12-30')
            ->assertSet('mes', 12)
            ->assertSet('ano', 2026);

        // Avança para a primeira semana de 2027 (início em 04/01/2027)
        $componente->call('proximaSemana')
            ->assertSet('semanaInicio', '2027-01-04')
            ->assertSet('diaSemana', 'QUARTA')
            ->assertSet('data', '2027-01-06')
            ->assertSet('mes', 1)
            ->assertSet('ano', 2027)
            ->assertSee('Quarta-feira, 06/01/2027');

        // Retorna para a semana de 2026
        $componente->call('semanaAnterior')
            ->assertSet('semanaInicio', '2026-12-28')
            ->assertSet('diaSemana', 'QUARTA')
            ->assertSet('data', '2026-12-30')
            ->assertSet('mes', 12)
            ->assertSet('ano', 2026);
    }

    /**
     * TESTE 7: Agendamento em slot da data selecionada pré-preenche corretamente.
     */
    public function test_7_agendamento_em_slot_da_data_selecionada(): void
    {
        Livewire::actingAs($this->usuarioRecepcao)
            ->test(AgendaOperacional::class, ['aba' => 'grade', 'data' => '2026-11-09'])
            ->call('mudarDiaSemana', 'TERCA')
            ->call('abrirModalNovoAgendamento', $this->sala1->id, '10:00', '2026-11-10')
            ->assertSet('modalNovoAgendamentoAberto', true)
            ->assertSet('novo_sala_id', $this->sala1->id)
            ->assertSet('novo_hora_inicio', '10:00')
            ->assertSet('novo_hora_fim', '10:30')
            ->assertSet('novo_data', '2026-11-10')
            ->assertSet('novo_dia_semana', 'TERCA');
    }

    /**
     * TESTE 8: Preservação dos filtros (turno e visão).
     */
    public function test_8_preservacao_dos_filtros(): void
    {
        Livewire::actingAs($this->usuarioRecepcao)
            ->test(AgendaOperacional::class, ['aba' => 'grade', 'data' => '2026-11-09'])
            ->set('turnoGrade', 'TARDE')
            ->call('alternarVisaoGrade', 'SEMANA_COMPLETA')
            ->call('mudarDiaSemana', 'QUINTA')
            ->call('proximaSemana')
            ->assertSet('turnoGrade', 'TARDE')
            ->assertSet('visaoGrade', 'SEMANA_COMPLETA')
            ->assertSet('diaSemana', 'QUINTA');
    }

    /**
     * TESTE 9: Atualização por polling sem perder o dia selecionado.
     */
    public function test_9_atualizacao_por_polling_sem_perder_o_dia_selecionado(): void
    {
        $componente = Livewire::actingAs($this->usuarioRecepcao)
            ->test(AgendaOperacional::class, ['aba' => 'grade', 'data' => '2026-11-09'])
            ->call('mudarDiaSemana', 'QUARTA')
            ->assertSet('data', '2026-11-11');

        // Simula renderização repetida do Livewire / polling
        $componente->call('$refresh')
            ->assertSet('diaSemana', 'QUARTA')
            ->assertSet('data', '2026-11-11')
            ->assertSee('Quarta-feira, 11/11/2026');
    }

    /**
     * TESTE 10: Preservação da regra de agendamento recorrente e compartilhado.
     */
    public function test_10_preservacao_da_regra_de_agendamento_recorrente_e_compartilhado(): void
    {
        $componente = Livewire::actingAs($this->usuarioRecepcao)
            ->test(AgendaOperacional::class, ['aba' => 'grade', 'data' => '2026-11-09'])
            ->call('mudarDiaSemana', 'TERCA')
            ->call('abrirModalNovoAgendamento', $this->sala1->id, '14:00', '2026-11-10');

        $componente->set('novo_paciente_id', $this->paciente1->id)
            ->set('novo_profissionais', [$this->terapeuta1->id, $this->terapeuta2->id])
            ->set('novo_recorrente', true)
            ->set('novo_semanas_recorrencia', 4)
            ->call('salvarNovoAgendamento')
            ->assertHasNoErrors();

        // Verifica que foram gerados 4 agendamentos nas terças-feiras
        $datasEsperadas = ['2026-11-10', '2026-11-17', '2026-11-24', '2026-12-01'];
        foreach ($datasEsperadas as $dataEsperada) {
            $ag = Agendamento::whereDate('data_atendimento', $dataEsperada)
                ->where('sala_id', $this->sala1->id)
                ->first();

            $this->assertNotNull($ag, "Agendamento recorrente não encontrado para a data $dataEsperada.");
            $this->assertSame('COMPARTILHADO', $ag->tipo_atendimento);
            $this->assertSame('TERCA', $ag->dia_semana);
            $this->assertCount(2, $ag->profissionais);
        }
    }
}

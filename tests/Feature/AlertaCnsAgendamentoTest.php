<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\AgendaOperacional;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use App\Services\AgendamentoService;
use Database\Seeders\ProcedimentoSigtapSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AlertaCnsAgendamentoTest extends TestCase
{
    use RefreshDatabase;

    private User $coordenador;

    private User $recepcionista;

    private User $terapeutaUser;

    private User $faturamentoUser;

    private Profissional $profissional;

    private Sala $sala;

    private ProcedimentoSigtap $procedimento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            ProcedimentoSigtapSeeder::class,
            SalaSeeder::class,
        ]);

        $this->sala = Sala::first();
        $this->procedimento = ProcedimentoSigtap::first();

        $this->coordenador = User::factory()->create(['name' => 'Coordenador Sistema', 'ativo' => true]);
        $this->coordenador->assignRole('coordenacao');

        $this->recepcionista = User::factory()->create(['name' => 'Recepcionista Sistema', 'ativo' => true]);
        $this->recepcionista->assignRole('recepcao');

        $this->terapeutaUser = User::factory()->create(['name' => 'Terapeuta Sistema', 'ativo' => true]);
        $this->terapeutaUser->assignRole('terapeuta');

        $this->faturamentoUser = User::factory()->create(['name' => 'Faturamento Sistema', 'ativo' => true]);
        $this->faturamentoUser->assignRole('faturamento');

        $this->profissional = Profissional::create([
            'user_id' => $this->terapeutaUser->id,
            'nome' => 'Dr. Terapeuta Fonoaudiólogo',
            'cpf' => '22233344455',
            'cns' => CnsValido::gerarDefinitivo('22233344455'),
            'registro_conselho' => 'CREFONO 1234/AL',
            'cbo_padrao' => '223810',
            'especialidade' => 'Fonoaudiologia',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);
    }

    /**
     * CENÁRIO 1: Paciente sem CNS: apresentar aviso imediato ao selecionar.
     */
    public function test_paciente_sem_cns_apresenta_aviso_imediato_ao_selecionar(): void
    {
        $pacienteSemCns = Paciente::create([
            'nome' => 'Carlos Eduardo Sem Cartão SUS',
            'cpf' => '11122233344',
            'cns' => null,
            'data_nascimento' => '2015-06-10',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Carlos',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        Livewire::actingAs($this->recepcionista)
            ->test(AgendaOperacional::class)
            ->call('abrirModalNovoAgendamento', $this->sala->id, '08:00', '2026-11-16')
            ->call('selecionarPaciente', $pacienteSemCns->id)
            ->assertSet('novo_paciente_id', $pacienteSemCns->id)
            ->assertSet('alertaPaciente.tipo', 'sem_cns')
            ->assertSet('alertaPaciente.titulo', 'Atenção: paciente sem Cartão SUS')
            ->assertSee('Atenção: paciente sem Cartão SUS')
            ->assertSee('Este paciente não possui o número do Cartão SUS (CNS) cadastrado. Para realizar o agendamento, atualize o cadastro do paciente.')
            ->assertDontSee('RN02');
    }

    /**
     * CENÁRIO 2: Paciente com CNS inválido: apresentar aviso específico ao selecionar.
     */
    public function test_paciente_com_cns_invalido_apresenta_aviso_especifico_ao_selecionar(): void
    {
        $pacienteCnsInvalido = Paciente::create([
            'nome' => 'Luciana CNS Invalido da Silva',
            'cpf' => '22233344455',
            'cns' => '111111111111111', // Formato de dígitos repetidos, inválido no Módulo 11
            'data_nascimento' => '2016-08-20',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Luciana',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        Livewire::actingAs($this->recepcionista)
            ->test(AgendaOperacional::class)
            ->call('abrirModalNovoAgendamento', $this->sala->id, '08:00', '2026-11-16')
            ->call('selecionarPaciente', $pacienteCnsInvalido->id)
            ->assertSet('alertaPaciente.tipo', 'cns_invalido')
            ->assertSet('alertaPaciente.titulo', 'Atenção: Cartão SUS inválido')
            ->assertSee('Atenção: Cartão SUS inválido')
            ->assertSee('O número do Cartão SUS cadastrado para este paciente é inválido. Confira e atualize essa informação antes de continuar.')
            ->assertDontSee('RN02');
    }

    /**
     * CENÁRIO 3: Paciente sem data de nascimento: indicar a pendência correta.
     */
    public function test_paciente_sem_data_nascimento_indica_pendencia_correta(): void
    {
        $pacienteSemNascimento = new Paciente([
            'nome' => 'Mariana Sem Data Nascimento',
            'cpf' => '33344455566',
            'cns' => CnsValido::gerarDefinitivo('33344455566'),
            'data_nascimento' => null,
            'sexo' => 'F',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);
        $pacienteSemNascimento->id = 999;

        $service = app(AgendamentoService::class);
        $alerta = $service->validarDadosCadastraisPaciente($pacienteSemNascimento);

        $this->assertNotNull($alerta);
        $this->assertSame('sem_nascimento', $alerta['tipo']);
        $this->assertSame('Atenção: data de nascimento não informada', $alerta['titulo']);
        $this->assertSame('Este paciente não possui a data de nascimento cadastrada. Para realizar o agendamento, atualize o cadastro do paciente.', $alerta['mensagem']);
    }

    /**
     * CENÁRIO 4: Paciente com dados válidos: permitir agendamento sem alertas.
     */
    public function test_paciente_com_dados_validos_permite_agendamento_sem_alertas(): void
    {
        $pacienteValido = Paciente::create([
            'nome' => 'Beatriz Dados Validos Santos',
            'cpf' => '44455566677',
            'cns' => CnsValido::gerarDefinitivo('44455566677'),
            'data_nascimento' => '2014-03-25',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Beatriz',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        Livewire::actingAs($this->recepcionista)
            ->test(AgendaOperacional::class)
            ->call('abrirModalNovoAgendamento', $this->sala->id, '08:00', '2026-11-16')
            ->call('selecionarPaciente', $pacienteValido->id)
            ->assertSet('alertaPaciente', null)
            ->assertDontSee('Pendência Cadastral')
            ->assertDontSee('Atenção: paciente sem Cartão SUS')
            ->set('novo_sala_id', $this->sala->id)
            ->set('novo_data', '2026-11-16')
            ->set('novo_hora_inicio', '08:00')
            ->set('novo_hora_fim', '08:30')
            ->set('novo_dia_semana', 'SEGUNDA')
            ->set('novo_tipo_atendimento', 'INDIVIDUAL')
            ->set('novo_profissionais', [$this->profissional->id])
            ->call('salvarNovoAgendamento')
            ->assertHasNoErrors()
            ->assertSet('modalNovoAgendamentoAberto', false);

        $agendamento = Agendamento::query()->where('paciente_id', $pacienteValido->id)->first();
        $this->assertNotNull($agendamento);
        $this->assertSame($this->sala->id, $agendamento->sala_id);
        $this->assertSame('2026-11-16', $agendamento->data_atendimento->toDateString());
    }

    /**
     * CENÁRIO 5: Tentativa de salvar sem CNS: manter modal aberto e preservar formulário preenchido.
     */
    public function test_tentativa_de_salvar_sem_cns_mantem_modal_aberto_e_preserva_formulario(): void
    {
        $pacienteSemCns = Paciente::create([
            'nome' => 'Diego Sem CNS da Silva',
            'cpf' => '55566677788',
            'cns' => null,
            'data_nascimento' => '2013-11-10',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Diego',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        Livewire::actingAs($this->recepcionista)
            ->test(AgendaOperacional::class)
            ->call('abrirModalNovoAgendamento', $this->sala->id, '09:00', '2026-11-16')
            ->call('selecionarPaciente', $pacienteSemCns->id)
            ->set('novo_sala_id', $this->sala->id)
            ->set('novo_data', '2026-11-16')
            ->set('novo_hora_inicio', '09:00')
            ->set('novo_hora_fim', '09:30')
            ->set('novo_dia_semana', 'SEGUNDA')
            ->set('novo_tipo_atendimento', 'INDIVIDUAL')
            ->set('novo_profissionais', [$this->profissional->id])
            ->set('novo_observacao', 'Observação importante pré-atendimento')
            ->call('salvarNovoAgendamento')
            // O modal deve continuar aberto
            ->assertSet('modalNovoAgendamentoAberto', true)
            // Os campos do formulário devem ser preservados
            ->assertSet('novo_sala_id', $this->sala->id)
            ->assertSet('novo_data', '2026-11-16')
            ->assertSet('novo_hora_inicio', '09:00')
            ->assertSet('novo_hora_fim', '09:30')
            ->assertSet('novo_profissionais', [$this->profissional->id])
            ->assertSet('novo_observacao', 'Observação importante pré-atendimento')
            // O nome do paciente continua visível
            ->assertSee('Diego Sem CNS da Silva')
            // Nenhum agendamento gravado
            ->assertHasErrors(['novo_paciente_id']);

        $this->assertDatabaseMissing('agendamentos', [
            'paciente_id' => $pacienteSemCns->id,
        ]);
    }

    /**
     * CENÁRIO 6: Exibir erro de validação no campo correto da Agenda Operacional (novo_paciente_id).
     */
    public function test_exibe_erro_de_validacao_no_campo_correto_novo_paciente_id(): void
    {
        $pacienteSemCns = Paciente::create([
            'nome' => 'Fernando Sem Documento SUS',
            'cpf' => '66677788899',
            'cns' => '',
            'data_nascimento' => '2012-07-04',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Fernando',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        Livewire::actingAs($this->recepcionista)
            ->test(AgendaOperacional::class)
            ->call('abrirModalNovoAgendamento', $this->sala->id, '10:00', '2026-11-16')
            ->call('selecionarPaciente', $pacienteSemCns->id)
            ->set('novo_sala_id', $this->sala->id)
            ->set('novo_data', '2026-11-16')
            ->set('novo_hora_inicio', '10:00')
            ->set('novo_hora_fim', '10:30')
            ->set('novo_profissionais', [$this->profissional->id])
            ->call('salvarNovoAgendamento')
            ->assertHasErrors(['novo_paciente_id' => 'Este paciente não possui o número do Cartão SUS (CNS) cadastrado. Para realizar o agendamento, atualize o cadastro do paciente.']);
    }

    /**
     * CENÁRIO 7: Trocar o paciente: limpar o alerta anterior ao selecionar outro paciente válido.
     */
    public function test_trocar_paciente_limpa_o_alerta_anterior(): void
    {
        $pacienteSemCns = Paciente::create([
            'nome' => 'Gabriel Sem Cartao',
            'cpf' => '77788899900',
            'cns' => null,
            'data_nascimento' => '2011-09-15',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Gabriel',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $pacienteComCns = Paciente::create([
            'nome' => 'Helena Com Cartao SUS Valido',
            'cpf' => '88899900011',
            'cns' => CnsValido::gerarDefinitivo('88899900011'),
            'data_nascimento' => '2010-12-05',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Helena',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $component = Livewire::actingAs($this->recepcionista)
            ->test(AgendaOperacional::class)
            ->call('abrirModalNovoAgendamento')
            // 1. Seleciona paciente sem CNS -> Alerta ativo
            ->call('selecionarPaciente', $pacienteSemCns->id)
            ->assertSet('alertaPaciente.tipo', 'sem_cns')
            // 2. Clica em trocar -> Alerta limpo
            ->call('limparPacienteSelecionado')
            ->assertSet('novo_paciente_id', null)
            ->assertSet('pacienteSelecionado', null)
            ->assertSet('alertaPaciente', null)
            // 3. Seleciona paciente com CNS válido -> Sem alerta
            ->call('selecionarPaciente', $pacienteComCns->id)
            ->assertSet('novo_paciente_id', $pacienteComCns->id)
            ->assertSet('alertaPaciente', null)
            ->assertDontSee('Pendência Cadastral');
    }

    /**
     * CENÁRIO 8: Cadastro corrigido: permitir nova tentativa com revalidação de dados.
     */
    public function test_cadastro_corrigido_permite_revalidar_dados_e_salvar(): void
    {
        $paciente = Paciente::create([
            'nome' => 'Igor Que Teve Cadastro Atualizado',
            'cpf' => '99900011122',
            'cns' => null, // Começa sem CNS
            'data_nascimento' => '2015-01-30',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Igor',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $test = Livewire::actingAs($this->recepcionista)
            ->test(AgendaOperacional::class)
            ->call('abrirModalNovoAgendamento', $this->sala->id, '14:00', '2026-11-16')
            ->call('selecionarPaciente', $paciente->id)
            ->assertSet('alertaPaciente.tipo', 'sem_cns')
            ->set('novo_sala_id', $this->sala->id)
            ->set('novo_data', '2026-11-16')
            ->set('novo_hora_inicio', '14:00')
            ->set('novo_hora_fim', '14:30')
            ->set('novo_profissionais', [$this->profissional->id]);

        // Simula a recepção atualizando o cadastro do paciente no banco de dados
        $cnsGerado = CnsValido::gerarDefinitivo('99900011122');
        $paciente->update(['cns' => $cnsGerado]);

        // Clica em revalidar dados
        $test->call('revalidarPacienteSelecionado')
            ->assertSet('alertaPaciente', null)
            ->assertSet('pacienteSelecionado.cns', $cnsGerado)
            // Agora pode salvar com sucesso
            ->call('salvarNovoAgendamento')
            ->assertHasNoErrors()
            ->assertSet('modalNovoAgendamentoAberto', false);

        $this->assertDatabaseHas('agendamentos', [
            'paciente_id' => $paciente->id,
            'hora_inicio' => '14:00',
        ]);
    }

    /**
     * CENÁRIO 9: Respeitar permissões de edição de cadastro.
     */
    public function test_respeita_permissoes_de_edicao_de_cadastro(): void
    {
        $paciente = Paciente::create([
            'nome' => 'João Permissão de Edição',
            'cpf' => '12312312344',
            'cns' => null,
            'data_nascimento' => '2014-05-12',
            'sexo' => 'M',
            'nome_mae' => 'Mãe João',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        // Coordenação e Recepção têm permissão para atualizar cadastro
        $this->actingAs($this->coordenador)
            ->get(route('configuracoes.secao', ['secao' => 'pacientes', 'editar_paciente' => $paciente->id]))
            ->assertOk()
            ->assertSee('João Permissão de Edição');

        $this->actingAs($this->recepcionista)
            ->get(route('configuracoes.secao', ['secao' => 'pacientes', 'editar_paciente' => $paciente->id]))
            ->assertOk()
            ->assertSee('João Permissão de Edição');

        // Terapeuta e Faturamento não possuem permissão de editar cadastro de pacientes
        $this->actingAs($this->terapeutaUser)
            ->get(route('configuracoes.secao', ['secao' => 'pacientes', 'editar_paciente' => $paciente->id]))
            ->assertForbidden();

        $this->actingAs($this->faturamentoUser)
            ->get(route('configuracoes.secao', ['secao' => 'pacientes', 'editar_paciente' => $paciente->id]))
            ->assertForbidden();
    }

    /**
     * CENÁRIO 10: Não permitir contornar a validação manipulando propriedades Livewire.
     */
    public function test_nao_permite_contornar_validacao_manipulando_propriedades_livewire(): void
    {
        $pacienteSemCns = Paciente::create([
            'nome' => 'Katia Tentativa Bypass',
            'cpf' => '32132132155',
            'cns' => null,
            'data_nascimento' => '2016-02-14',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Katia',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        // Atacante tenta setar alertaPaciente = null diretamente e chamar salvarNovoAgendamento
        Livewire::actingAs($this->recepcionista)
            ->test(AgendaOperacional::class)
            ->call('abrirModalNovoAgendamento', $this->sala->id, '15:00', '2026-11-16')
            ->set('novo_paciente_id', $pacienteSemCns->id)
            ->set('alertaPaciente', null) // Tenta forçar sem alerta
            ->set('novo_sala_id', $this->sala->id)
            ->set('novo_data', '2026-11-16')
            ->set('novo_hora_inicio', '15:00')
            ->set('novo_hora_fim', '15:30')
            ->set('novo_dia_semana', 'SEGUNDA')
            ->set('novo_profissionais', [$this->profissional->id])
            ->call('salvarNovoAgendamento')
            // O servidor deve interceptar e impedir a gravação
            ->assertHasErrors(['novo_paciente_id'])
            ->assertSet('modalNovoAgendamentoAberto', true);

        $this->assertDatabaseMissing('agendamentos', [
            'paciente_id' => $pacienteSemCns->id,
        ]);
    }

    /**
     * CENÁRIO 11: Preservar os demais erros de validação (conflito, horários, profissionais, salas).
     */
    public function test_preserva_os_demais_erros_de_validacao(): void
    {
        $pacienteValido = Paciente::create([
            'nome' => 'Larissa Conflito Horario',
            'cpf' => '54354354366',
            'cns' => CnsValido::gerarDefinitivo('54354354366'),
            'data_nascimento' => '2013-09-18',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Larissa',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        // Agendamento já existente na sala 1 no mesmo horário
        Agendamento::create([
            'paciente_id' => $pacienteValido->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => '2026-11-16',
            'hora_inicio' => '16:00',
            'hora_fim' => '16:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
            'recorrente' => false,
        ]);

        $outroPacienteValido = Paciente::create([
            'nome' => 'Marcos Outro Paciente',
            'cpf' => '65465465477',
            'cns' => CnsValido::gerarDefinitivo('65465465477'),
            'data_nascimento' => '2012-04-22',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Marcos',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        // Tentativa de agendar no mesmo horário e sala ocupada -> erro de conflito em novo_sala_id
        Livewire::actingAs($this->recepcionista)
            ->test(AgendaOperacional::class)
            ->call('abrirModalNovoAgendamento', $this->sala->id, '16:00', '2026-11-16')
            ->call('selecionarPaciente', $outroPacienteValido->id)
            ->set('novo_sala_id', $this->sala->id)
            ->set('novo_data', '2026-11-16')
            ->set('novo_hora_inicio', '16:00')
            ->set('novo_hora_fim', '16:30')
            ->set('novo_dia_semana', 'SEGUNDA')
            ->set('novo_profissionais', [$this->profissional->id])
            ->call('salvarNovoAgendamento')
            ->assertHasErrors(['novo_sala_id']);
    }
}

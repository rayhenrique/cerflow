<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\AgendaOperacional;
use App\Livewire\AjudaSobre;
use App\Livewire\ProntuarioPaciente;
use App\Models\Agendamento;
use App\Models\ConfiguracaoEstabelecimento;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use App\Support\IbgeMunicipio;
use Database\Seeders\DadosFicticiosLocaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AjustesInterfacePepIbgeCnesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DadosFicticiosLocaisSeeder::class);
    }

    public function test_botao_novo_agendamento_na_agenda_operacional_exibe_texto_sem_sinal_de_mais_duplicado(): void
    {
        $recepcao = User::where('email', 'recepcao@cerflow.gov.br')->firstOrFail();

        Livewire::actingAs($recepcao)
            ->test(AgendaOperacional::class)
            ->assertSeeHtml('Novo Agendamento')
            ->assertDontSeeHtml('+ Novo Agendamento')
            ->assertDontSeeHtml('+  Novo Agendamento');
    }

    public function test_sub_aba_meus_atendimentos_no_pep_lista_agendamentos_com_filtros(): void
    {
        $terapeuta = User::where('email', 'fisioterapeuta@cerflow.gov.br')->firstOrFail();

        Livewire::actingAs($terapeuta)
            ->test(ProntuarioPaciente::class)
            ->call('selecionarAbaPep', 'meus_atendimentos')
            ->assertSet('abaPep', 'meus_atendimentos')
            ->assertSeeHtml('Meus Atendimentos')
            ->assertSeeHtml('Próximos atendimentos')
            ->set('filtroPeriodo', 'hoje')
            ->assertStatus(200)
            ->set('filtroPeriodo', 'proximos')
            ->assertStatus(200)
            ->set('filtroPeriodo', 'anteriores')
            ->assertStatus(200)
            ->set('filtroStatus', 'AGENDADO')
            ->assertStatus(200);
    }

    public function test_acao_abrir_prontuario_no_pep_carrega_paciente_na_aba_clinica(): void
    {
        $terapeuta = User::where('email', 'fisioterapeuta@cerflow.gov.br')->firstOrFail();
        $profissional = Profissional::where('user_id', $terapeuta->id)->firstOrFail();
        $paciente = Paciente::firstOrFail();
        $sala = Sala::firstOrFail();

        $agendamento = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => now()->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fim' => '10:40:00',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
        ]);
        $agendamento->profissionais()->sync([$profissional->id]);

        Livewire::actingAs($terapeuta)
            ->test(ProntuarioPaciente::class)
            ->call('selecionarAbaPep', 'meus_atendimentos')
            ->call('abrirProntuarioAtendimento', $agendamento->id)
            ->assertSet('abaPep', 'prontuario')
            ->assertSet('pacienteId', $paciente->id)
            ->assertSet('agendamento_id', $agendamento->id);
    }

    public function test_historico_de_atendimentos_do_paciente_exibe_secao_e_paginacao(): void
    {
        $terapeuta = User::where('email', 'fisioterapeuta@cerflow.gov.br')->firstOrFail();
        $paciente = Paciente::firstOrFail();

        Livewire::actingAs($terapeuta)
            ->test(ProntuarioPaciente::class)
            ->call('selecionarPaciente', $paciente->id)
            ->assertSeeHtml('Atendimentos do Paciente')
            ->assertSeeHtml('Data / Horário');
    }

    public function test_criacao_de_agendamento_direto_pelo_pep_via_modal(): void
    {
        $terapeuta = User::where('email', 'fisioterapeuta@cerflow.gov.br')->firstOrFail();
        $profissional = Profissional::where('user_id', $terapeuta->id)->firstOrFail();
        $paciente = Paciente::firstOrFail();
        $sala = Sala::firstOrFail();

        $dataFutura = now()->addDays(60)->next('Wednesday')->toDateString();

        Livewire::actingAs($terapeuta)
            ->test(ProntuarioPaciente::class)
            ->call('selecionarPaciente', $paciente->id)
            ->call('abrirModalNovoAgendamento', $paciente->id)
            ->assertSet('modalNovoAgendamentoAberto', true)
            ->assertSet('novo_paciente_id', $paciente->id)
            ->set('novo_sala_id', $sala->id)
            ->set('novo_data', $dataFutura)
            ->set('novo_dia_semana', 'QUARTA')
            ->set('novo_hora_inicio', '14:00')
            ->set('novo_hora_fim', '14:40')
            ->set('novo_profissionais', [$profissional->id])
            ->call('salvarNovoAgendamento')
            ->assertHasNoErrors()
            ->assertSet('modalNovoAgendamentoAberto', false);

        $this->assertDatabaseHas('agendamentos', [
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'hora_inicio' => '14:00',
            'status' => 'AGENDADO',
        ]);
    }

    public function test_bloqueio_de_evolucao_clinica_para_agendamento_sem_presenca_confirmada(): void
    {
        $terapeuta = User::where('email', 'fisioterapeuta@cerflow.gov.br')->firstOrFail();
        $profissional = Profissional::where('user_id', $terapeuta->id)->firstOrFail();
        $paciente = Paciente::firstOrFail();
        $sala = Sala::firstOrFail();
        $procedimento = ProcedimentoSigtap::firstOrFail();

        $agendamento = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => now()->toDateString(),
            'hora_inicio' => '11:00:00',
            'hora_fim' => '11:40:00',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
        ]);
        $agendamento->profissionais()->sync([$profissional->id]);

        Livewire::actingAs($terapeuta)
            ->test(ProntuarioPaciente::class)
            ->call('selecionarPaciente', $paciente->id)
            ->set('agendamento_id', $agendamento->id)
            ->set('procedimento_id', $procedimento->id)
            ->set('queixa_estado_atual', 'Paciente relata dores na coluna lombar')
            ->set('conduta_terapeutica', 'Mobilização articular passiva e cinesioterapia')
            ->call('solicitarPinAssinatura')
            ->assertHasErrors(['agendamento_id']);

        $this->assertDatabaseMissing('evolucoes_clinicas', [
            'agendamento_id' => $agendamento->id,
        ]);
    }

    public function test_assinatura_de_evolucao_clinica_para_agendamento_presente_gera_producao(): void
    {
        $terapeuta = User::where('email', 'fisioterapeuta@cerflow.gov.br')->firstOrFail();
        $profissional = Profissional::where('user_id', $terapeuta->id)->firstOrFail();
        $paciente = Paciente::firstOrFail();
        $sala = Sala::firstOrFail();
        $procedimento = ProcedimentoSigtap::firstOrFail();

        $agendamento = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => now()->toDateString(),
            'hora_inicio' => '09:00:00',
            'hora_fim' => '09:40:00',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
            'chegada_em' => now(),
        ]);
        $agendamento->profissionais()->sync([$profissional->id]);

        Livewire::actingAs($terapeuta)
            ->test(ProntuarioPaciente::class)
            ->call('selecionarPaciente', $paciente->id)
            ->set('agendamento_id', $agendamento->id)
            ->set('procedimento_id', $procedimento->id)
            ->set('queixa_estado_atual', 'Paciente relata dores musculares em melhora')
            ->set('conduta_terapeutica', 'Exercícios funcionais com boa evolução motora')
            ->call('solicitarPinAssinatura')
            ->assertHasNoErrors()
            ->set('pin_assinatura', '1234')
            ->call('confirmarAssinaturaEvolucao')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('evolucoes_clinicas', [
            'agendamento_id' => $agendamento->id,
            'paciente_id' => $paciente->id,
            'profissional_id' => $profissional->id,
        ]);

        $this->assertDatabaseHas('producoes_bpai', [
            'paciente_id' => $paciente->id,
            'profissional_id' => $profissional->id,
            'procedimento_id' => $procedimento->id,
        ]);
    }

    public function test_codigo_ibge_padrao_e_cnes_padrao_sao_teotonio_vilela_2709152_e_0795690(): void
    {
        $config = ConfiguracaoEstabelecimento::atual();

        $this->assertSame('0795690', $config->cnes);
        $this->assertSame('2709152', $config->codigo_ibge_padrao);
    }

    public function test_preservacao_de_pacientes_com_outros_municipios(): void
    {
        $cns = CnsValido::gerarDefinitivo('12345678901');

        $paciente = Paciente::create([
            'nome' => 'Paciente de Maceió',
            'cpf' => '99887766554',
            'cns' => $cns,
            'data_nascimento' => '1990-05-10',
            'sexo' => 'M',
            'nome_mae' => 'Mãe de Maceió',
            'municipio_ibge' => '2704302',
            'ativo' => true,
        ]);

        $this->assertSame('2704302', $paciente->fresh()->municipio_ibge);
        $this->assertSame('270430', IbgeMunicipio::converterParaBpai($paciente->municipio_ibge));
    }

    public function test_helper_ibge_municipio_validacao_e_conversao_bpai(): void
    {
        $this->assertTrue(IbgeMunicipio::validar('2709152'));
        $this->assertTrue(IbgeMunicipio::validar('270915'));
        $this->assertTrue(IbgeMunicipio::validar('2704302'));
        $this->assertFalse(IbgeMunicipio::validar('123'));
        $this->assertFalse(IbgeMunicipio::validar('abcdefg'));

        $this->assertSame('270915', IbgeMunicipio::converterParaBpai('2709152'));
        $this->assertSame('270915', IbgeMunicipio::converterParaBpai('270915'));
        $this->assertSame('270430', IbgeMunicipio::converterParaBpai('2704302'));
        $this->assertSame('2709152', IbgeMunicipio::formatar7('270915'));
        $this->assertSame(2, IbgeMunicipio::calcularDigitoVerificador('270915'));
    }

    public function test_cnes_exibido_dinamicamente_na_central_de_ajuda_preserva_zero_inicial(): void
    {
        $usuario = User::firstOrFail();

        Livewire::actingAs($usuario)
            ->test(AjudaSobre::class)
            ->assertSeeHtml('0795690')
            ->assertSeeHtml('Teotônio Vilela / AL')
            ->assertDontSeeHtml('2000001');
    }
}

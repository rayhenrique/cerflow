<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\AgendaOperacional;
use App\Livewire\ProntuarioPaciente;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\ProducaoBpai;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use App\Services\EvolucaoService;
use Carbon\CarbonImmutable;
use Database\Seeders\ProcedimentoSigtapSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AgendaOperacionalPepTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioRecepcao;

    private User $usuarioTerapeuta1;

    private User $usuarioTerapeuta2;

    private User $usuarioCoordenacao;

    private User $usuarioSemPermissao;

    private Profissional $terapeuta1;

    private Profissional $terapeuta2;

    private Sala $sala1;

    private Sala $sala2;

    private ProcedimentoSigtap $procedimentoFono;

    private ProcedimentoSigtap $procedimentoTo;

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

        $this->procedimentoFono = ProcedimentoSigtap::where('codigo', '0301040044')->firstOrFail();
        $this->procedimentoTo = ProcedimentoSigtap::where('codigo', '0301070113')->firstOrFail();

        // Usuário Recepção
        $this->usuarioRecepcao = User::factory()->create(['name' => 'Recepcionista Maria', 'ativo' => true]);
        $this->usuarioRecepcao->assignRole('recepcao');

        // Usuário Terapeuta 1
        $this->usuarioTerapeuta1 = User::factory()->create(['name' => 'Dra. Helena Fono', 'ativo' => true]);
        $this->usuarioTerapeuta1->assignRole('terapeuta');
        $this->terapeuta1 = Profissional::create([
            'user_id' => $this->usuarioTerapeuta1->id,
            'nome' => 'Dra. Helena Fono',
            'cpf' => '11122233344',
            'cns' => CnsValido::gerarDefinitivo('11122233344'),
            'registro_conselho' => 'CRFa 4-1010',
            'cbo_padrao' => '223810',
            'especialidade' => 'Fonoaudiologia',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);

        // Usuário Terapeuta 2
        $this->usuarioTerapeuta2 = User::factory()->create(['name' => 'Dra. Beatriz TO', 'ativo' => true]);
        $this->usuarioTerapeuta2->assignRole('terapeuta');
        $this->terapeuta2 = Profissional::create([
            'user_id' => $this->usuarioTerapeuta2->id,
            'nome' => 'Dra. Beatriz TO',
            'cpf' => '22233344455',
            'cns' => CnsValido::gerarDefinitivo('22233344455'),
            'registro_conselho' => 'CREFITO-1 5050',
            'cbo_padrao' => '223905',
            'especialidade' => 'Terapia Ocupacional',
            'pin_assinatura_hash' => Hash::make('5678'),
            'ativo' => true,
        ]);

        // Usuário Coordenação
        $this->usuarioCoordenacao = User::factory()->create(['name' => 'Coordenação Geral', 'ativo' => true]);
        $this->usuarioCoordenacao->assignRole('coordenacao');

        // Usuário Sem Permissão
        $this->usuarioSemPermissao = User::factory()->create(['name' => 'Sem Acesso', 'ativo' => true]);
    }

    private function criarPaciente(string $nome, string $cpf = '12345678901'): Paciente
    {
        return Paciente::create([
            'nome' => $nome,
            'cpf' => $cpf,
            'cns' => CnsValido::gerarDefinitivo($cpf),
            'data_nascimento' => '2015-05-10',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Teste',
            'municipio_ibge' => '270430',
            'cid_principal' => 'F840',
            'ativo' => true,
        ]);
    }

    // 1. Renderização da Agenda Operacional com as 3 abas para perfis autorizados
    public function test_renderizacao_agenda_operacional_com_3_abas_para_perfis_autorizados(): void
    {
        $this->actingAs($this->usuarioRecepcao)->get('/agenda')
            ->assertOk()
            ->assertSee('Agenda Operacional do CER')
            ->assertSee('Visualização Diária')
            ->assertSee('Grade Semanal')
            ->assertSee('Mapa Mensal');

        $this->actingAs($this->usuarioTerapeuta1)->get('/agenda')->assertOk();
        $this->actingAs($this->usuarioCoordenacao)->get('/agenda')->assertOk();
    }

    // 2. Bloqueio de acesso para usuários sem permissão (403 ou redirect)
    public function test_bloqueio_de_acesso_para_usuarios_sem_permissao(): void
    {
        $this->get('/agenda')->assertRedirect('/login');
        $this->actingAs($this->usuarioSemPermissao)->get('/agenda')->assertForbidden();
    }

    // 3. Alternância entre as abas (Dia, Grade, Mês) atualizando o estado do componente
    public function test_alternancia_entre_abas_atualiza_estado_do_componente(): void
    {
        Livewire::actingAs($this->usuarioRecepcao, 'web')
            ->test(AgendaOperacional::class)
            ->assertSet('aba', 'dia')
            ->assertSee('Visualização Diária')
            ->call('definirAba', 'grade')
            ->assertSet('aba', 'grade')
            ->assertSee('Semana de')
            ->call('definirAba', 'mensal')
            ->assertSet('aba', 'mensal')
            ->assertSee('Mês Atual');
    }

    // 4. Contadores da Agenda do Dia refletindo corretamente os agendamentos
    public function test_contadores_da_agenda_do_dia_refletem_agendamentos(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $paciente1 = $this->criarPaciente('Paciente Um', '11111111111');
        $paciente2 = $this->criarPaciente('Paciente Dois', '22222222222');
        $paciente3 = $this->criarPaciente('Paciente Três', '33333333333');

        $ag1 = Agendamento::create([
            'paciente_id' => $paciente1->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'AGENDADO',
        ]);
        $ag1->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        $ag2 = Agendamento::create([
            'paciente_id' => $paciente2->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '09:00',
            'hora_fim' => '09:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'PRESENTE',
            'chegada_em' => now(),
        ]);
        $ag2->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        $ag3 = Agendamento::create([
            'paciente_id' => $paciente3->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '10:00',
            'hora_fim' => '10:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'ATENDIDO',
            'chegada_em' => now()->subMinutes(60),
        ]);
        $ag3->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        Livewire::actingAs($this->usuarioRecepcao, 'web')
            ->test(AgendaOperacional::class, ['data' => $hoje])
            ->assertViewHas('contadoresDia', function ($contadores) {
                return $contadores['total'] === 3
                    && $contadores['agendados'] === 1
                    && $contadores['presentes'] === 1
                    && $contadores['atendidos'] === 1;
            });
    }

    // 5. Filtro por período (manhã, tarde, noite, todos) filtrando corretamente
    public function test_filtro_por_periodo_filtra_corretamente(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $paciente1 = $this->criarPaciente('Paciente Manhã', '11111111111');
        $paciente2 = $this->criarPaciente('Paciente Tarde', '22222222222');

        $agManha = Agendamento::create([
            'paciente_id' => $paciente1->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'AGENDADO',
        ]);
        $agManha->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        $agTarde = Agendamento::create([
            'paciente_id' => $paciente2->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '14:00',
            'hora_fim' => '14:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'AGENDADO',
        ]);
        $agTarde->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        Livewire::actingAs($this->usuarioRecepcao, 'web')
            ->test(AgendaOperacional::class, ['data' => $hoje])
            ->assertSee('Paciente Manhã')
            ->assertSee('Paciente Tarde')
            ->call('definirPeriodo', 'MANHA')
            ->assertSee('Paciente Manhã')
            ->assertDontSee('Paciente Tarde')
            ->call('definirPeriodo', 'TARDE')
            ->assertDontSee('Paciente Manhã')
            ->assertSee('Paciente Tarde');
    }

    // 6. Filtro por profissional e sala funcionando isoladamente e combinados
    public function test_filtro_por_profissional_e_sala(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $p1 = $this->criarPaciente('Paciente Fono Sala1', '11111111111');
        $p2 = $this->criarPaciente('Paciente TO Sala2', '22222222222');

        $ag1 = Agendamento::create([
            'paciente_id' => $p1->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'AGENDADO',
        ]);
        $ag1->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        $ag2 = Agendamento::create([
            'paciente_id' => $p2->id,
            'sala_id' => $this->sala2->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '09:00',
            'hora_fim' => '09:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'AGENDADO',
        ]);
        $ag2->profissionais()->attach($this->terapeuta2->id, ['papel' => 'PRINCIPAL']);

        Livewire::actingAs($this->usuarioRecepcao, 'web')
            ->test(AgendaOperacional::class, ['data' => $hoje])
            ->set('filtroProfissionalId', (string) $this->terapeuta1->id)
            ->assertSee('Paciente Fono Sala1')
            ->assertDontSee('Paciente TO Sala2')
            ->set('filtroProfissionalId', '')
            ->set('filtroSalaId', (string) $this->sala2->id)
            ->assertDontSee('Paciente Fono Sala1')
            ->assertSee('Paciente TO Sala2');
    }

    // 7. Confirmar presença na Agenda do Dia atualiza status para PRESENTE e registra chegada_em
    public function test_confirmar_presenca_atualiza_status_para_presente_e_registra_chegada(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $paciente = $this->criarPaciente('Paciente Presença', '11111111111');

        $ag = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '08:30',
            'hora_fim' => '09:00',
            'dia_semana' => 'SEGUNDA',
            'status' => 'AGENDADO',
            'chegada_em' => null,
        ]);
        $ag->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        Livewire::actingAs($this->usuarioRecepcao, 'web')
            ->test(AgendaOperacional::class, ['data' => $hoje])
            ->call('confirmarPresenca', $ag->id);

        $ag->refresh();
        $this->assertSame('PRESENTE', $ag->status);
        $this->assertNotNull($ag->chegada_em);

        $this->assertDatabaseHas('logs_auditoria', [
            'user_id' => $this->usuarioRecepcao->id,
            'acao' => 'CHECKIN_PRESENCA',
            'tabela_afetada' => 'agendamentos',
            'registro_id' => $ag->id,
        ]);
    }

    // 8. Confirmar presença alimenta a lista de pacientes no PEP do(s) profissional(is)
    public function test_confirmar_presenca_alimenta_fila_pep_do_profissional(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $paciente = $this->criarPaciente('Paciente Fila PEP', '11111111111');

        $ag = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '09:30',
            'hora_fim' => '10:00',
            'dia_semana' => 'SEGUNDA',
            'status' => 'AGENDADO',
        ]);
        $ag->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        // Antes do check-in: não aparece na fila de espera confirmada do PEP
        Livewire::actingAs($this->usuarioTerapeuta1, 'web')
            ->test(ProntuarioPaciente::class)
            ->assertDontSee('Paciente Fila PEP');

        // Recepção realiza o check-in
        $ag->update(['status' => 'PRESENTE', 'chegada_em' => now()]);

        // Após check-in: aparece na fila automática do terapeuta com estado Aguardando
        Livewire::actingAs($this->usuarioTerapeuta1, 'web')
            ->test(ProntuarioPaciente::class)
            ->assertSee('Meus Pacientes / Atendimentos de Hoje')
            ->assertSee('Paciente Fila PEP')
            ->assertSee('Aguardando');
    }

    // 9. Chamar paciente na TV a partir da Agenda do Dia dispara chamada sem mudar para ATENDIDO
    public function test_chamar_paciente_dispara_chamada_sem_mudar_status_para_atendido(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $paciente = $this->criarPaciente('Paciente TV', '11111111111');

        $ag = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '10:00',
            'hora_fim' => '10:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'PRESENTE',
            'chegada_em' => now(),
        ]);
        $ag->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        Livewire::actingAs($this->usuarioRecepcao, 'web')
            ->test(AgendaOperacional::class, ['data' => $hoje])
            ->call('chamarPaciente', $ag->id);

        $ag->refresh();
        $this->assertSame('PRESENTE', $ag->status);
        $this->assertDatabaseHas('chamadas_painel', [
            'agendamento_id' => $ag->id,
            'paciente_id' => $paciente->id,
        ]);
    }

    // 10. PEP exibe a lista de pacientes do dia do profissional autenticado
    public function test_pep_exibe_lista_de_pacientes_do_dia_do_profissional(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $pacienteHelena = $this->criarPaciente('Paciente da Helena', '11111111111');
        $pacienteBeatriz = $this->criarPaciente('Paciente da Beatriz', '22222222222');

        $agHelena = Agendamento::create([
            'paciente_id' => $pacienteHelena->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'PRESENTE',
            'chegada_em' => now(),
        ]);
        $agHelena->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        $agBeatriz = Agendamento::create([
            'paciente_id' => $pacienteBeatriz->id,
            'sala_id' => $this->sala2->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '09:00',
            'hora_fim' => '09:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'PRESENTE',
            'chegada_em' => now(),
        ]);
        $agBeatriz->profissionais()->attach($this->terapeuta2->id, ['papel' => 'PRINCIPAL']);

        // Helena só deve ver o seu paciente na sua fila
        Livewire::actingAs($this->usuarioTerapeuta1, 'web')
            ->test(ProntuarioPaciente::class)
            ->assertSee('Paciente da Helena')
            ->assertDontSee('Paciente da Beatriz');

        // Beatriz só deve ver o seu paciente na sua fila
        Livewire::actingAs($this->usuarioTerapeuta2, 'web')
            ->test(ProntuarioPaciente::class)
            ->assertDontSee('Paciente da Helena')
            ->assertSee('Paciente da Beatriz');
    }

    // 11. Ação Abrir Prontuário no PEP carrega o paciente e o agendamento sem pesquisa manual
    public function test_abrir_prontuario_no_pep_carrega_paciente_e_agendamento_automaticamente(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $paciente = $this->criarPaciente('Paciente Abrir PEP', '11111111111');

        $ag = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'PRESENTE',
            'chegada_em' => now(),
        ]);
        $ag->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        $test = Livewire::actingAs($this->usuarioTerapeuta1, 'web')
            ->test(ProntuarioPaciente::class)
            ->call('abrirProntuarioAtendimento', $ag->id)
            ->assertSet('pacienteId', $paciente->id)
            ->assertSet('agendamento_id', $ag->id)
            ->assertSee('Paciente Abrir PEP')
            ->assertSee('Nova Evolução Clínica + Produção BPA-i');

        $this->assertNotNull($test->get('procedimento_id'));
    }

    // 12. Atendimento compartilhado: Profissional 1 assina -> status permanece PRESENTE e continua na fila do Profissional 2
    public function test_atendimento_compartilhado_primeira_assinatura_nao_marca_atendido(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $paciente = $this->criarPaciente('Lucas Compartilhado', '11111111111');

        $ag = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '10:00',
            'hora_fim' => '10:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'COMPARTILHADO',
            'status' => 'PRESENTE',
            'chegada_em' => now(),
        ]);
        $ag->profissionais()->attach([
            $this->terapeuta1->id => ['papel' => 'PRINCIPAL'],
            $this->terapeuta2->id => ['papel' => 'COMPARTILHADO'],
        ]);

        $evolucaoService = app(EvolucaoService::class);

        // Helena (Terapeuta 1) assina a sua evolução
        $evolucaoService->registrarEAssinar(
            agendamento: $ag,
            profissional: $this->terapeuta1,
            procedimento: $this->procedimentoFono,
            dadosClinicos: [
                'queixa_estado_atual' => 'Dificuldades na fonação e deglutição.',
                'conduta_terapeutica' => 'Exercícios miofuncionais orofaciais.',
            ],
            pinAssinatura: '1234',
        );

        $ag->refresh();
        // O status NÃO pode mudar para ATENDIDO porque a Beatriz ainda não assinou
        $this->assertSame('PRESENTE', $ag->status);

        // Beatriz (Terapeuta 2) ainda deve ver o paciente na sua fila como Aguardando
        Livewire::actingAs($this->usuarioTerapeuta2, 'web')
            ->test(ProntuarioPaciente::class)
            ->assertSee('Lucas Compartilhado')
            ->assertSee('Aguardando');
    }

    // 13. Atendimento compartilhado: Profissional 2 assina -> status muda para ATENDIDO
    public function test_atendimento_compartilhado_segunda_assinatura_marca_atendido(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $paciente = $this->criarPaciente('Lucas Compartilhado Dois', '11111111111');

        $ag = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '11:00',
            'hora_fim' => '11:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'COMPARTILHADO',
            'status' => 'PRESENTE',
            'chegada_em' => now(),
        ]);
        $ag->profissionais()->attach([
            $this->terapeuta1->id => ['papel' => 'PRINCIPAL'],
            $this->terapeuta2->id => ['papel' => 'COMPARTILHADO'],
        ]);

        $evolucaoService = app(EvolucaoService::class);

        // Helena assina
        $evolucaoService->registrarEAssinar(
            agendamento: $ag,
            profissional: $this->terapeuta1,
            procedimento: $this->procedimentoFono,
            dadosClinicos: [
                'queixa_estado_atual' => 'Dificuldades na fonação.',
                'conduta_terapeutica' => 'Exercícios miofuncionais.',
            ],
            pinAssinatura: '1234',
        );

        $ag->refresh();
        $this->assertSame('PRESENTE', $ag->status);

        // Agora Beatriz assina
        $evolucaoService->registrarEAssinar(
            agendamento: $ag,
            profissional: $this->terapeuta2,
            procedimento: $this->procedimentoTo,
            dadosClinicos: [
                'queixa_estado_atual' => 'Busca proprioceptiva intensa.',
                'conduta_terapeutica' => 'Treino em circuito sensorial.',
            ],
            pinAssinatura: '5678',
        );

        $ag->refresh();
        // Agora que TODOS os profissionais assinaram, o status deve ser ATENDIDO
        $this->assertSame('ATENDIDO', $ag->status);
    }

    // 14. Atendimento compartilhado: 2 registros de produção BPA-i gerados com CBOs e procedimentos respectivos
    public function test_atendimento_compartilhado_gera_producoes_bpai_individuais(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $paciente = $this->criarPaciente('Paciente Producao Dupla', '11111111111');

        $ag = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '14:00',
            'hora_fim' => '14:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'COMPARTILHADO',
            'status' => 'PRESENTE',
            'chegada_em' => now(),
        ]);
        $ag->profissionais()->attach([
            $this->terapeuta1->id => ['papel' => 'PRINCIPAL'],
            $this->terapeuta2->id => ['papel' => 'COMPARTILHADO'],
        ]);

        $evolucaoService = app(EvolucaoService::class);

        $evolucaoService->registrarEAssinar(
            agendamento: $ag,
            profissional: $this->terapeuta1,
            procedimento: $this->procedimentoFono,
            dadosClinicos: [
                'queixa_estado_atual' => 'Queixa Fono.',
                'conduta_terapeutica' => 'Conduta Fono.',
            ],
            pinAssinatura: '1234',
        );

        $evolucaoService->registrarEAssinar(
            agendamento: $ag,
            profissional: $this->terapeuta2,
            procedimento: $this->procedimentoTo,
            dadosClinicos: [
                'queixa_estado_atual' => 'Queixa TO.',
                'conduta_terapeutica' => 'Conduta TO.',
            ],
            pinAssinatura: '5678',
        );

        $this->assertSame(2, ProducaoBpai::where('paciente_id', $paciente->id)->count());

        $this->assertDatabaseHas('producoes_bpai', [
            'paciente_id' => $paciente->id,
            'profissional_id' => $this->terapeuta1->id,
            'cbo_executante' => '223810',
            'procedimento_id' => $this->procedimentoFono->id,
        ]);

        $this->assertDatabaseHas('producoes_bpai', [
            'paciente_id' => $paciente->id,
            'profissional_id' => $this->terapeuta2->id,
            'cbo_executante' => '223905',
            'procedimento_id' => $this->procedimentoTo->id,
        ]);
    }

    // 15. Atendimento individual: assinatura única muda status imediatamente para ATENDIDO
    public function test_atendimento_individual_assinatura_muda_status_imediatamente_para_atendido(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $paciente = $this->criarPaciente('Paciente Individual', '11111111111');

        $ag = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '15:00',
            'hora_fim' => '15:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
            'chegada_em' => now(),
        ]);
        $ag->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        $evolucaoService = app(EvolucaoService::class);
        $evolucaoService->registrarEAssinar(
            agendamento: $ag,
            profissional: $this->terapeuta1,
            procedimento: $this->procedimentoFono,
            dadosClinicos: [
                'queixa_estado_atual' => 'Atendimento individual fono.',
                'conduta_terapeutica' => 'Treino articulatório.',
            ],
            pinAssinatura: '1234',
        );

        $ag->refresh();
        $this->assertSame('ATENDIDO', $ag->status);
    }

    // 16. Alertas de absenteísmo exibidos na Agenda do Dia sem queries N+1
    public function test_alertas_de_absenteismo_exibidos_sem_n_mais_um(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();
        $pacienteFaltoso = $this->criarPaciente('Paciente Faltoso', '11111111111');

        // Cria 3 faltas consecutivas no histórico recente
        for ($i = 3; $i >= 1; $i--) {
            Agendamento::create([
                'paciente_id' => $pacienteFaltoso->id,
                'sala_id' => $this->sala1->id,
                'data_atendimento' => CarbonImmutable::now()->subDays($i)->toDateString(),
                'hora_inicio' => '08:00',
                'hora_fim' => '08:30',
                'dia_semana' => 'SEGUNDA',
                'status' => 'FALTA_INJUSTIFICADA',
            ]);
        }

        $agHoje = Agendamento::create([
            'paciente_id' => $pacienteFaltoso->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'AGENDADO',
        ]);
        $agHoje->profissionais()->attach($this->terapeuta1->id, ['papel' => 'PRINCIPAL']);

        Livewire::actingAs($this->usuarioRecepcao, 'web')
            ->test(AgendaOperacional::class, ['data' => $hoje])
            ->assertSee('Absenteísmo: 3 faltas consecutivas');
    }
}

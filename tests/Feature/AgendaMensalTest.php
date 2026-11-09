<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\AgendaMensal;
use App\Livewire\GradeSemanal;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AgendaMensalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            SalaSeeder::class,
        ]);
    }

    public function test_rota_agenda_mensal_exige_autenticacao(): void
    {
        $this->get('/agenda/mensal')->assertRedirect('/login');
    }

    public function test_rota_agenda_mensal_respeita_rbac_e_policy(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $recepcao = User::factory()->create(['ativo' => true]);
        $recepcao->assignRole('recepcao');

        $terapeuta = User::factory()->create(['ativo' => true]);
        $terapeuta->assignRole('terapeuta');

        $faturamento = User::factory()->create(['ativo' => true]);
        $faturamento->assignRole('faturamento');

        $usuarioSemPermissao = User::factory()->create(['ativo' => true]);

        $this->actingAs($coordenador)->get('/agenda/mensal')->assertRedirect('/agenda?aba=mensal');
        $this->actingAs($recepcao)->get('/agenda/mensal')->assertRedirect('/agenda?aba=mensal');
        $this->actingAs($terapeuta)->get('/agenda/mensal')->assertRedirect('/agenda?aba=mensal');
        $this->actingAs($faturamento)->get('/agenda/mensal')->assertRedirect('/agenda?aba=mensal');
        $this->actingAs($usuarioSemPermissao)->get('/agenda/mensal')->assertForbidden();
    }

    public function test_renderizacao_inicial_com_mes_atual_e_cards_de_indicadores(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $response = $this->actingAs($coordenador)->followingRedirects()->get('/agenda/mensal');
        $response->assertOk();
        $response->assertSee('Mapa Mensal');
        $response->assertSee('Volume de Agendamentos');
        $response->assertSee('Atendimentos Realizados');
        $response->assertSee('Faltas &amp; Absenteísmo', false);
        $response->assertSee('Dias com Agenda');
        $response->assertSee('Visualização Diária (Operacional)');
        $response->assertSee('Grade Semanal (Planejamento)');
    }

    public function test_agendamentos_cancelados_sao_excluidos_dos_totais_ativos(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $paciente = Paciente::create([
            'nome' => 'Ana Clara Teste',
            'cns' => CnsValido::gerarDefinitivo('10120230344'),
            'data_nascimento' => '2015-04-10',
            'sexo' => 'F',
            'nome_mae' => 'Maria Teste',
            'municipio_ibge' => '270430',
            'cid_principal' => 'G800',
            'modalidades_reabilitacao' => ['FISICA'],
            'ativo' => true,
        ]);

        $sala = Sala::firstOrFail();
        $hoje = CarbonImmutable::now();
        $dataNoMes = $hoje->startOfMonth()->addDays(5)->toDateString();

        // 1 PRESENTE, 1 ATENDIDO, 1 AGENDADO, 1 FALTA_JUSTIFICADA, 1 FALTA_INJUSTIFICADA, 1 CANCELADO
        $statusList = [
            'PRESENTE',
            'ATENDIDO',
            'AGENDADO',
            'FALTA_JUSTIFICADA',
            'FALTA_INJUSTIFICADA',
            'CANCELADO',
        ];

        foreach ($statusList as $idx => $st) {
            $horaInicio = sprintf('%02d:00', 8 + $idx);
            $horaFim = sprintf('%02d:30', 8 + $idx);
            Agendamento::create([
                'paciente_id' => $paciente->id,
                'sala_id' => $sala->id,
                'data_atendimento' => $dataNoMes,
                'hora_inicio' => $horaInicio,
                'hora_fim' => $horaFim,
                'dia_semana' => 'SEGUNDA',
                'tipo_atendimento' => 'INDIVIDUAL',
                'status' => $st,
                'recorrente' => false,
            ]);
        }

        Livewire::actingAs($coordenador)
            ->test(AgendaMensal::class, [
                'mes' => (int) $hoje->format('n'),
                'ano' => (int) $hoje->format('Y'),
            ])
            ->assertViewHas('metricasMes', function (array $metricas): bool {
                // Total ativo = 5 (exclui o cancelado)
                // Realizados = 2 (PRESENTE + ATENDIDO)
                // Faltas = 2 (FALTA_JUSTIFICADA + FALTA_INJUSTIFICADA)
                // Agendados = 1 (AGENDADO)
                // Cancelados = 1
                return $metricas['total'] === 5
                    && $metricas['realizados'] === 2
                    && $metricas['faltas'] === 2
                    && $metricas['agendados'] === 1
                    && $metricas['cancelados'] === 1
                    && $metricas['diasComAgenda'] === 1
                    && $metricas['taxaComparecimento'] === 50
                    && $metricas['taxaAbsenteismo'] === 50;
            });
    }

    public function test_navegacao_entre_meses_e_retorno_ao_mes_atual(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $componente = Livewire::actingAs($coordenador)
            ->test(AgendaMensal::class, ['mes' => 5, 'ano' => 2026])
            ->assertSet('mes', 5)
            ->assertSet('ano', 2026);

        // Mês anterior (Maio -> Abril)
        $componente->call('mesAnterior')
            ->assertSet('mes', 4)
            ->assertSet('ano', 2026);

        // Próximo mês duas vezes (Abril -> Maio -> Junho)
        $componente->call('proximoMes')
            ->call('proximoMes')
            ->assertSet('mes', 6)
            ->assertSet('ano', 2026);

        // Retorno ao mês atual
        $hoje = CarbonImmutable::now();
        $componente->call('irParaMesAtual')
            ->assertSet('mes', (int) $hoje->format('n'))
            ->assertSet('ano', (int) $hoje->format('Y'));
    }

    public function test_filtros_por_especialidade_profissional_e_turno(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $userFisio = User::factory()->create(['ativo' => true]);
        $userFisio->assignRole('terapeuta');
        $profFisio = Profissional::create([
            'user_id' => $userFisio->id,
            'nome' => 'Dr. Lucas Fisioterapeuta',
            'cpf' => '11122233344',
            'cns' => CnsValido::gerarDefinitivo('11122233344'),
            'registro_conselho' => 'CREFITO 1234',
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);

        $userPsico = User::factory()->create(['ativo' => true]);
        $userPsico->assignRole('terapeuta');
        $profPsico = Profissional::create([
            'user_id' => $userPsico->id,
            'nome' => 'Dra. Camila Psicóloga',
            'cpf' => '22233344455',
            'cns' => CnsValido::gerarDefinitivo('22233344455'),
            'registro_conselho' => 'CRP 5678',
            'cbo_padrao' => '251510',
            'especialidade' => 'Psicologia',
            'pin_assinatura_hash' => Hash::make('5678'),
            'ativo' => true,
        ]);

        $paciente = Paciente::create([
            'nome' => 'Carlos Eduardo Lima',
            'cns' => CnsValido::gerarDefinitivo('33344455566'),
            'data_nascimento' => '2012-08-15',
            'sexo' => 'M',
            'nome_mae' => 'Sandra Lima',
            'municipio_ibge' => '270430',
            'cid_principal' => 'F840',
            'modalidades_reabilitacao' => ['INTELECTUAL'],
            'ativo' => true,
        ]);

        $sala = Sala::firstOrFail();

        // Agendamento 1: Fisioterapia pela Manhã (09:00) em 10/11/2026
        $agendamentoFisio = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => '2026-11-10',
            'hora_inicio' => '09:00',
            'hora_fim' => '09:30',
            'dia_semana' => 'TERCA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
            'recorrente' => false,
        ]);
        $agendamentoFisio->profissionais()->attach($profFisio->id, ['papel' => 'PRINCIPAL']);

        // Agendamento 2: Psicologia pela Tarde (14:00) em 12/11/2026
        $agendamentoPsico = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => '2026-11-12',
            'hora_inicio' => '14:00',
            'hora_fim' => '14:30',
            'dia_semana' => 'QUINTA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
            'recorrente' => false,
        ]);
        $agendamentoPsico->profissionais()->attach($profPsico->id, ['papel' => 'PRINCIPAL']);

        $test = Livewire::actingAs($coordenador)
            ->test(AgendaMensal::class, ['mes' => 11, 'ano' => 2026]);

        // Sem filtros: 2 atendimentos
        $test->assertViewHas('metricasMes', fn ($m) => $m['total'] === 2);

        // Filtro por especialidade 'Fisioterapia' -> 1
        $test->set('filtroEspecialidade', 'Fisioterapia')
            ->assertViewHas('metricasMes', fn ($m) => $m['total'] === 1 && $m['agendados'] === 1);

        // Filtro por especialidade 'Psicologia' -> 1 (realizado)
        $test->set('filtroEspecialidade', 'Psicologia')
            ->assertViewHas('metricasMes', fn ($m) => $m['total'] === 1 && $m['realizados'] === 1);

        // Filtro por profissional Fisio -> 1
        $test->set('filtroEspecialidade', '')
            ->set('filtroProfissionalId', (string) $profFisio->id)
            ->assertViewHas('metricasMes', fn ($m) => $m['total'] === 1);

        // Filtro por turno MANHA -> 1
        $test->set('filtroProfissionalId', '')
            ->set('filtroTurno', 'MANHA')
            ->assertViewHas('metricasMes', fn ($m) => $m['total'] === 1);

        // Filtro por turno TARDE -> 1
        $test->set('filtroTurno', 'TARDE')
            ->assertViewHas('metricasMes', fn ($m) => $m['total'] === 1);

        // Limpar filtros -> 2
        $test->call('limparFiltros')
            ->assertSet('filtroEspecialidade', '')
            ->assertSet('filtroProfissionalId', '')
            ->assertSet('filtroTurno', '')
            ->assertViewHas('metricasMes', fn ($m) => $m['total'] === 2);
    }

    public function test_dias_calendario_calcula_meses_com_28_29_30_31_dias(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        // Fevereiro 2024 (Ano bissexto: 29 dias)
        Livewire::actingAs($coordenador)
            ->test(AgendaMensal::class, ['mes' => 2, 'ano' => 2024])
            ->assertViewHas('diasCalendario', function (array $dias): bool {
                $diasDoMes = array_filter($dias, fn ($d) => $d['ehMesAtual']);

                // Começa na segunda-feira
                return count($diasDoMes) === 29 && $dias[0]['diaSemana'] === 1;
            });

        // Fevereiro 2025 (Não bissexto: 28 dias)
        Livewire::actingAs($coordenador)
            ->test(AgendaMensal::class, ['mes' => 2, 'ano' => 2025])
            ->assertViewHas('diasCalendario', function (array $dias): bool {
                $diasDoMes = array_filter($dias, fn ($d) => $d['ehMesAtual']);

                return count($diasDoMes) === 28;
            });

        // Novembro 2026 (30 dias)
        Livewire::actingAs($coordenador)
            ->test(AgendaMensal::class, ['mes' => 11, 'ano' => 2026])
            ->assertViewHas('diasCalendario', function (array $dias): bool {
                $diasDoMes = array_filter($dias, fn ($d) => $d['ehMesAtual']);

                return count($diasDoMes) === 30;
            });

        // Outubro 2026 (31 dias)
        Livewire::actingAs($coordenador)
            ->test(AgendaMensal::class, ['mes' => 10, 'ano' => 2026])
            ->assertViewHas('diasCalendario', function (array $dias): bool {
                $diasDoMes = array_filter($dias, fn ($d) => $d['ehMesAtual']);

                return count($diasDoMes) === 31;
            });
    }

    public function test_privacidade_lgpd_nao_expoe_nomes_cns_ou_dados_clinicos_no_mapa_mensal(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $cnsConfidencial = CnsValido::gerarDefinitivo('88899900011');
        $paciente = Paciente::create([
            'nome' => 'Paciente Estritamente Confidencial Silva',
            'cns' => $cnsConfidencial,
            'data_nascimento' => '2010-01-01',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Confidencial',
            'municipio_ibge' => '270430',
            'cid_principal' => 'G801',
            'modalidades_reabilitacao' => ['FISICA'],
            'ativo' => true,
        ]);

        $sala = Sala::firstOrFail();
        Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => '2026-11-16',
            'hora_inicio' => '10:00',
            'hora_fim' => '10:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
            'observacao' => 'Diagnóstico reservado do paciente em reabilitação',
            'recorrente' => false,
        ]);

        $response = $this->actingAs($coordenador)->followingRedirects()->get('/agenda/mensal?mes=11&ano=2026');

        $response->assertOk();
        // Não deve exibir dados sensíveis do paciente no mapa gerencial
        $response->assertDontSee('Paciente Estritamente Confidencial Silva');
        $response->assertDontSee($cnsConfidencial);
        $response->assertDontSee('Diagnóstico reservado do paciente em reabilitação');
    }

    public function test_integracao_abrir_dia_carrega_grade_semanal_com_parametro_data(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        // Ao abrir a grade semanal com data específica de quarta-feira (18/11/2026)
        $response = $this->actingAs($coordenador)->followingRedirects()->get('/grade?data=2026-11-18');
        $response->assertOk();
        $response->assertSee('18/11/2026');

        Livewire::actingAs($coordenador)
            ->test(GradeSemanal::class, ['data' => '2026-11-18'])
            ->assertSet('diaSemana', 'QUARTA')
            ->assertSet('dataReferencia', '2026-11-18');

        // Ao acessar com ?novo=1, abre automaticamente o modal de agendamento
        Livewire::actingAs($coordenador)
            ->withQueryParams(['novo' => '1'])
            ->test(GradeSemanal::class)
            ->assertSet('modalAberto', true);
    }

    public function test_menu_lateral_exibe_agenda_operacional_unificada(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $response = $this->actingAs($coordenador)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Agenda Operacional');
        $response->assertSee(route('agenda.operacional'));
    }
}

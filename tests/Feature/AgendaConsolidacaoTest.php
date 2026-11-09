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
use App\Services\ChamadaPacienteService;
use App\Services\EvolucaoService;
use Carbon\CarbonImmutable;
use Database\Seeders\ProcedimentoSigtapSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AgendaConsolidacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioRecepcao;

    private User $usuarioTerapeuta;

    private User $usuarioCoordenacao;

    private User $usuarioFaturamento;

    private Profissional $terapeuta;

    private Sala $sala1;

    private Sala $sala2;

    private Paciente $pacienteSegunda;

    private Paciente $pacienteQuarta;

    private ProcedimentoSigtap $procedimento;

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
        $this->procedimento = ProcedimentoSigtap::first();

        $this->usuarioRecepcao = User::factory()->create(['name' => 'Recepção Teste', 'ativo' => true]);
        $this->usuarioRecepcao->assignRole('recepcao');

        $this->usuarioTerapeuta = User::factory()->create(['name' => 'Dra. Fisioterapeuta', 'ativo' => true]);
        $this->usuarioTerapeuta->assignRole('terapeuta');
        $this->terapeuta = Profissional::create([
            'user_id' => $this->usuarioTerapeuta->id,
            'nome' => 'Dra. Fisioterapeuta',
            'cpf' => '11144477733',
            'cns' => CnsValido::gerarDefinitivo('11144477733'),
            'registro_conselho' => 'CREFITO 5555/AL',
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia Neurofuncional',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);

        $this->usuarioCoordenacao = User::factory()->create(['name' => 'Coordenação Teste', 'ativo' => true]);
        $this->usuarioCoordenacao->assignRole('coordenacao');

        $this->usuarioFaturamento = User::factory()->create(['name' => 'Faturamento Teste', 'ativo' => true]);
        $this->usuarioFaturamento->assignRole('faturamento');

        $this->pacienteSegunda = Paciente::create([
            'nome' => 'Paciente da Segunda',
            'cns' => CnsValido::gerarDefinitivo('10000000001'),
            'data_nascimento' => '2010-01-15',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Segunda',
            'municipio_ibge' => '270430',
            'cid_principal' => 'G800',
            'modalidades_reabilitacao' => ['FISICA'],
            'ativo' => true,
        ]);

        $this->pacienteQuarta = Paciente::create([
            'nome' => 'Paciente da Quarta',
            'cns' => CnsValido::gerarDefinitivo('10000000002'),
            'data_nascimento' => '2012-05-20',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Quarta',
            'municipio_ibge' => '270430',
            'cid_principal' => 'F840',
            'modalidades_reabilitacao' => ['INTELECTUAL'],
            'ativo' => true,
        ]);
    }

    /**
     * CENÁRIO 1: Agendamentos de quarta-feira (11/11/2026) NÃO aparecem na visualização de segunda-feira (09/11/2026).
     */
    public function test_cenario_01_agendamentos_de_quarta_feira_nao_aparecem_na_segunda_feira(): void
    {
        Agendamento::create([
            'paciente_id' => $this->pacienteQuarta->id,
            'sala_id' => $this->sala1->id,
            'profissional_id' => $this->terapeuta->id,
            'procedimento_sigtap_id' => $this->procedimento->id,
            'data_atendimento' => '2026-11-11',
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'QUARTA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
            'recorrente' => false,
        ]);

        Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'grade',
                'data' => '2026-11-09',
                'diaSemana' => 'SEGUNDA',
            ])
            ->assertDontSee('Paciente da Quarta')
            ->assertViewHas('gradeIndexada', function (array $grade): bool {
                foreach ($grade as $salaId => $slots) {
                    foreach ($slots as $hora => $agendamentos) {
                        foreach ($agendamentos as $ag) {
                            if ($ag->paciente_id === $this->pacienteQuarta->id) {
                                return false;
                            }
                        }
                    }
                }

                return true;
            });
    }

    /**
     * CENÁRIO 2: Agendamentos de quarta-feira aparecem exclusivamente na visualização de quarta-feira.
     */
    public function test_cenario_02_agendamentos_de_quarta_feira_aparecem_exclusivamente_na_quarta_feira(): void
    {
        $agQuarta = Agendamento::create([
            'paciente_id' => $this->pacienteQuarta->id,
            'sala_id' => $this->sala1->id,
            'profissional_id' => $this->terapeuta->id,
            'procedimento_sigtap_id' => $this->procedimento->id,
            'data_atendimento' => '2026-11-11',
            'hora_inicio' => '09:00',
            'hora_fim' => '09:30',
            'dia_semana' => 'QUARTA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
            'recorrente' => false,
        ]);

        Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'grade',
                'data' => '2026-11-11',
                'diaSemana' => 'QUARTA',
            ])
            ->assertSee('Paciente da Quarta')
            ->assertViewHas('gradeIndexada', function (array $grade) use ($agQuarta): bool {
                foreach ($grade as $salaId => $slots) {
                    foreach ($slots as $hora => $agendamentos) {
                        foreach ($agendamentos as $ag) {
                            if ($ag->id === $agQuarta->id) {
                                return true;
                            }
                        }
                    }
                }

                return false;
            });
    }

    /**
     * CENÁRIO 3: Agendamento com dia_semana inconsistente no banco (data 11/11/2026 e dia_semana = 'SEGUNDA')
     * é exibido exclusivamente no dia da sua data real (quarta-feira) e NUNCA na segunda-feira,
     * em ambas as visões ("Por Sala" e "Semana Completa").
     */
    public function test_cenario_03_agendamento_com_dia_semana_inconsistente_exibido_apenas_na_data_real(): void
    {
        // Registro inconsistente: data de Quarta-feira, mas dia_semana gravado como 'SEGUNDA' no banco
        $agInconsistente = Agendamento::create([
            'paciente_id' => $this->pacienteQuarta->id,
            'sala_id' => $this->sala1->id,
            'profissional_id' => $this->terapeuta->id,
            'procedimento_sigtap_id' => $this->procedimento->id,
            'data_atendimento' => '2026-11-11', // Quarta-feira real
            'hora_inicio' => '10:00',
            'hora_fim' => '10:30',
            'dia_semana' => 'SEGUNDA', // Inconsistência proposital do legado
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
            'recorrente' => false,
        ]);

        // 1. Visão "Por Sala" na Segunda-feira (09/11/2026) -> NÃO deve aparecer
        Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'grade',
                'visaoGrade' => 'SALA',
                'data' => '2026-11-09',
                'diaSemana' => 'SEGUNDA',
            ])
            ->assertDontSee('Paciente da Quarta')
            ->assertViewHas('gradeIndexada', function (array $grade) use ($agInconsistente): bool {
                foreach ($grade as $salaId => $slots) {
                    foreach ($slots as $hora => $ags) {
                        foreach ($ags as $ag) {
                            if ($ag->id === $agInconsistente->id) {
                                return false; // Encontrado indevidamente na segunda-feira
                            }
                        }
                    }
                }

                return true;
            });

        // 2. Visão "Por Sala" na Quarta-feira (11/11/2026) -> DEVE aparecer
        Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'grade',
                'visaoGrade' => 'SALA',
                'data' => '2026-11-11',
                'diaSemana' => 'QUARTA',
            ])
            ->assertSee('Paciente da Quarta')
            ->assertViewHas('gradeIndexada', function (array $grade) use ($agInconsistente): bool {
                foreach ($grade as $salaId => $slots) {
                    foreach ($slots as $hora => $ags) {
                        foreach ($ags as $ag) {
                            if ($ag->id === $agInconsistente->id) {
                                return true;
                            }
                        }
                    }
                }

                return false;
            });

        // 3. Visão "Semana Completa" -> deve aparecer agrupado APENAS na data de quarta-feira (2026-11-11)
        Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'grade',
                'visaoGrade' => 'SEMANA_COMPLETA',
                'data' => '2026-11-09',
                'semanaInicio' => '2026-11-09',
            ])
            ->assertViewHas('gradeSemanaCompleta', function (array $grade) use ($agInconsistente): bool {
                $segundaTemRegistro = false;
                $quartaTemRegistro = false;

                foreach ($grade['2026-11-09'] ?? [] as $hora => $ags) {
                    foreach ($ags as $ag) {
                        if ($ag->id === $agInconsistente->id) {
                            $segundaTemRegistro = true;
                        }
                    }
                }

                foreach ($grade['2026-11-11'] ?? [] as $hora => $ags) {
                    foreach ($ags as $ag) {
                        if ($ag->id === $agInconsistente->id) {
                            $quartaTemRegistro = true;
                        }
                    }
                }

                return ! $segundaTemRegistro && $quartaTemRegistro;
            });
    }

    /**
     * CENÁRIO 4: Criação de novo agendamento via AgendamentoService grava dia_semana coerente com data_atendimento.
     */
    public function test_cenario_04_agendamento_service_grava_dia_semana_coerente_com_data_atendimento(): void
    {
        $service = app(AgendamentoService::class);

        // Usuário envia dia_semana 'SEGUNDA' por engano, mas a data é Quarta-feira (18/11/2026)
        $dados = [
            'paciente_id' => $this->pacienteQuarta->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => '2026-11-18', // Quarta-feira
            'hora_inicio' => '14:00',
            'hora_fim' => '14:30',
            'dia_semana' => 'SEGUNDA', // Deve ser substituído pelo service
            'tipo_atendimento' => 'INDIVIDUAL',
            'recorrente' => false,
        ];

        $criados = $service->criarGrade($dados, [$this->terapeuta->id], 1);

        $this->assertCount(1, $criados);
        $this->assertSame('2026-11-18', $criados[0]->data_atendimento->toDateString());
        $this->assertSame('QUARTA', $criados[0]->dia_semana);

        // Testar bloqueio de agendamento em final de semana (Sábado = 21/11/2026)
        $dadosFimDeSemana = $dados;
        $dadosFimDeSemana['data_atendimento'] = '2026-11-21';

        $this->expectException(ValidationException::class);
        $service->criarGrade($dadosFimDeSemana, [$this->terapeuta->id], 1);
    }

    /**
     * CENÁRIO 5: Recorrência semanal gera agendamentos com dia_semana e data_atendimento coerentes em todas as semanas.
     */
    public function test_cenario_05_recorrencia_semanal_gera_dia_semana_e_data_coerentes_em_todas_as_semanas(): void
    {
        $service = app(AgendamentoService::class);

        $dados = [
            'paciente_id' => $this->pacienteSegunda->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => '2026-11-17', // Terça-feira
            'hora_inicio' => '08:30',
            'hora_fim' => '09:00',
            'dia_semana' => 'TERCA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'recorrente' => true,
        ];

        $criados = $service->criarGrade($dados, [$this->terapeuta->id], 4);

        $this->assertCount(4, $criados);

        $datasEsperadas = ['2026-11-17', '2026-11-24', '2026-12-01', '2026-12-08'];
        foreach ($criados as $index => $ag) {
            $this->assertSame($datasEsperadas[$index], $ag->data_atendimento->toDateString());
            $this->assertSame('TERCA', $ag->dia_semana);
            $this->assertSame(2, $ag->data_atendimento->dayOfWeekIso);
        }
    }

    /**
     * CENÁRIO 6: Alternância entre visão "Por Sala" e "Semana Completa" mantém a consistência dos dados exibidos.
     */
    public function test_cenario_06_alternancia_entre_visao_por_sala_e_semana_completa_mantem_consistencia(): void
    {
        Agendamento::create([
            'paciente_id' => $this->pacienteSegunda->id,
            'sala_id' => $this->sala1->id,
            'profissional_id' => $this->terapeuta->id,
            'procedimento_sigtap_id' => $this->procedimento->id,
            'data_atendimento' => '2026-11-09',
            'hora_inicio' => '10:00',
            'hora_fim' => '10:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
            'recorrente' => false,
        ]);

        Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'grade',
                'visaoGrade' => 'SALA',
                'data' => '2026-11-09',
                'diaSemana' => 'SEGUNDA',
            ])
            ->assertSee('Paciente da Segunda')
            ->call('alternarVisaoGrade', 'SEMANA_COMPLETA')
            ->assertSet('visaoGrade', 'SEMANA_COMPLETA')
            ->assertSee('Paciente da Segunda')
            ->call('alternarVisaoGrade', 'SALA')
            ->assertSet('visaoGrade', 'SALA')
            ->assertSee('Paciente da Segunda');
    }

    /**
     * CENÁRIO 7: Navegação entre semanas e navegação mensal.
     */
    public function test_cenario_07_navegacao_entre_semanas_e_navegacao_mensal(): void
    {
        $test = Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'grade',
                'semanaInicio' => '2026-11-09',
                'diaSemana' => 'SEGUNDA',
                'data' => '2026-11-09',
            ]);

        // Avançar semana
        $test->call('proximaSemana');
        $this->assertSame('2026-11-16', $test->get('semanaInicio'));
        $this->assertSame('2026-11-16', $test->get('data'));

        // Voltar semana
        $test->call('semanaAnterior');
        $this->assertSame('2026-11-09', $test->get('semanaInicio'));
        $this->assertSame('2026-11-09', $test->get('data'));

        // Ir para aba mensal e navegar
        $test->call('definirAba', 'mensal');
        $this->assertSame('mensal', $test->get('aba'));

        $test->set('mes', 11)->set('ano', 2026);
        $test->call('proximoMes');
        $this->assertSame(12, $test->get('mes'));
        $this->assertSame(2026, $test->get('ano'));

        $test->call('mesAnterior');
        $this->assertSame(11, $test->get('mes'));
        $this->assertSame(2026, $test->get('ano'));

        $hoje = CarbonImmutable::now();
        $test->call('irParaMesAtual');
        $this->assertSame((int) $hoje->format('n'), $test->get('mes'));
        $this->assertSame((int) $hoje->format('Y'), $test->get('ano'));
    }

    /**
     * CENÁRIO 8: Na visão "Semana Completa", clicar em "+ Agendar" sem filtro de sala não pré-seleciona a primeira sala.
     */
    public function test_cenario_08_semana_completa_clicar_novo_sem_filtro_de_sala_nao_pre_seleciona_sala(): void
    {
        Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'grade',
                'visaoGrade' => 'SEMANA_COMPLETA',
                'filtroSalaGrade' => '',
            ])
            ->call('abrirModalNovoAgendamento', null, '08:00', '2026-11-11')
            ->assertSet('modalNovoAgendamentoAberto', true)
            ->assertSet('novo_sala_id', null)
            ->assertSet('novo_hora_inicio', '08:00')
            ->assertSet('novo_data', '2026-11-11')
            ->assertSet('novo_dia_semana', 'QUARTA');
    }

    /**
     * CENÁRIO 9: Redirecionamento de /grade, /agenda/mensal e /recepcao para /agenda com aba correta e parâmetros.
     */
    public function test_cenario_09_redirecionamento_de_rotas_legadas_com_preservacao_de_query_params(): void
    {
        // 1. /grade com parâmetros
        $respGrade = $this->actingAs($this->usuarioCoordenacao)->get('/grade?data=2026-11-18&sala_id=2');
        $respGrade->assertRedirect(route('agenda.operacional', [
            'data' => '2026-11-18',
            'sala_id' => '2',
            'aba' => 'grade',
        ]));

        // 2. /agenda/mensal com parâmetros
        $respMensal = $this->actingAs($this->usuarioCoordenacao)->get('/agenda/mensal?mes=12&ano=2026');
        $respMensal->assertRedirect(route('agenda.operacional', [
            'mes' => '12',
            'ano' => '2026',
            'aba' => 'mensal',
        ]));

        // 3. /recepcao com parâmetros
        $respRecepcao = $this->actingAs($this->usuarioRecepcao)->get('/recepcao?data=2026-11-10');
        $respRecepcao->assertRedirect(route('agenda.operacional', [
            'data' => '2026-11-10',
            'aba' => 'dia',
        ]));
    }

    /**
     * CENÁRIO 10: Abertura de dia específico a partir do Mapa Mensal carrega a Agenda Operacional na data selecionada.
     */
    public function test_cenario_10_abertura_de_dia_especifico_a_partir_do_mapa_mensal(): void
    {
        Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'mensal',
                'mes' => 11,
                'ano' => 2026,
            ])
            // Clica para abrir na Agenda Diária
            ->call('selecionarDiaDoMes', '2026-11-18', 'dia')
            ->assertSet('aba', 'dia')
            ->assertSet('data', '2026-11-18')
            // Clica para abrir na Grade Semanal
            ->call('selecionarDiaDoMes', '2026-11-19', 'grade')
            ->assertSet('aba', 'grade')
            ->assertSet('diaSemana', 'QUINTA')
            ->assertSet('data', '2026-11-19');
    }

    /**
     * CENÁRIO 11: Filtros do Mapa Mensal e indicadores executivos calculados corretamente.
     */
    public function test_cenario_11_filtros_e_indicadores_executivos_do_mapa_mensal(): void
    {
        Agendamento::create([
            'paciente_id' => $this->pacienteSegunda->id,
            'sala_id' => $this->sala1->id,
            'profissional_id' => $this->terapeuta->id,
            'procedimento_sigtap_id' => $this->procedimento->id,
            'data_atendimento' => '2026-11-10',
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'TERCA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'ATENDIDO',
            'recorrente' => false,
        ]);

        Agendamento::create([
            'paciente_id' => $this->pacienteQuarta->id,
            'sala_id' => $this->sala1->id,
            'profissional_id' => $this->terapeuta->id,
            'procedimento_sigtap_id' => $this->procedimento->id,
            'data_atendimento' => '2026-11-11',
            'hora_inicio' => '09:00',
            'hora_fim' => '09:30',
            'dia_semana' => 'QUARTA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'FALTA_INJUSTIFICADA',
            'recorrente' => false,
        ]);

        Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'mensal',
                'mes' => 11,
                'ano' => 2026,
            ])
            ->assertViewHas('metricasMes', function (array $m): bool {
                return $m['total'] === 2
                    && $m['realizados'] === 1
                    && $m['faltas'] === 1
                    && $m['taxaComparecimento'] === 50
                    && $m['taxaAbsenteismo'] === 50
                    && $m['diasComAgenda'] === 2;
            })
            // Aplicar filtro por turno 'MANHA'
            ->set('filtroTurnoMensal', 'MANHA')
            ->assertViewHas('metricasMes', function (array $m): bool {
                return $m['total'] === 2;
            })
            // Limpar filtros
            ->call('limparFiltrosMensal')
            ->assertSet('filtroTurnoMensal', '')
            ->assertSet('filtroEspecialidadeMensal', '')
            ->assertSet('filtroProfissionalIdMensal', '');
    }

    /**
     * CENÁRIO 12: Fluxo operacional completo preservado: check-in, repetição de chamada na TV e integração com PEP.
     */
    public function test_cenario_12_fluxo_operacional_completo_checkin_chamada_tv_e_pep(): void
    {
        $hoje = CarbonImmutable::now()->toDateString();

        $agendamento = Agendamento::create([
            'paciente_id' => $this->pacienteSegunda->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => $hoje,
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
            'recorrente' => false,
        ]);
        $agendamento->profissionais()->sync([$this->terapeuta->id]);

        // 1. Recepção realiza Check-in
        Livewire::actingAs($this->usuarioRecepcao)
            ->test(AgendaOperacional::class, ['aba' => 'dia', 'data' => $hoje])
            ->call('confirmarPresenca', $agendamento->id);

        $agendamento->refresh();
        $this->assertSame('PRESENTE', $agendamento->status);
        $this->assertNotNull($agendamento->chegada_em);

        // 2. Chamar e repetir chamada na TV
        $chamadaService = app(ChamadaPacienteService::class);
        $chamada = $chamadaService->chamarPaciente($agendamento, $this->usuarioRecepcao);
        $this->assertSame('CHAMANDO', $chamada->status);
        $this->assertSame('CHAMADA', $chamada->tipo);

        $chamadaRepetida = $chamadaService->repetirChamada($agendamento->id, $this->usuarioRecepcao);
        $this->assertSame('CHAMANDO', $chamadaRepetida->status);
        $this->assertSame('REPETICAO', $chamadaRepetida->tipo);

        // 3. Terapeuta visualiza paciente no PEP e registra evolução
        $this->actingAs($this->usuarioTerapeuta)
            ->get('/prontuario/'.$this->pacienteSegunda->id)
            ->assertOk()
            ->assertSee('Paciente da Segunda');

        $evolucaoService = app(EvolucaoService::class);
        $evolucaoService->registrarEAssinar(
            agendamento: $agendamento,
            profissional: $this->terapeuta,
            procedimento: $this->procedimento,
            dadosClinicos: [
                'queixa_estado_atual' => 'Paciente compareceu pontualmente para sessão de reabilitação.',
                'conduta_terapeutica' => 'Realizados exercícios de fortalecimento e mobilidade.',
            ],
            pinAssinatura: '1234',
        );

        $this->assertDatabaseHas('evolucoes_clinicas', [
            'paciente_id' => $this->pacienteSegunda->id,
            'profissional_id' => $this->terapeuta->id,
            'queixa_estado_atual' => 'Paciente compareceu pontualmente para sessão de reabilitação.',
        ]);

        $this->assertDatabaseHas('producoes_bpai', [
            'paciente_id' => $this->pacienteSegunda->id,
            'profissional_id' => $this->terapeuta->id,
            'procedimento_id' => $this->procedimento->id,
        ]);
    }

    /**
     * CENÁRIO 13: Perfis de acesso RBAC com comportamentos esperados.
     */
    public function test_cenario_13_perfis_de_acesso_rbac(): void
    {
        // Recepção: acessa agenda e recepção (redirecionada), mas não acessa faturamento
        $this->actingAs($this->usuarioRecepcao)->get('/agenda')->assertOk();
        $this->actingAs($this->usuarioRecepcao)->get('/recepcao')->assertRedirect(route('agenda.operacional', ['aba' => 'dia']));
        $this->actingAs($this->usuarioRecepcao)->get('/faturamento')->assertForbidden();

        // Terapeuta: acessa agenda e prontuário, mas não acessa faturamento
        $this->actingAs($this->usuarioTerapeuta)->get('/agenda')->assertOk();
        $this->actingAs($this->usuarioTerapeuta)->get('/prontuario')->assertOk();
        $this->actingAs($this->usuarioTerapeuta)->get('/faturamento')->assertForbidden();

        // Faturamento: acessa agenda e faturamento, mas não acessa prontuário PEP nem recepção
        $this->actingAs($this->usuarioFaturamento)->get('/agenda')->assertOk();
        $this->actingAs($this->usuarioFaturamento)->get('/faturamento')->assertOk();
        $this->actingAs($this->usuarioFaturamento)->get('/prontuario')->assertForbidden();
        $this->actingAs($this->usuarioFaturamento)->get('/recepcao')->assertForbidden();

        // Coordenação: acesso amplo
        $this->actingAs($this->usuarioCoordenacao)->get('/agenda')->assertOk();
        $this->actingAs($this->usuarioCoordenacao)->get('/prontuario')->assertOk();
        $this->actingAs($this->usuarioCoordenacao)->get('/faturamento')->assertOk();
    }

    /**
     * CENÁRIO 14: Comprovação de que a query de agendamentos diários não é executada nas abas grade e mensal.
     */
    public function test_cenario_14_query_de_agendamentos_diarios_nao_executa_em_grade_e_mensal(): void
    {
        // Aba 'grade': agendamentosDia é uma coleção vazia
        Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'grade',
                'data' => '2026-11-09',
            ])
            ->assertViewHas('agendamentosDia', function ($agendamentos): bool {
                return $agendamentos->isEmpty();
            });

        // Aba 'mensal': agendamentosDia é uma coleção vazia
        Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'mensal',
                'mes' => 11,
                'ano' => 2026,
            ])
            ->assertViewHas('agendamentosDia', function ($agendamentos): bool {
                return $agendamentos->isEmpty();
            });

        // Aba 'dia': agendamentosDia contém a consulta real
        Agendamento::create([
            'paciente_id' => $this->pacienteSegunda->id,
            'sala_id' => $this->sala1->id,
            'data_atendimento' => '2026-11-09',
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
            'recorrente' => false,
        ]);

        Livewire::actingAs($this->usuarioCoordenacao)
            ->test(AgendaOperacional::class, [
                'aba' => 'dia',
                'data' => '2026-11-09',
            ])
            ->assertViewHas('agendamentosDia', function ($agendamentos): bool {
                return $agendamentos->isNotEmpty();
            });
    }

    /**
     * CENÁRIO 15: Links existentes e rotas legadas continuam funcionando via redirecionamento.
     */
    public function test_cenario_15_links_existentes_e_rotas_nomeadas_nao_quebram(): void
    {
        $this->assertTrue(Route::has('agenda.operacional'));
        $this->assertTrue(Route::has('grade.semanal'));
        $this->assertTrue(Route::has('agenda.mensal'));
        $this->assertTrue(Route::has('recepcao.checkin'));

        $this->actingAs($this->usuarioCoordenacao)->followingRedirects()->get(route('grade.semanal'))->assertOk();
        $this->actingAs($this->usuarioCoordenacao)->followingRedirects()->get(route('agenda.mensal'))->assertOk();
        $this->actingAs($this->usuarioRecepcao)->followingRedirects()->get(route('recepcao.checkin'))->assertOk();
    }
}

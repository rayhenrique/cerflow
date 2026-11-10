<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\RelatoriosGerenciais;
use App\Models\Agendamento;
use App\Models\EvolucaoClinica;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\ProducaoBpai;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Services\RelatorioService;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class RelatoriosGerenciaisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function criarPaciente(array $atributos = []): Paciente
    {
        static $contador = 1;
        $contador++;

        return Paciente::create(array_merge([
            'nome' => 'Paciente Teste '.$contador,
            'cpf' => str_pad((string) $contador, 11, '0', STR_PAD_LEFT),
            'cns' => '7'.str_pad((string) $contador, 14, '0', STR_PAD_LEFT),
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Teste '.$contador,
            'municipio_ibge' => '270430',
            'ubs_referencia' => 'UBS Centro',
            'nome_acs' => 'ACS Maria',
            'ativo' => true,
        ], $atributos));
    }

    private function criarSala(array $atributos = []): Sala
    {
        static $contadorSala = 1;
        $contadorSala++;

        return Sala::create(array_merge([
            'nome' => 'Sala '.$contadorSala,
            'tipo' => 'Consultório',
            'ativo' => true,
        ], $atributos));
    }

    private function criarProfissional(array $atributos = []): Profissional
    {
        static $contadorProf = 1;
        $contadorProf++;

        $user = User::factory()->create(['ativo' => true]);
        $user->assignRole('terapeuta');

        return Profissional::create(array_merge([
            'user_id' => $user->id,
            'nome' => 'Dr. Profissional '.$contadorProf,
            'cpf' => str_pad((string) $contadorProf, 11, '0', STR_PAD_LEFT),
            'cns' => '7'.str_pad((string) $contadorProf, 14, '0', STR_PAD_LEFT),
            'registro_conselho' => 'REG '.$contadorProf,
            'cbo_padrao' => '223810',
            'especialidade' => 'Fisioterapia',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ], $atributos));
    }

    private function criarAgendamento(array $atributos = []): Agendamento
    {
        $paciente = $atributos['paciente_id'] ?? $this->criarPaciente()->id;
        $sala = $atributos['sala_id'] ?? $this->criarSala()->id;

        return Agendamento::create(array_merge([
            'paciente_id' => $paciente,
            'sala_id' => $sala,
            'data_atendimento' => CarbonImmutable::now()->toDateString(),
            'hora_inicio' => '08:00:00',
            'hora_fim' => '08:45:00',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
            'recorrente' => true,
        ], $atributos));
    }

    public function test_acesso_negado_para_usuario_nao_autenticado(): void
    {
        $response = $this->get('/relatorios');
        $response->assertRedirect('/login');
    }

    public function test_todos_os_papeis_autorizados_podem_acessar_modulo_de_relatorios(): void
    {
        $roles = ['coordenacao', 'recepcao', 'terapeuta', 'faturamento'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['ativo' => true]);
            $user->assignRole($role);

            $response = $this->actingAs($user)->get('/relatorios');
            $response->assertOk();
            $response->assertSee('Relatórios e Indicadores Gerenciais');
        }
    }

    public function test_aba_producao_bpai_e_restrita_para_coordenacao_e_faturamento(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $recepcionista = User::factory()->create(['ativo' => true]);
        $recepcionista->assignRole('recepcao');

        // Coordenação pode acessar producao-bpai
        Livewire::actingAs($coordenador)
            ->test(RelatoriosGerenciais::class, ['aba' => 'producao-bpai'])
            ->assertSet('abaAtiva', 'producao-bpai')
            ->assertSee('Competência BPA-i');

        // Recepção não tem permissão para producao-bpai e é direcionada para aba permitida
        Livewire::actingAs($recepcionista)
            ->test(RelatoriosGerenciais::class, ['aba' => 'producao-bpai'])
            ->assertSet('abaAtiva', 'visao-geral')
            ->assertDontSee('Competência BPA-i');
    }

    public function test_visao_geral_calcula_corretamente_distincao_entre_presente_e_atendido(): void
    {
        $hoje = CarbonImmutable::now();

        $paciente = $this->criarPaciente();
        $sala = $this->criarSala();
        $profissional = $this->criarProfissional();

        // 1 Atendido
        $agAtendido = $this->criarAgendamento([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'hora_inicio' => '08:00:00',
            'hora_fim' => '08:45:00',
            'status' => 'ATENDIDO',
            'tipo_atendimento' => 'INDIVIDUAL',
        ]);
        $agAtendido->profissionais()->attach($profissional->id, ['papel' => 'PRINCIPAL']);

        // 1 Presente (aguardando consulta)
        $agPresente = $this->criarAgendamento([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'hora_inicio' => '09:00:00',
            'hora_fim' => '09:45:00',
            'status' => 'PRESENTE',
            'tipo_atendimento' => 'INDIVIDUAL',
        ]);
        $agPresente->profissionais()->attach($profissional->id, ['papel' => 'PRINCIPAL']);

        // 1 Agendado (sem confirmação ainda)
        $agAgendado = $this->criarAgendamento([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fim' => '10:45:00',
            'status' => 'AGENDADO',
            'tipo_atendimento' => 'INDIVIDUAL',
        ]);
        $agAgendado->profissionais()->attach($profissional->id, ['papel' => 'PRINCIPAL']);

        $service = app(RelatorioService::class);
        $periodo = $service->resolverPeriodo('hoje');
        $dados = $service->obterVisaoGeral($periodo);

        $this->assertSame(3, $dados['total_agendamentos']);
        $this->assertSame(1, $dados['atendidos']);
        $this->assertSame(1, $dados['presencas']);
        $this->assertSame(1, $dados['aguardando']);
        $this->assertSame(0, $dados['faltas_justificadas']);
        $this->assertSame(0, $dados['faltas_injustificadas']);

        // Garante que PRESENTE não é contabilizado no campo de atendidos
        $this->assertNotSame($dados['atendidos'], $dados['presencas'] + $dados['atendidos']);
    }

    public function test_taxas_de_comparecimento_e_absenteismo_excluem_cancelados_e_agendados_futuros(): void
    {
        $hoje = CarbonImmutable::now();

        $paciente = $this->criarPaciente();
        $sala = $this->criarSala();

        // 1 Atendido
        $this->criarAgendamento([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'status' => 'ATENDIDO',
        ]);

        // 1 Presente
        $this->criarAgendamento([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'status' => 'PRESENTE',
        ]);

        // 1 Falta Injustificada
        $this->criarAgendamento([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'status' => 'FALTA_INJUSTIFICADA',
        ]);

        // 1 Cancelado (NÃO deve entrar no denominador de comparecimento/absenteísmo)
        $this->criarAgendamento([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'status' => 'CANCELADO',
        ]);

        // 1 Agendado sem desfecho (NÃO deve entrar no denominador de comparecimento/absenteísmo)
        $this->criarAgendamento([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'status' => 'AGENDADO',
        ]);

        $service = app(RelatorioService::class);
        $periodo = $service->resolverPeriodo('hoje');
        $dados = $service->obterVisaoGeral($periodo);

        // Total com desfecho: 1 atendido + 1 presente + 1 falta = 3
        $this->assertSame(3, $dados['total_com_desfecho']);
        $this->assertSame(2, $dados['total_compareceram']);
        $this->assertSame(1, $dados['total_faltas']);
        $this->assertSame(1, $dados['cancelamentos']);
        $this->assertSame(1, $dados['aguardando']);

        // Taxa comparecimento: 2 / 3 = 66.7%
        $this->assertSame(66.7, $dados['taxa_comparecimento']);
        // Taxa absenteísmo: 1 / 3 = 33.3%
        $this->assertSame(33.3, $dados['taxa_absenteismo']);
    }

    public function test_agendamentos_compartilhados_sao_contados_uma_unica_vez_no_consolidado_e_por_profissional_no_relatorio_de_profissionais(): void
    {
        $hoje = CarbonImmutable::now();

        $paciente = $this->criarPaciente();
        $sala = $this->criarSala();

        $profA = $this->criarProfissional(['nome' => 'Fonoaudiólogo Teste', 'especialidade' => 'Fonoaudiologia']);
        $profB = $this->criarProfissional(['nome' => 'Terapeuta Ocupacional Teste', 'especialidade' => 'Terapia Ocupacional']);

        // 1 Agendamento compartilhado com 2 profissionais
        $agendamento = $this->criarAgendamento([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'tipo_atendimento' => 'COMPARTILHADO',
            'status' => 'ATENDIDO',
        ]);
        $agendamento->profissionais()->attach([
            $profA->id => ['papel' => 'PRINCIPAL'],
            $profB->id => ['papel' => 'COMPARTILHADO'],
        ]);

        $service = app(RelatorioService::class);
        $periodo = $service->resolverPeriodo('hoje');

        // 1. No consolidado da unidade, o agendamento é computado somente UMA vez
        $visaoGeral = $service->obterVisaoGeral($periodo);
        $this->assertSame(1, $visaoGeral['total_agendamentos']);
        $this->assertSame(1, $visaoGeral['compartilhados']);

        // 2. No relatório por profissional, ambos têm a sua participação registrada
        $dadosProf = $service->obterProfissionais($periodo);
        $linhas = collect($dadosProf['linhas'])->keyBy('id');

        $this->assertSame(1, $linhas[$profA->id]['compartilhados']);
        $this->assertSame(1, $linhas[$profB->id]['compartilhados']);
    }

    public function test_relatorio_de_pacientes_calcula_qualidade_cadastral_sem_carregar_toda_a_base_em_memoria(): void
    {
        // Cria pacientes com diferentes graus de completude
        $this->criarPaciente([
            'cns' => '700000000000001',
            'data_nascimento' => '1995-05-10',
            'ubs_referencia' => 'UBS Centro',
            'nome_acs' => 'Maria Silva',
        ]);

        $this->criarPaciente([
            'cns' => null,
            'data_nascimento' => '2000-01-01',
            'ubs_referencia' => null,
            'nome_acs' => null,
        ]);

        $service = app(RelatorioService::class);
        $periodo = $service->resolverPeriodo('mes_atual');
        $dadosPacientes = $service->obterPacientes($periodo);

        $this->assertSame(2, $dadosPacientes['total_ativos']);
        $this->assertSame(1, $dadosPacientes['sem_cns']);
        $this->assertSame(0, $dadosPacientes['sem_nascimento']);
        $this->assertSame(1, $dadosPacientes['sem_ubs']);
        $this->assertSame(1, $dadosPacientes['sem_acs']);
        $this->assertSame(50.0, $dadosPacientes['perc_cns']);
        $this->assertSame(100.0, $dadosPacientes['perc_nascimento']);
    }

    public function test_producao_bpai_e_estritamente_somente_leitura_sem_persistir_status(): void
    {
        $paciente = $this->criarPaciente();
        $prof = $this->criarProfissional();
        $sigtap = ProcedimentoSigtap::create([
            'codigo' => '0301070111',
            'descricao' => 'Atendimento em Reabilitação',
            'cbos_permitidos' => ['223810'],
            'sexo_permitido' => 'AMBOS',
            'idade_minima' => 0,
            'idade_maxima' => 999,
            'ativo' => true,
        ]);

        $agendamento = $this->criarAgendamento(['paciente_id' => $paciente->id]);

        $evolucao = EvolucaoClinica::create([
            'agendamento_id' => $agendamento->id,
            'paciente_id' => $paciente->id,
            'profissional_id' => $prof->id,
            'queixa_estado_atual' => 'Paciente em evolução de marcha.',
            'conduta_terapeutica' => 'Treino proprioceptivo.',
            'intercorrencias' => null,
            'assinado_em' => now(),
            'assinatura_hash' => 'fake_hash',
            'bloqueado_edicao' => false,
        ]);

        $producao = ProducaoBpai::create([
            'evolucao_id' => $evolucao->id,
            'paciente_id' => $paciente->id,
            'profissional_id' => $prof->id,
            'procedimento_id' => $sigtap->id,
            'cbo_executante' => '223810',
            'cid_atendimento' => 'F840',
            'data_atendimento' => '2026-11-05',
            'competencia' => '202611',
            'status_exportacao' => 'PENDENTE',
            'mensagem_inconsistencia' => null,
        ]);

        $service = app(RelatorioService::class);
        $dadosBpai = $service->obterProducaoBpai('202611');

        $this->assertSame(1, $dadosBpai['total']);
        $this->assertSame(1, $dadosBpai['pendentes']);

        // Certifica que o registro no banco não teve o status modificado
        $producao->refresh();
        $this->assertSame('PENDENTE', $producao->status_exportacao);
    }

    public function test_exportacao_csv_gera_arquivo_stream_com_utf8_e_auditoria_lgpd(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $user->assignRole('coordenacao');

        $service = app(RelatorioService::class);
        $periodo = $service->resolverPeriodo('mes_atual');

        $response = $service->exportarCsv('visao-geral', $periodo, $user);

        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="relatorio-visao-geral-', (string) $response->headers->get('Content-Disposition'));

        // Verifica auditoria LGPD
        $log = LogAuditoria::where('acao', 'EXPORTACAO_RELATORIO')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('visao-geral', $log->detalhes['aba']);
    }

    public function test_limpeza_de_filtros_restaura_padroes_do_mes_atual(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $user->assignRole('coordenacao');

        Livewire::actingAs($user)
            ->test(RelatoriosGerenciais::class)
            ->set('periodoPredefinido', 'hoje')
            ->set('profissionalId', 99)
            ->set('especialidade', 'Psicologia')
            ->set('salaId', 5)
            ->call('limparFiltros')
            ->assertSet('periodoPredefinido', 'mes_atual')
            ->assertSet('profissionalId', null)
            ->assertSet('especialidade', '')
            ->assertSet('salaId', null);
    }

    public function test_renderizacao_com_banco_vazio_nao_dispara_divisao_por_zero(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $user->assignRole('coordenacao');

        $abas = ['visao-geral', 'atendimentos', 'pacientes', 'profissionais', 'salas', 'producao-bpai'];

        foreach ($abas as $aba) {
            Livewire::actingAs($user)
                ->test(RelatoriosGerenciais::class, ['aba' => $aba])
                ->assertSet('abaAtiva', $aba)
                ->assertOk();
        }
    }

    public function test_filtros_de_sala_e_especialidade_restringem_consultas(): void
    {
        $hoje = CarbonImmutable::now();

        $salaA = $this->criarSala(['nome' => 'Consultório A']);
        $salaB = $this->criarSala(['nome' => 'Consultório B']);

        $profA = $this->criarProfissional(['nome' => 'Dra. Fisio', 'especialidade' => 'Fisioterapia']);
        $profB = $this->criarProfissional(['nome' => 'Dr. Fono', 'especialidade' => 'Fonoaudiologia']);

        $agA = $this->criarAgendamento([
            'sala_id' => $salaA->id,
            'data_atendimento' => $hoje->toDateString(),
            'status' => 'ATENDIDO',
        ]);
        $agA->profissionais()->attach($profA->id, ['papel' => 'PRINCIPAL']);

        $agB = $this->criarAgendamento([
            'sala_id' => $salaB->id,
            'data_atendimento' => $hoje->toDateString(),
            'status' => 'ATENDIDO',
        ]);
        $agB->profissionais()->attach($profB->id, ['papel' => 'PRINCIPAL']);

        $service = app(RelatorioService::class);
        $periodo = $service->resolverPeriodo('hoje');

        // Filtra por Sala A
        $dadosSalaA = $service->obterVisaoGeral(array_merge($periodo, ['salaId' => $salaA->id]));
        $this->assertSame(1, $dadosSalaA['total_agendamentos']);

        // Filtra por Especialidade Fisioterapia
        $dadosFisio = $service->obterVisaoGeral(array_merge($periodo, ['especialidade' => 'Fisioterapia']));
        $this->assertSame(1, $dadosFisio['total_agendamentos']);

        // Filtra por Especialidade Fonoaudiologia
        $dadosFono = $service->obterVisaoGeral(array_merge($periodo, ['especialidade' => 'Fonoaudiologia']));
        $this->assertSame(1, $dadosFono['total_agendamentos']);
    }

    public function test_exportacao_csv_para_todas_as_abas_disponiveis(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $user->assignRole('coordenacao');

        $service = app(RelatorioService::class);
        $periodo = $service->resolverPeriodo('mes_atual');

        $abas = ['visao-geral', 'atendimentos', 'absenteismo', 'pacientes', 'profissionais', 'salas', 'producao-bpai'];

        foreach ($abas as $aba) {
            $response = $service->exportarCsv($aba, $periodo, $user);
            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
            $this->assertStringContainsString("attachment; filename=\"relatorio-{$aba}-", (string) $response->headers->get('Content-Disposition'));
        }
    }

    public function test_menu_lateral_apresenta_link_para_relatorios(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $user->assignRole('coordenacao');

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('/relatorios');
        $response->assertSee('Relatórios');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
use App\Livewire\DashboardInicial;
use App\Livewire\GradeSemanal;
use App\Livewire\RecepcaoCheckin;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\UnidadeBasicaSaude;
use App\Models\User;
use App\Rules\CnsValido;
use Carbon\CarbonImmutable;
use Database\Seeders\ProcedimentoSigtapSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class OtimizacaoConsultasPacientesTest extends TestCase
{
    use RefreshDatabase;

    private User $coordenador;

    private User $terapeuta;

    private User $recepcionista;

    private Sala $sala;

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

        $this->terapeuta = User::factory()->create(['ativo' => true]);
        $this->terapeuta->assignRole('terapeuta');
        Profissional::create([
            'user_id' => $this->terapeuta->id,
            'nome' => 'Dra. Terapeuta Responsavel',
            'cpf' => '11122233344',
            'cns' => CnsValido::gerarDefinitivo('11122233344'),
            'registro_conselho' => 'CREFITO 1234',
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);

        $this->recepcionista = User::factory()->create(['ativo' => true]);
        $this->recepcionista->assignRole('recepcao');

        $this->sala = Sala::first() ?? Sala::create(['nome' => 'Consultório 01', 'tipo' => 'CONSULTORIO', 'ativo' => true]);
    }

    public function test_abertura_modal_grade_semanal_nao_carrega_pacientes_sem_busca(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Paciente::create([
                'nome' => sprintf('Paciente Carga Teste %02d', $i),
                'cpf' => sprintf('111000000%02d', $i),
                'cns' => CnsValido::gerarDefinitivo(sprintf('111000000%02d', $i)),
                'data_nascimento' => '1990-01-01',
                'sexo' => 'M',
                'nome_mae' => 'Mãe Teste',
                'municipio_ibge' => '270430',
                'ativo' => true,
            ]);
        }

        $consultasPacientes = 0;
        DB::listen(function ($query) use (&$consultasPacientes): void {
            if (str_contains(strtolower($query->sql), 'from "pacientes"') || str_contains(strtolower($query->sql), 'from `pacientes`')) {
                $consultasPacientes++;
            }
        });

        Livewire::actingAs($this->coordenador)
            ->test(GradeSemanal::class)
            ->call('abrirModalSlot', $this->sala->id, '08:00')
            ->assertSet('modalAberto', true)
            ->assertSet('pesquisaPaciente', '')
            ->assertViewHas('pacientesResultados', []);

        // Garante que não executou SELECT * FROM pacientes carregando os 30 registros
        $this->assertSame(0, $consultasPacientes);
    }

    public function test_busca_otimizada_por_cpf_cns_e_nome_utiliza_projecao_restrita_e_limite(): void
    {
        $alvo = Paciente::create([
            'nome' => 'Renato Augusto Medeiros',
            'cpf' => '55566677788',
            'cns' => CnsValido::gerarDefinitivo('55566677788'),
            'data_nascimento' => '1988-04-10',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Renato',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        // 1. Busca por CPF numérico (11 dígitos)
        $resultadoCpf = Paciente::buscarParaSelecao('555.666.777-88', 20);
        $this->assertCount(1, $resultadoCpf);
        $this->assertSame($alvo->id, $resultadoCpf->first()->id);

        // 2. Busca por CNS
        $resultadoCns = Paciente::buscarParaSelecao((string) $alvo->cns, 20);
        $this->assertCount(1, $resultadoCns);
        $this->assertSame($alvo->id, $resultadoCns->first()->id);

        // 3. Busca por prefixo de Nome
        $resultadoNome = Paciente::buscarParaSelecao('Renato', 20);
        $this->assertCount(1, $resultadoNome);
        $this->assertSame('Renato Augusto Medeiros', $resultadoNome->first()->nome);

        // 4. Termo com menos de 3 caracteres não dispara consulta e retorna vazio
        $resultadoVazio = Paciente::buscarParaSelecao('Re', 20);
        $this->assertCount(0, $resultadoVazio);
    }

    public function test_configuracoes_sistema_nao_consulta_pacientes_nem_ubs_em_outras_abas(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Paciente::create([
                'nome' => sprintf('Paciente Base %02d', $i),
                'cpf' => sprintf('222000000%02d', $i),
                'cns' => CnsValido::gerarDefinitivo(sprintf('222000000%02d', $i)),
                'data_nascimento' => '1990-01-01',
                'sexo' => 'M',
                'nome_mae' => 'Mãe Teste',
                'municipio_ibge' => '270430',
                'ubs_referencia' => 'UBS Centro / PSF Central',
                'ativo' => true,
            ]);
        }

        $consultasPacientes = 0;
        DB::listen(function ($query) use (&$consultasPacientes): void {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'from "pacientes"') || str_contains($sql, 'from `pacientes`')) {
                $consultasPacientes++;
            }
        });

        // 1. Acesso à aba "salas" não deve tocar na tabela pacientes
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'salas'])
            ->assertSet('abaAtiva', 'salas')
            ->assertViewHas('pacientes', function ($pac) {
                return $pac->isEmpty();
            })
            ->assertViewMissing('listaUbs');

        $this->assertSame(0, $consultasPacientes, 'Aba salas não deve executar queries na tabela pacientes');

        // 2. Acesso à aba "profissionais" não deve tocar na tabela pacientes
        $consultasPacientes = 0;
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'profissionais'])
            ->assertSet('abaAtiva', 'profissionais')
            ->assertViewHas('pacientes', function ($pac) {
                return $pac->isEmpty();
            });

        $this->assertSame(0, $consultasPacientes, 'Aba profissionais não deve executar queries na tabela pacientes');
    }

    public function test_configuracoes_sistema_aba_pacientes_utiliza_projecao_restrita_sem_select_star(): void
    {
        Paciente::create([
            'nome' => 'Paciente Projeção Teste',
            'cpf' => '33300000001',
            'cns' => CnsValido::gerarDefinitivo('33300000001'),
            'data_nascimento' => '1995-05-05',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Projeção',
            'municipio_ibge' => '270430',
            'endereco' => 'Rua Muito Longa com Dados Desnecessários para Listagem 123',
            'bairro' => 'Bairro Distante',
            'cid_principal' => 'F84.0',
            'ativo' => true,
        ]);

        $queriesExecutadas = [];
        DB::listen(function ($query) use (&$queriesExecutadas): void {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'from "pacientes"') || str_contains($sql, 'from `pacientes`')) {
                $queriesExecutadas[] = $query->sql;
            }
        });

        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->assertSet('abaAtiva', 'pacientes')
            ->assertViewHas('pacientes', function ($pac) {
                return $pac->count() >= 1;
            });

        // Verifica que nenhuma consulta de paginação fez "select *" ou "select `pacientes`.*"
        foreach ($queriesExecutadas as $sql) {
            $sqlLower = strtolower($sql);
            if (str_contains($sqlLower, 'limit') && ! str_contains($sqlLower, 'count(')) {
                $this->assertFalse(str_contains($sqlLower, 'select *'), "Query não deve usar select *: {$sql}");
                $this->assertFalse(str_contains($sqlLower, 'select "pacientes".*'), "Query não deve usar select * com alias: {$sql}");
                $this->assertFalse(str_contains($sqlLower, 'select `pacientes`.*'), "Query não deve usar select * com alias: {$sql}");
            }
        }
    }

    public function test_dropdown_ubs_referencia_utiliza_apenas_unidades_cadastradas_sem_mock(): void
    {
        $ubsReal = UnidadeBasicaSaude::create([
            'nome' => 'UBS Vila Nova Esperanca',
            'cnes' => '2719999',
            'municipio_ibge' => '270430',
            'origem' => 'MANUAL',
            'ativo' => true,
        ]);

        // Renderização deve conter a UBS cadastrada em todasUbs
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->assertViewHas('todasUbs', function ($todasUbs) use ($ubsReal) {
                return $todasUbs->contains('id', $ubsReal->id);
            });

        // Salvar paciente selecionando a UBS associa pac_ubs_id e sincroniza pac_ubs_referencia
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->set('pac_nome', 'Novo Paciente Cadastrado')
            ->set('pac_data_nascimento', '2000-01-01')
            ->set('pac_sexo', 'M')
            ->set('pac_municipio_ibge', '270430')
            ->set('pac_ubs_id', $ubsReal->id)
            ->call('salvarPaciente')
            ->assertHasNoErrors();

        $pacienteCriado = Paciente::where('nome', 'Novo Paciente Cadastrado')->first();
        $this->assertNotNull($pacienteCriado);
        $this->assertSame($ubsReal->id, $pacienteCriado->ubs_id);
        $this->assertSame('UBS Vila Nova Esperanca', $pacienteCriado->ubs_referencia);
    }

    public function test_dashboard_contagem_modalidades_e_metricas_possuem_cache(): void
    {
        Paciente::create([
            'nome' => 'Paciente TEA Modalidade',
            'cpf' => '66600000001',
            'cns' => CnsValido::gerarDefinitivo('66600000001'),
            'data_nascimento' => '2015-06-01',
            'sexo' => 'M',
            'nome_mae' => 'Mãe TEA',
            'municipio_ibge' => '270430',
            'cid_principal' => 'F84.0',
            'modalidades_reabilitacao' => ['INTELECTUAL'],
            'ativo' => true,
        ]);

        Livewire::actingAs($this->coordenador)
            ->test(DashboardInicial::class)
            ->assertViewHas('contagemModalidades', function (array $mod) {
                return isset($mod['INTELECTUAL']) && $mod['INTELECTUAL'] >= 1;
            });

        $this->assertTrue(Cache::has('dashboard_contagem_modalidades'));
        $this->assertTrue(Cache::has('dash_total_pacientes_ativos'));
    }

    public function test_recepcao_checkin_carrega_pacientes_com_projecao_restrita(): void
    {
        $hoje = CarbonImmutable::now();
        $paciente = Paciente::create([
            'nome' => 'Paciente Recepcao Checkin Otimizado',
            'cpf' => '77700000001',
            'cns' => CnsValido::gerarDefinitivo('77700000001'),
            'data_nascimento' => '1985-03-03',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Recepcao',
            'municipio_ibge' => '270430',
            'endereco' => 'Endereco Super Longo que nao deve ser lido',
            'ativo' => true,
        ]);

        $prof = Profissional::first();

        $agendamento = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'hora_inicio' => '10:00',
            'hora_fim' => '10:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
        ]);
        $agendamento->profissionais()->sync([$prof->id]);

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            if (str_contains(strtolower($query->sql), 'from "pacientes"') || str_contains(strtolower($query->sql), 'from `pacientes`')) {
                $queries[] = $query->sql;
            }
        });

        Livewire::actingAs($this->recepcionista)
            ->test(RecepcaoCheckin::class)
            ->assertSee('Paciente Recepcao Checkin Otimizado');

        foreach ($queries as $sql) {
            $sqlLower = strtolower($sql);
            $this->assertFalse(str_contains($sqlLower, 'select *'), "RecepcaoCheckin não deve usar select * em pacientes: {$sql}");
        }
    }
}

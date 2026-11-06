<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\ProducaoBpai;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use App\Services\AgendamentoService;
use App\Services\EvolucaoService;
use Database\Seeders\ProcedimentoSigtapSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AtendimentoCompartilhadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            ProcedimentoSigtapSeeder::class,
            SalaSeeder::class,
        ]);
    }

    public function test_desmembra_producao_bpai_por_profissional_e_cbo_em_atendimento_compartilhado(): void
    {
        $userFono = User::factory()->create(['ativo' => true]);
        $userFono->assignRole('terapeuta');
        $fono = Profissional::create([
            'user_id' => $userFono->id,
            'nome' => 'Dra. Helena Fonoaudióloga',
            'cpf' => '10120230344',
            'cns' => CnsValido::gerarDefinitivo('10120230344'),
            'registro_conselho' => 'CRFa 4-1010',
            'cbo_padrao' => '223810',
            'especialidade' => 'Fonoaudiologia',
            'pin_assinatura_hash' => Hash::make('1111'),
            'ativo' => true,
        ]);

        $userTo = User::factory()->create(['ativo' => true]);
        $userTo->assignRole('terapeuta');
        $terapeutaOcupacional = Profissional::create([
            'user_id' => $userTo->id,
            'nome' => 'Dra. Beatriz Terapeuta Ocupacional',
            'cpf' => '20230340455',
            'cns' => CnsValido::gerarDefinitivo('20230340455'),
            'registro_conselho' => 'CREFITO-1 5050-TO',
            'cbo_padrao' => '223905',
            'especialidade' => 'Terapia Ocupacional',
            'pin_assinatura_hash' => Hash::make('2222'),
            'ativo' => true,
        ]);

        $paciente = Paciente::create([
            'cns' => CnsValido::gerarDefinitivo('14455566677'),
            'cpf' => '44455566677',
            'nome' => 'Lucas Gabriel Oliveira',
            'data_nascimento' => '2019-08-20',
            'sexo' => 'M',
            'nome_mae' => 'Fernanda Oliveira',
            'municipio_ibge' => '270430',
            'cid_principal' => 'F840',
            'modalidades_reabilitacao' => ['INTELECTUAL'],
            'ativo' => true,
        ]);

        $salaSensorial = Sala::where('tipo', 'SENSORIAL')->firstOrFail();

        $agendamentoService = app(AgendamentoService::class);
        $agendamentos = $agendamentoService->criarGrade(
            dados: [
                'paciente_id' => $paciente->id,
                'sala_id' => $salaSensorial->id,
                'data_atendimento' => '2026-11-09',
                'hora_inicio' => '09:00',
                'hora_fim' => '09:30',
                'dia_semana' => 'SEGUNDA',
                'tipo_atendimento' => 'COMPARTILHADO',
                'recorrente' => false,
            ],
            profissionaisIds: [$fono->id, $terapeutaOcupacional->id],
            semanasRecorrencia: 1,
        );

        $agendamento = $agendamentos->first();
        $this->assertSame('COMPARTILHADO', $agendamento->tipo_atendimento);
        $this->assertCount(2, $agendamento->profissionais);

        $procTea = ProcedimentoSigtap::where('codigo', '0301070113')->firstOrFail();
        $procFono = ProcedimentoSigtap::where('codigo', '0301040044')->firstOrFail();

        $evolucaoService = app(EvolucaoService::class);
        $evolucoes = $evolucaoService->registrarAtendimentoCompartilhado($agendamento, [
            [
                'profissional' => $fono,
                'procedimento' => $procFono,
                'queixa_estado_atual' => 'Demanda comunicativa verbal reduzida.',
                'conduta_terapeutica' => 'Estimulação de linguagem pragmática com CAA.',
                'pin_assinatura' => '1111',
            ],
            [
                'profissional' => $terapeutaOcupacional,
                'procedimento' => $procTea,
                'queixa_estado_atual' => 'Hipersensibilidade tátil e busca vestibular.',
                'conduta_terapeutica' => 'Integração sensorial na sala suspensa.',
                'pin_assinatura' => '2222',
            ],
        ]);

        $this->assertCount(2, $evolucoes);
        $this->assertSame(2, ProducaoBpai::where('paciente_id', $paciente->id)->count());

        $this->assertDatabaseHas('producoes_bpai', [
            'paciente_id' => $paciente->id,
            'profissional_id' => $fono->id,
            'procedimento_id' => $procFono->id,
            'cbo_executante' => '223810',
            'competencia' => '202611',
        ]);

        $this->assertDatabaseHas('producoes_bpai', [
            'paciente_id' => $paciente->id,
            'profissional_id' => $terapeutaOcupacional->id,
            'procedimento_id' => $procTea->id,
            'cbo_executante' => '223905',
            'competencia' => '202611',
        ]);
    }
}


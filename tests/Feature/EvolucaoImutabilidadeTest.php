<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use App\Services\EvolucaoService;
use Database\Seeders\ProcedimentoSigtapSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EvolucaoImutabilidadeTest extends TestCase
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

    public function test_permite_edicao_pelo_autor_antes_de_24_horas_e_bloqueia_apos_24_horas(): void
    {
        $userAutor = User::factory()->create(['ativo' => true]);
        $userAutor->assignRole('terapeuta');

        $profissionalAutor = Profissional::create([
            'user_id' => $userAutor->id,
            'nome' => 'Dra. Clara Fisioterapeuta',
            'cpf' => '11122233344',
            'cns' => CnsValido::gerarDefinitivo('11122233344'),
            'registro_conselho' => 'CREFITO-1 12345-F',
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);

        $userOutro = User::factory()->create(['ativo' => true]);
        $userOutro->assignRole('terapeuta');

        Profissional::create([
            'user_id' => $userOutro->id,
            'nome' => 'Dr. Marcos Terapeuta Ocupacional',
            'cpf' => '55566677788',
            'cns' => CnsValido::gerarDefinitivo('25566677788'),
            'registro_conselho' => 'CREFITO-1 9999-TO',
            'cbo_padrao' => '223905',
            'especialidade' => 'Terapia Ocupacional',
            'pin_assinatura_hash' => Hash::make('5678'),
            'ativo' => true,
        ]);

        $paciente = Paciente::create([
            'cns' => CnsValido::gerarDefinitivo('19988877766'),
            'cpf' => '99988877766',
            'nome' => 'João Pedro da Silva',
            'data_nascimento' => '2018-04-12',
            'sexo' => 'M',
            'nome_mae' => 'Maria da Silva',
            'municipio_ibge' => '270430',
            'cid_principal' => 'G800',
            'modalidades_reabilitacao' => ['FISICA'],
            'ativo' => true,
        ]);

        $sala = Sala::firstOrFail();
        $procedimento = ProcedimentoSigtap::where('codigo', '0301070075')->firstOrFail();

        $agendamento = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
            'recorrente' => true,
        ]);

        $evolucaoService = app(EvolucaoService::class);

        $evolucao = $evolucaoService->registrarEAssinar(
            agendamento: $agendamento,
            profissional: $profissionalAutor,
            procedimento: $procedimento,
            dadosClinicos: [
                'queixa_estado_atual' => 'Paciente estável, bom controle cervical.',
                'conduta_terapeutica' => 'Treino motor em prono e ortostatismo assistido.',
            ],
            pinAssinatura: '1234',
            assinadoEm: now()->subHours(12),
        );

        // 1. Antes de 24h, o próprio autor consegue atualizar:
        $atualizada = $evolucaoService->atualizarEvolucao($userAutor->fresh(), $evolucao, [
            'queixa_estado_atual' => 'Paciente estável, ótimo controle cervical e de tronco.',
            'conduta_terapeutica' => 'Treino motor em prono e ortostatismo assistido por 20 min.',
        ]);

        $this->assertStringContainsString('ótimo controle cervical', $atualizada->queixa_estado_atual);

        // 2. Outro profissional é bloqueado mesmo dentro das 24h:
        $this->expectException(AuthorizationException::class);
        try {
            $evolucaoService->atualizarEvolucao($userOutro->fresh(), $evolucao, [
                'queixa_estado_atual' => 'Tentativa indevida por terceiro.',
                'conduta_terapeutica' => 'Conduta alterada.',
            ]);
        } catch (AuthorizationException $e) {
            // 3. Simula passagem de 24 horas desde a assinatura:
            $evolucao->update(['assinado_em' => now()->subHours(25)]);
            $this->assertTrue($evolucao->fresh()->estaBloqueada());

            // 4. Após 24h, permite apenas inserção de Termo de Adendo retificador pelo autor:
            $adendo = $evolucaoService->registrarAdendo(
                user: $userAutor->fresh(),
                evolucao: $evolucao->fresh(),
                justificativa: 'Complemento de medição goniométrica pós-sessão.',
                textoAdendo: 'ADM de dorsiflexão bilateral evoluiu 5 graus.',
                pinAssinatura: '1234',
            );

            $this->assertDatabaseHas('evolucao_adendos', [
                'id' => $adendo->id,
                'evolucao_id' => $evolucao->id,
                'profissional_id' => $profissionalAutor->id,
            ]);

            // 5. E bloqueia qualquer tentativa de update direto na evolução após 24h:
            $evolucaoService->atualizarEvolucao($userAutor->fresh(), $evolucao->fresh(), [
                'queixa_estado_atual' => 'Tentativa de edição após 24h.',
                'conduta_terapeutica' => 'Deve lançar AuthorizationException.',
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\DashboardInicial;
use App\Models\Paciente;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardInicialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_dashboard_inicial_exibe_estatisticas_do_cer(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        Sala::create([
            'nome' => 'Consultório 01 - Fisioterapia',
            'tipo' => 'CONSULTORIO',
            'ativo' => true,
        ]);

        Paciente::create([
            'nome' => 'Miguel Henrique Alves',
            'cns' => CnsValido::gerarDefinitivo('31122233344'),
            'data_nascimento' => '2016-05-10',
            'sexo' => 'M',
            'nome_mae' => 'Patrícia Alves',
            'municipio_ibge' => '270430',
            'cid_principal' => 'G800',
            'modalidades_reabilitacao' => ['FISICA', 'INTELECTUAL'],
            'ativo' => true,
        ]);

        $response = $this->actingAs($coordenador)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Painel de Indicadores do CER');
        $response->assertSee('Pacientes por Modalidade de Reabilitação');
        $response->assertSee('Assiduidade & Absenteísmo no Mês', false);

        Livewire::actingAs($coordenador)
            ->test(DashboardInicial::class)
            ->assertSee('Reabilitação Física')
            ->assertSee('Consultório 01 - Fisioterapia');
    }

    public function test_contagem_modalidades_ignora_inativos_e_excluidos(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        // Paciente Ativo com FISICA e VISUAL
        Paciente::create([
            'nome' => 'Paciente Ativo Modalidades',
            'cns' => CnsValido::gerarDefinitivo('10101010101'),
            'data_nascimento' => '2010-01-01',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Ativa',
            'municipio_ibge' => '270430',
            'modalidades_reabilitacao' => ['FISICA', 'VISUAL'],
            'ativo' => true,
        ]);

        // Paciente Inativo com AUDITIVA (não deve ser contado)
        Paciente::create([
            'nome' => 'Paciente Inativo Modalidade',
            'cns' => CnsValido::gerarDefinitivo('20202020202'),
            'data_nascimento' => '2010-01-01',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Inativa',
            'municipio_ibge' => '270430',
            'modalidades_reabilitacao' => ['AUDITIVA'],
            'ativo' => false,
        ]);

        // Paciente Excluído com INTELECTUAL (não deve ser contado)
        $excluido = Paciente::create([
            'nome' => 'Paciente Excluído Modalidade',
            'cns' => CnsValido::gerarDefinitivo('30303030303'),
            'data_nascimento' => '2010-01-01',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Excluída',
            'municipio_ibge' => '270430',
            'modalidades_reabilitacao' => ['INTELECTUAL'],
            'ativo' => true,
        ]);
        $excluido->delete();

        Livewire::actingAs($coordenador)
            ->test(DashboardInicial::class)
            ->assertViewHas('contagemModalidades', function (array $contagem): bool {
                return $contagem['FISICA'] === 1
                    && $contagem['VISUAL'] === 1
                    && $contagem['AUDITIVA'] === 0
                    && $contagem['INTELECTUAL'] === 0;
            });
    }
}

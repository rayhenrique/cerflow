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
}

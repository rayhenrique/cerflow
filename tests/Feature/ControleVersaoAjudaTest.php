<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ModalNovidadesVersao;
use App\Models\User;
use App\Services\VersionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ControleVersaoAjudaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_dispara_modal_de_novidades_da_versao_sempre_que_usuario_faz_login(): void
    {
        $user = User::factory()->create([
            'email' => 'terapeuta@cerflow.gov.br',
            'password' => Hash::make('secret123'),
            'ativo' => true,
        ]);
        $user->assignRole('terapeuta');

        $response = $this->post('/login', [
            'email' => 'terapeuta@cerflow.gov.br',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/');
        $this->assertTrue(session()->get('exibir_modal_versao'));

        // Ao acessar a tela após login, o modal de novidades da versão deve estar aberto e exibir a versão atual:
        $versaoAtiva = app(VersionService::class)->versaoAtual();
        $paginaGrade = $this->actingAs($user)->get('/grade');
        $paginaGrade->assertOk();
        $paginaGrade->assertSee('Novidades da Versão');
        $paginaGrade->assertSee('v'.$versaoAtiva);

        // Ao fechar o modal pelo componente Livewire, limpa a flag da sessão atual:
        Livewire::actingAs($user)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', true)
            ->call('fecharModal')
            ->assertSet('aberto', false);

        $this->assertNull(session()->get('exibir_modal_versao'));
    }

    public function test_modulo_ajuda_e_sobre_exibe_evolucao_das_versoes_e_manual_sus(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $user->assignRole('recepcao');

        $response = $this->actingAs($user)->get('/ajuda');

        $response->assertOk();
        $response->assertSee('Central de Ajuda & Sobre o CER Flow', false);
        $response->assertSee('v1.1.1');
        $response->assertSee('v1.1.0');
        $response->assertSee('v1.0.1');
        $response->assertSee('v1.0.0');
    }
}

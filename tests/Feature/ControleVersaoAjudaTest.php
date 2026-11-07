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

    public function test_primeiro_acesso_exibe_modal_de_novidades_e_salva_no_banco_ao_fechar(): void
    {
        $versaoAtiva = app(VersionService::class)->versaoAtual();

        $user = User::factory()->create([
            'email' => 'terapeuta@cerflow.gov.br',
            'password' => Hash::make('secret123'),
            'ativo' => true,
            'ultima_versao_novidades_vista' => null,
        ]);
        $user->assignRole('terapeuta');

        // 1. No primeiro acesso, o modal abre automaticamente
        $componente = Livewire::actingAs($user)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', true);

        // Enquanto não fechar, o banco continua sem marcar a versão
        $user->refresh();
        $this->assertNull($user->ultima_versao_novidades_vista);

        // 2. Ao fechar o modal, salva a versão no cadastro do usuário
        $componente->call('fecharModal')
            ->assertSet('aberto', false);

        $user->refresh();
        $this->assertSame($versaoAtiva, $user->ultima_versao_novidades_vista);

        // 3. Em um próximo acesso com o mesmo usuário, o modal permanece fechado
        Livewire::actingAs($user)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', false);
    }

    public function test_apos_fechar_nao_aparece_novamente_nos_proximos_logins(): void
    {
        $versaoAtiva = app(VersionService::class)->versaoAtual();

        $user = User::factory()->create([
            'email' => 'recepcao@cerflow.gov.br',
            'password' => Hash::make('secret123'),
            'ativo' => true,
            'ultima_versao_novidades_vista' => $versaoAtiva,
        ]);
        $user->assignRole('recepcao');

        // Faz login via HTTP POST
        $response = $this->post('/login', [
            'email' => 'recepcao@cerflow.gov.br',
            'password' => 'secret123',
        ]);
        $response->assertRedirect('/');

        // Ao carregar o componente, não deve abrir
        Livewire::actingAs($user)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', false);
    }

    public function test_nova_versao_publicada_faz_modal_reaparecer_uma_unica_vez(): void
    {
        $versaoAtiva = app(VersionService::class)->versaoAtual();

        // Usuário que já havia visto uma versão antiga
        $user = User::factory()->create([
            'ativo' => true,
            'ultima_versao_novidades_vista' => '1.0.0',
        ]);
        $user->assignRole('terapeuta');

        // Modal deve abrir automaticamente porque 1.0.0 !== $versaoAtiva
        $componente = Livewire::actingAs($user)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', true);

        // Ao fechar, atualiza para a nova versão
        $componente->call('fecharModal')
            ->assertSet('aberto', false);

        $user->refresh();
        $this->assertSame($versaoAtiva, $user->ultima_versao_novidades_vista);

        // Em nova montagem, não abre novamente
        Livewire::actingAs($user)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', false);
    }

    public function test_usuarios_diferentes_possuem_controles_independentes_de_visualizacao(): void
    {
        $versaoAtiva = app(VersionService::class)->versaoAtual();

        $userA = User::factory()->create(['ativo' => true, 'ultima_versao_novidades_vista' => $versaoAtiva]);
        $userA->assignRole('terapeuta');

        $userB = User::factory()->create(['ativo' => true, 'ultima_versao_novidades_vista' => null]);
        $userB->assignRole('coordenacao');

        // Usuário A já viu a versão atual
        Livewire::actingAs($userA)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', false);

        // Usuário B ainda não viu e deve receber o modal
        Livewire::actingAs($userB)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', true);
    }

    public function test_botao_manual_de_novidades_continua_funcionando(): void
    {
        $versaoAtiva = app(VersionService::class)->versaoAtual();

        $user = User::factory()->create(['ativo' => true, 'ultima_versao_novidades_vista' => $versaoAtiva]);
        $user->assignRole('faturamento');

        // Inicia fechado pois já foi visto
        Livewire::actingAs($user)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', false)
            ->dispatch('abrir-modal-versao')
            ->assertSet('aberto', true);
    }

    public function test_link_central_de_ajuda_no_modal_persiste_visualizacao_antes_de_redirecionar(): void
    {
        $versaoAtiva = app(VersionService::class)->versaoAtual();

        $user = User::factory()->create([
            'ativo' => true,
            'ultima_versao_novidades_vista' => null,
        ]);
        $user->assignRole('terapeuta');

        // Ao clicar no link da Central de Ajuda, o modal deve persistir a versão e redirecionar
        Livewire::actingAs($user)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', true)
            ->call('irParaAjudaSobre')
            ->assertSet('aberto', false)
            ->assertRedirect(route('ajuda.sobre'));

        $user->refresh();
        $this->assertSame($versaoAtiva, $user->ultima_versao_novidades_vista);
    }

    public function test_version_service_mantem_consistencia_entre_versao_atual_e_release_atual(): void
    {
        $versionService = app(VersionService::class);

        $versaoAtual = $versionService->versaoAtual();
        $releaseAtual = $versionService->releaseAtual();

        $this->assertSame($versaoAtual, $releaseAtual['versao']);
        $this->assertNotEmpty($releaseAtual['titulo']);
        $this->assertNotEmpty($releaseAtual['destaques']);
        $this->assertSame($releaseAtual['versao'], $versionService->historicoVersoes()[0]['versao']);
    }

    public function test_modulo_ajuda_e_sobre_exibe_identidade_cer_digital_e_todas_as_versoes(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $user->assignRole('recepcao');

        $response = $this->actingAs($user)->get('/ajuda');

        $response->assertOk();
        $response->assertSee('Central de Ajuda & Sobre o CER Digital', false);
        $response->assertSee('v1.11.0');
        $response->assertSee('v1.10.0');
        $response->assertSee('v1.9.0');
        $response->assertSee('v1.8.0');
        $response->assertSee('v1.7.0');
        $response->assertSee('v1.6.2');
        $response->assertSee('v1.6.1');
        $response->assertSee('v1.6.0');
        $response->assertSee('v1.5.0');
        $response->assertSee('v1.4.0');
        $response->assertSee('v1.3.3');
        $response->assertSee('v1.3.2');
        $response->assertSee('v1.3.1');
        $response->assertSee('v1.3.0');
        $response->assertSee('v1.2.0');
        $response->assertSee('v1.1.4');
        $response->assertSee('v1.1.3');
        $response->assertSee('v1.1.2');
        $response->assertSee('v1.1.1');
        $response->assertSee('v1.1.0');
        $response->assertSee('v1.0.1');
        $response->assertSee('v1.0.0');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ConfiguracaoEstabelecimento;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingPageELoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_landing_page_publica_responde_200_sem_autenticacao(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('CER Digital')
            ->assertSee('Tecnologia a serviço do cuidado.')
            ->assertSee('Organize atendimentos, acompanhe pacientes')
            ->assertSee('Cuidado Integral em Reabilitação')
            ->assertSee('Como Funciona')
            ->assertSee('Segurança')
            ->assertSee('Entrar no Sistema');
    }

    public function test_landing_page_para_usuario_autenticado_exibe_atalho_ao_painel(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $user->assignRole('coordenacao');

        $response = $this->actingAs($user)->get('/');

        $response->assertOk()
            ->assertSee('Acessar o Painel');
    }

    public function test_tela_de_login_possui_elementos_visuais_e_retorno_a_home(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('Bem-vindo ao CER Digital')
            ->assertSee('Voltar à página inicial')
            ->assertSee('Manter conectado neste dispositivo')
            ->assertSee('Entrar no CER Digital')
            ->assertSee('E-mail institucional')
            ->assertSee('Senha de acesso');
    }

    public function test_login_autentica_com_sucesso_e_redireciona_ao_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'terapeuta@cer.saude.gov.br',
            'password' => bcrypt('SenhaCorreta@2026'),
            'ativo' => true,
        ]);
        $user->assignRole('terapeuta');

        $response = $this->post('/login', [
            'email' => 'terapeuta@cer.saude.gov.br',
            'password' => 'SenhaCorreta@2026',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_com_credenciais_invalidas_permanece_com_erro(): void
    {
        User::factory()->create([
            'email' => 'terapeuta@cer.saude.gov.br',
            'password' => bcrypt('SenhaCorreta@2026'),
            'ativo' => true,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'terapeuta@cer.saude.gov.br',
            'password' => 'SenhaErrada',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_suporte_a_logo_personalizada_na_landing_page_e_login(): void
    {
        Storage::fake('public');
        $logoFake = UploadedFile::fake()->image('logo_cer.png', 160, 60)->store('identidade-estabelecimento', 'public');

        ConfiguracaoEstabelecimento::atual()->update([
            'logo_path' => $logoFake,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('alt="Logo do estabelecimento"', false)
            ->assertSee(Storage::disk('public')->url($logoFake), false);

        $this->get('/login')
            ->assertOk()
            ->assertSee('alt="Logo do estabelecimento"', false)
            ->assertSee(Storage::disk('public')->url($logoFake), false);
    }
}

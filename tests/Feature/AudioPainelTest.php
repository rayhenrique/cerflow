<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ConfiguracaoEstabelecimento;
use App\Models\User;
use App\Services\TvPairingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AudioPainelTest extends TestCase
{
    use RefreshDatabase;

    private User $coordenador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->coordenador = User::factory()->create([
            'email' => 'coord.audio@cerflow.gov.br',
            'password' => Hash::make('password'),
            'ativo' => true,
        ]);
        $this->coordenador->assignRole('coordenacao');

        ConfiguracaoEstabelecimento::updateOrCreate(
            ['id' => 1],
            [
                'cnes' => '2000001',
                'codigo_ibge_padrao' => '270430',
                'painel_token_acesso' => 'TOKEN-TESTE-AUDIO-123',
                'painel_permitir_token_legado' => true,
            ]
        );
    }

    public function test_acesso_sem_autorizacao_retorna_403(): void
    {
        $response = $this->get('/painel-chamadas/audio/fala?texto=Teste');
        $response->assertStatus(403);

        $statusResponse = $this->get('/painel-chamadas/audio/status');
        $statusResponse->assertStatus(403);
    }

    public function test_acesso_autorizado_sem_texto_retorna_400(): void
    {
        $response = $this->actingAs($this->coordenador)
            ->get('/painel-chamadas/audio/fala?texto=');

        $response->assertStatus(400);
    }

    public function test_acesso_autorizado_com_sessao_retorna_audio_wav_valido(): void
    {
        $response = $this->actingAs($this->coordenador)
            ->get('/painel-chamadas/audio/fala?texto=Paciente+Carlos+Eduardo');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'audio/wav');
        $this->assertStringStartsWith('RIFF', $response->getContent());
    }

    public function test_acesso_autorizado_via_cookie_de_smart_tv_pareada(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();
        $service->autorizarCodigo($dados['codigo'], 'Smart TV Philips Sala 1', $this->coordenador);
        $res = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);
        $tokenCookie = $res['token'];

        $response = $this->withCookie(TvPairingService::COOKIE_NAME, $tokenCookie)
            ->get('/painel-chamadas/audio/fala?texto=Chamada+da+Recepcao');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'audio/wav');
        $this->assertStringStartsWith('RIFF', $response->getContent());
    }

    public function test_acesso_autorizado_via_token_legado_quando_habilitado(): void
    {
        $response = $this->get('/painel-chamadas/audio/fala?token=TOKEN-TESTE-AUDIO-123&texto=Paciente+Teste');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'audio/wav');
    }

    public function test_endpoint_status_retorna_informacoes_do_driver_local(): void
    {
        $response = $this->actingAs($this->coordenador)
            ->getJson('/painel-chamadas/audio/status');

        $response->assertOk();
        $response->assertJsonStructure([
            'disponivel',
            'driver',
            'descricao',
        ]);
    }
}

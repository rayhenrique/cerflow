<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DadosFicticiosLocaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulosAcessoLocalTest extends TestCase
{
    use RefreshDatabase;

    public function test_todos_os_modulos_carregam_com_sucesso(): void
    {
        $this->seed(DadosFicticiosLocaisSeeder::class);

        $coordenacao = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();
        $recepcao = User::where('email', 'recepcao@cerflow.gov.br')->firstOrFail();
        $fisioterapeuta = User::where('email', 'fisioterapeuta@cerflow.gov.br')->firstOrFail();
        $faturamento = User::where('email', 'faturamento@cerflow.gov.br')->firstOrFail();

        // 1. Dashboard Inicial (Coordenação)
        $this->actingAs($coordenacao)->get('/dashboard')->assertOk();

        // 2. Recepção e Check-in (redireciona para /agenda?aba=dia)
        $this->actingAs($recepcao)->followingRedirects()->get('/recepcao')->assertOk();

        // 3. Grade Semanal (redireciona para /agenda?aba=grade)
        $this->actingAs($coordenacao)->followingRedirects()->get('/grade')->assertOk();

        // 4. Agenda Mensal (redireciona para /agenda?aba=mensal)
        $this->actingAs($coordenacao)->followingRedirects()->get('/agenda/mensal')->assertOk();

        // 5. Prontuário Multiprofissional PEP (Terapeuta)
        $this->actingAs($fisioterapeuta)->get('/prontuario')->assertOk();

        // 6. Faturamento BPA-i
        $this->actingAs($faturamento)->get('/faturamento')->assertOk();

        // 7. Configurações e Abas
        $this->actingAs($coordenacao)->get('/configuracoes')->assertOk();
        $this->actingAs($coordenacao)->get('/configuracoes/pacientes')->assertOk();
        $this->actingAs($coordenacao)->get('/configuracoes/profissionais')->assertOk();
        $this->actingAs($coordenacao)->get('/configuracoes/salas')->assertOk();
        $this->actingAs($coordenacao)->get('/configuracoes/chamadas')->assertOk();

        // 8. Painel de Chamadas para TV (sem auth via token ou autenticado)
        $this->get('/painel-chamadas?token=TV-CER-DIGITAL-TOKEN-DEMO')->assertOk();
        $this->actingAs($recepcao)->get('/painel-chamadas')->assertOk();

        // 9. Central de Ajuda
        $this->actingAs($coordenacao)->get('/ajuda')->assertOk();
    }
}

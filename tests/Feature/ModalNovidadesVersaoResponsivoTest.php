<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ModalNovidadesVersao;
use App\Models\User;
use App\Services\VersionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModalNovidadesVersaoResponsivoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_modal_renderiza_com_estrutura_flex_col_e_limite_de_altura_responsiva(): void
    {
        $user = User::factory()->create(['ativo' => true, 'ultima_versao_novidades_vista' => null]);
        $user->assignRole('terapeuta');

        $componente = Livewire::actingAs($user)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', true);

        $html = $componente->html();

        // Container principal do modal com Flexbox e limite de 85vh
        $this->assertStringContainsString('flex flex-col', $html);
        $this->assertStringContainsString('max-h-[85vh]', $html);
        $this->assertStringContainsString('max-h-[85dvh]', $html);
        $this->assertStringContainsString('max-w-lg sm:max-w-xl', $html);
        $this->assertStringContainsString('min(85vh, 85dvh', $html);

        // Acessibilidade ARIA e diálogo
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('aria-labelledby="titulo-modal-novidades"', $html);
        $this->assertStringContainsString('id="titulo-modal-novidades"', $html);

        // Cabeçalho fixo (shrink-0)
        $this->assertStringContainsString('shrink-0 bg-white', $html);
        $this->assertStringContainsString('Novidades da Versão', $html);
        $this->assertStringContainsString('aria-label="Fechar janela de novidades"', $html);

        // Área central rolável exclusivamente (flex-1, min-h-0, overflow-y-auto, overscroll-contain)
        $this->assertStringContainsString('flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-contain', $html);

        // Rodapé fixo (shrink-0, flex-col-reverse para mobile, sm:flex-row para desktop)
        $this->assertStringContainsString('flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between', $html);
        $this->assertStringContainsString('Entendi, continuar', $html);
        $this->assertStringContainsString('Ver evolução completa das versões →', $html);

        // Bloqueio de rolagem do fundo no Alpine
        $this->assertStringContainsString("document.body.classList.add('overflow-hidden')", $html);
        $this->assertStringContainsString("document.body.classList.remove('overflow-hidden')", $html);
    }

    public function test_modal_preserva_todos_os_destaques_da_versao_mesmo_com_textos_extensos(): void
    {
        $mockVersionService = $this->createMock(VersionService::class);
        $mockVersionService->method('versaoAtual')->willReturn('1.99.0');
        $mockVersionService->method('releaseAtual')->willReturn([
            'versao' => '1.99.0',
            'data' => '11/11/2026',
            'titulo' => 'Versão com Grande Volume de Mudanças',
            'tipo' => 'Novidades e Otimizações',
            'destaques' => [
                'Item 1: Descrição muito longa com múltiplos detalhes técnicos que necessitam de várias linhas para serem explicados adequadamente aos usuários do CER Digital.',
                'Item 2: Implementação de segurança avançada e conformidade LGPD em todos os módulos ambulatoriais.',
                'Item 3: Sincronização em tempo real de filas de atendimento no Painel TV.',
                'Item 4: Otimização do faturamento BPA-i para alta volumetria de procedimentos SUS.',
                'Item 5: Integração resiliente com e-SUS PEC via jobs assíncronos e verificação de integridade.',
                'Item 6: Suporte aprimorado para Smart TVs com controle remoto e áudio embarcado.',
                'Item 7: Novo modal de novidades com scrollbar inteligente e layout responsivo que nunca oculta o título nem o botão continuar.',
                'Item 8: Refatoração das rotas operacionais e segurança de sessão.',
                'Item 9: Melhorias visuais e tipografia moderna baseada em Instrument Sans.',
                'Item 10: Limpeza de caches obsoletos e consolidação de consultas SQL.',
            ],
        ]);
        $this->app->instance(VersionService::class, $mockVersionService);

        $user = User::factory()->create(['ativo' => true, 'ultima_versao_novidades_vista' => null]);
        $user->assignRole('coordenacao');

        $componente = Livewire::actingAs($user)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', true);

        // Verifica que todos os 10 destaques estão renderizados
        for ($i = 1; $i <= 10; $i++) {
            $componente->assertSee("Item {$i}:");
        }

        // Título e botão Continuar continuam presentes
        $componente->assertSee('Novidades da Versão — Versão com Grande Volume de Mudanças');
        $componente->assertSee('Entendi, continuar');
        $componente->assertSee('Ver evolução completa das versões →');
    }

    public function test_fechamento_do_modal_persiste_versao_e_fecha_janela(): void
    {
        $user = User::factory()->create(['ativo' => true, 'ultima_versao_novidades_vista' => null]);
        $user->assignRole('recepcao');

        $componente = Livewire::actingAs($user)
            ->test(ModalNovidadesVersao::class)
            ->assertSet('aberto', true);

        $componente->call('fecharModal')
            ->assertSet('aberto', false);

        $user->refresh();
        $this->assertNotNull($user->ultima_versao_novidades_vista);
    }
}

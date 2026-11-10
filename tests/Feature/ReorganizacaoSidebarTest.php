<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\NavigationMenu;
use Database\Seeders\DadosFicticiosLocaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReorganizacaoSidebarTest extends TestCase
{
    use RefreshDatabase;

    private NavigationMenu $menu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DadosFicticiosLocaisSeeder::class);
        $this->menu = new NavigationMenu;
    }

    public function test_ordem_exata_dos_grupos_e_itens_na_definicao_centralizada(): void
    {
        $grupos = $this->menu->todosGrupos();

        $this->assertCount(6, $grupos);

        // 1. INÍCIO
        $this->assertSame('inicio', $grupos[0]->id);
        $this->assertSame('Início', $grupos[0]->titulo);
        $this->assertFalse($grupos[0]->expansivel);
        $this->assertTrue($grupos[0]->padraoAberto);
        $itensInicio = $grupos[0]->itens;
        $this->assertCount(1, $itensInicio);
        $this->assertSame('dashboard', $itensInicio[0]->id);
        $this->assertSame('Painel Inicial', $itensInicio[0]->rotulo);
        $this->assertSame('dashboard', $itensInicio[0]->rota);

        // 2. ATENDIMENTO
        $this->assertSame('atendimento', $grupos[1]->id);
        $this->assertSame('Atendimento', $grupos[1]->titulo);
        $this->assertTrue($grupos[1]->expansivel);
        $this->assertTrue($grupos[1]->padraoAberto);
        $itensAtendimento = $grupos[1]->itens;
        $this->assertCount(4, $itensAtendimento);
        $this->assertSame(['agenda', 'pacientes', 'prontuario', 'painel_chamadas'], array_column($itensAtendimento, 'id'));
        $this->assertSame(['Agenda Operacional', 'Pacientes', 'Prontuário Eletrônico', 'Painel de Chamadas'], array_column($itensAtendimento, 'rotulo'));

        // 3. GESTÃO
        $this->assertSame('gestao', $grupos[2]->id);
        $this->assertSame('Gestão', $grupos[2]->titulo);
        $this->assertTrue($grupos[2]->expansivel);
        $this->assertTrue($grupos[2]->padraoAberto);
        $itensGestao = $grupos[2]->itens;
        $this->assertCount(2, $itensGestao);
        $this->assertSame(['relatorios', 'faturamento'], array_column($itensGestao, 'id'));
        $this->assertSame(['Relatórios e Indicadores', 'Faturamento BPA-i'], array_column($itensGestao, 'rotulo'));

        // 4. CADASTROS (Recolhido inicialmente)
        $this->assertSame('cadastros', $grupos[3]->id);
        $this->assertSame('Cadastros', $grupos[3]->titulo);
        $this->assertTrue($grupos[3]->expansivel);
        $this->assertFalse($grupos[3]->padraoAberto);
        $itensCadastros = $grupos[3]->itens;
        $this->assertCount(5, $itensCadastros);
        $this->assertSame(['profissionais', 'salas', 'cbos', 'ubs', 'sigtap'], array_column($itensCadastros, 'id'));
        $this->assertSame(['Equipe e Profissionais', 'Salas e Espaços', 'CBOs e Especialidades', 'Unidades de Saúde (UBS)', 'Procedimentos SIGTAP'], array_column($itensCadastros, 'rotulo'));

        // 5. ADMINISTRAÇÃO (Recolhido inicialmente)
        $this->assertSame('administracao', $grupos[4]->id);
        $this->assertSame('Administração', $grupos[4]->titulo);
        $this->assertTrue($grupos[4]->expansivel);
        $this->assertFalse($grupos[4]->padraoAberto);
        $itensAdmin = $grupos[4]->itens;
        $this->assertCount(4, $itensAdmin);
        $this->assertSame(['estabelecimento', 'esus', 'painel_tv_config', 'auditoria'], array_column($itensAdmin, 'id'));
        $this->assertSame(['Dados do Estabelecimento', 'Integração e-SUS PEC', 'Configuração do Painel TV', 'Auditoria e Segurança'], array_column($itensAdmin, 'rotulo'));

        // 6. CONTA E SUPORTE
        $this->assertSame('conta_suporte', $grupos[5]->id);
        $this->assertSame('Conta e Suporte', $grupos[5]->titulo);
        $this->assertFalse($grupos[5]->expansivel);
        $this->assertTrue($grupos[5]->padraoAberto);
        $itensConta = $grupos[5]->itens;
        $this->assertCount(2, $itensConta);
        $this->assertSame(['perfil', 'ajuda'], array_column($itensConta, 'id'));
        $this->assertSame(['Minha Conta e PIN', 'Ajuda e Manual'], array_column($itensConta, 'rotulo'));
    }

    public function test_perfil_recepcao_visualiza_apenas_itens_autorizados_e_oculta_cadastros_e_administracao(): void
    {
        $recepcao = User::where('email', 'recepcao@cerflow.gov.br')->firstOrFail();

        $gruposUsuario = $this->menu->gruposParaUsuario($recepcao);
        $idsGrupos = array_column($gruposUsuario, 'id');

        // Recepção vê Início, Atendimento, Gestão, Conta e Suporte
        $this->assertContains('inicio', $idsGrupos);
        $this->assertContains('atendimento', $idsGrupos);
        $this->assertContains('gestao', $idsGrupos);
        $this->assertContains('conta_suporte', $idsGrupos);

        // Grupos sem nenhum item autorizado devem ser totalmente ocultados
        $this->assertNotContains('cadastros', $idsGrupos);
        $this->assertNotContains('administracao', $idsGrupos);

        // Em Atendimento, recepção vê Agenda, Pacientes e Painel TV, mas não PEP Multi
        $grupoAtendimento = collect($gruposUsuario)->firstWhere('id', 'atendimento');
        $itensAtendimento = array_column($grupoAtendimento->itensAutorizados($recepcao), 'id');
        $this->assertContains('agenda', $itensAtendimento);
        $this->assertContains('pacientes', $itensAtendimento);
        $this->assertContains('painel_chamadas', $itensAtendimento);
        $this->assertNotContains('prontuario', $itensAtendimento);

        // Em Gestão, recepção vê Relatórios, mas não BPA-i
        $grupoGestao = collect($gruposUsuario)->firstWhere('id', 'gestao');
        $itensGestao = array_column($grupoGestao->itensAutorizados($recepcao), 'id');
        $this->assertContains('relatorios', $itensGestao);
        $this->assertNotContains('faturamento', $itensGestao);
    }

    public function test_perfil_terapeuta_visualiza_apenas_itens_autorizados_e_oculta_cadastros_e_administracao(): void
    {
        $terapeuta = User::where('email', 'fisioterapeuta@cerflow.gov.br')->firstOrFail();

        $gruposUsuario = $this->menu->gruposParaUsuario($terapeuta);
        $idsGrupos = array_column($gruposUsuario, 'id');

        $this->assertContains('inicio', $idsGrupos);
        $this->assertContains('atendimento', $idsGrupos);
        $this->assertContains('gestao', $idsGrupos);
        $this->assertContains('conta_suporte', $idsGrupos);
        $this->assertNotContains('cadastros', $idsGrupos);
        $this->assertNotContains('administracao', $idsGrupos);

        // Terapeuta vê PEP e Painel TV, mas não vê cadastro de pacientes
        $grupoAtendimento = collect($gruposUsuario)->firstWhere('id', 'atendimento');
        $itensAtendimento = array_column($grupoAtendimento->itensAutorizados($terapeuta), 'id');
        $this->assertContains('agenda', $itensAtendimento);
        $this->assertContains('prontuario', $itensAtendimento);
        $this->assertContains('painel_chamadas', $itensAtendimento);
        $this->assertNotContains('pacientes', $itensAtendimento);
    }

    public function test_perfil_faturamento_visualiza_apenas_sigtap_em_cadastros_e_oculta_administracao(): void
    {
        $faturamento = User::where('email', 'faturamento@cerflow.gov.br')->firstOrFail();

        $gruposUsuario = $this->menu->gruposParaUsuario($faturamento);
        $idsGrupos = array_column($gruposUsuario, 'id');

        $this->assertContains('inicio', $idsGrupos);
        $this->assertContains('atendimento', $idsGrupos);
        $this->assertContains('gestao', $idsGrupos);
        $this->assertContains('cadastros', $idsGrupos);
        $this->assertContains('conta_suporte', $idsGrupos);
        $this->assertNotContains('administracao', $idsGrupos);

        // Em cadastros, faturamento tem acesso unicamente a SIGTAP
        $grupoCadastros = collect($gruposUsuario)->firstWhere('id', 'cadastros');
        $itensCadastros = array_column($grupoCadastros->itensAutorizados($faturamento), 'id');
        $this->assertSame(['sigtap'], $itensCadastros);

        // Em gestão, vê relatórios e BPA-i
        $grupoGestao = collect($gruposUsuario)->firstWhere('id', 'gestao');
        $itensGestao = array_column($grupoGestao->itensAutorizados($faturamento), 'id');
        $this->assertContains('relatorios', $itensGestao);
        $this->assertContains('faturamento', $itensGestao);
    }

    public function test_perfil_coordenacao_visualiza_todos_os_grupos_e_itens(): void
    {
        $coordenacao = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();

        $gruposUsuario = $this->menu->gruposParaUsuario($coordenacao);
        $idsGrupos = array_column($gruposUsuario, 'id');

        $this->assertSame(['inicio', 'atendimento', 'gestao', 'cadastros', 'administracao', 'conta_suporte'], $idsGrupos);

        $totalItens = 0;
        foreach ($gruposUsuario as $grupo) {
            $totalItens += count($grupo->itensAutorizados($coordenacao));
        }

        // 1 (início) + 4 (atendimento) + 2 (gestão) + 5 (cadastros) + 4 (administração) + 2 (conta) = 18 itens
        $this->assertSame(18, $totalItens);
    }

    public function test_deteccao_correta_do_item_ativo_e_grupo_ativo(): void
    {
        $coordenacao = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();

        // 1. /dashboard
        $req = Request::create('/dashboard', 'GET');
        $req->setUserResolver(fn () => $coordenacao);
        $route = app('router')->getRoutes()->match($req);
        $req->setRouteResolver(fn () => $route);
        $this->assertSame('dashboard', $this->menu->itemAtivo($req, $coordenacao)?->id);
        $this->assertSame('inicio', $this->menu->grupoAtivoId($req, $coordenacao));

        // 2. /configuracoes/pacientes -> Deve ativar Pacientes em ATENDIMENTO
        $req = Request::create('/configuracoes/pacientes', 'GET');
        $req->setUserResolver(fn () => $coordenacao);
        $route = app('router')->getRoutes()->match($req);
        $req->setRouteResolver(fn () => $route);
        $this->assertSame('pacientes', $this->menu->itemAtivo($req, $coordenacao)?->id);
        $this->assertSame('atendimento', $this->menu->grupoAtivoId($req, $coordenacao));

        // 3. /configuracoes/cbos -> Deve ativar CBOs em CADASTROS
        $req = Request::create('/configuracoes/cbos', 'GET');
        $req->setUserResolver(fn () => $coordenacao);
        $route = app('router')->getRoutes()->match($req);
        $req->setRouteResolver(fn () => $route);
        $this->assertSame('cbos', $this->menu->itemAtivo($req, $coordenacao)?->id);
        $this->assertSame('cadastros', $this->menu->grupoAtivoId($req, $coordenacao));

        // 4. /configuracoes/esus -> Deve ativar e-SUS em ADMINISTRAÇÃO
        $req = Request::create('/configuracoes/esus', 'GET');
        $req->setUserResolver(fn () => $coordenacao);
        $route = app('router')->getRoutes()->match($req);
        $req->setRouteResolver(fn () => $route);
        $this->assertSame('esus', $this->menu->itemAtivo($req, $coordenacao)?->id);
        $this->assertSame('administracao', $this->menu->grupoAtivoId($req, $coordenacao));

        // 5. /configuracoes/perfil -> Deve ativar Minha Conta em CONTA_SUPORTE
        $req = Request::create('/configuracoes/perfil', 'GET');
        $req->setUserResolver(fn () => $coordenacao);
        $route = app('router')->getRoutes()->match($req);
        $req->setRouteResolver(fn () => $route);
        $this->assertSame('perfil', $this->menu->itemAtivo($req, $coordenacao)?->id);
        $this->assertSame('conta_suporte', $this->menu->grupoAtivoId($req, $coordenacao));

        // 6. /agenda?aba=grade -> Mantém Agenda Operacional ativa em ATENDIMENTO
        $req = Request::create('/agenda?aba=grade', 'GET');
        $req->setUserResolver(fn () => $coordenacao);
        $route = app('router')->getRoutes()->match($req);
        $req->setRouteResolver(fn () => $route);
        $this->assertSame('agenda', $this->menu->itemAtivo($req, $coordenacao)?->id);
        $this->assertSame('atendimento', $this->menu->grupoAtivoId($req, $coordenacao));
    }

    public function test_abertura_automatica_do_grupo_contendo_item_ativo(): void
    {
        $coordenacao = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();

        // No dashboard, Cadastros e Administração iniciam recolhidos
        $req = Request::create('/dashboard', 'GET');
        $req->setUserResolver(fn () => $coordenacao);
        $route = app('router')->getRoutes()->match($req);
        $req->setRouteResolver(fn () => $route);
        $estados = $this->menu->estadoInicialGrupos($req, $coordenacao);
        $this->assertTrue($estados['inicio']);
        $this->assertTrue($estados['atendimento']);
        $this->assertTrue($estados['gestao']);
        $this->assertFalse($estados['cadastros']);
        $this->assertFalse($estados['administracao']);

        // Ao navegar para /configuracoes/salas, o grupo Cadastros DEVE iniciar aberto
        $reqSalas = Request::create('/configuracoes/salas', 'GET');
        $reqSalas->setUserResolver(fn () => $coordenacao);
        $routeSalas = app('router')->getRoutes()->match($reqSalas);
        $reqSalas->setRouteResolver(fn () => $routeSalas);
        $estadosSalas = $this->menu->estadoInicialGrupos($reqSalas, $coordenacao);
        $this->assertTrue($estadosSalas['cadastros'], 'Cadastros deve abrir automaticamente quando contiver a página ativa');
        $this->assertFalse($estadosSalas['administracao'], 'Administração deve permanecer fechada');

        // Ao navegar para /configuracoes/auditoria, o grupo Administração DEVE iniciar aberto
        $reqAudit = Request::create('/configuracoes/auditoria', 'GET');
        $reqAudit->setUserResolver(fn () => $coordenacao);
        $routeAudit = app('router')->getRoutes()->match($reqAudit);
        $reqAudit->setRouteResolver(fn () => $routeAudit);
        $estadosAudit = $this->menu->estadoInicialGrupos($reqAudit, $coordenacao);
        $this->assertTrue($estadosAudit['administracao'], 'Administração deve abrir automaticamente quando contiver a página ativa');
        $this->assertFalse($estadosAudit['cadastros'], 'Cadastros deve permanecer fechado');
    }

    public function test_links_do_menu_carregam_sem_erro_404(): void
    {
        $coordenacao = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();

        $grupos = $this->menu->gruposParaUsuario($coordenacao);

        foreach ($grupos as $grupo) {
            foreach ($grupo->itensAutorizados($coordenacao) as $item) {
                // Testa cada URL gerada pelo item
                $url = $item->url();
                $this->assertNotEmpty($url);

                $response = $this->actingAs($coordenacao)->get($url);
                $this->assertNotSame(404, $response->status(), "Link {$item->rotulo} ({$url}) retornou 404");
                $this->assertTrue(in_array($response->status(), [200, 302], true), "Link {$item->rotulo} retornou status inesperado {$response->status()}");
            }
        }
    }

    public function test_bloqueio_de_acesso_direto_a_paginas_nao_autorizadas(): void
    {
        $recepcao = User::where('email', 'recepcao@cerflow.gov.br')->firstOrFail();
        $faturamento = User::where('email', 'faturamento@cerflow.gov.br')->firstOrFail();

        // Recepção não pode acessar salas nem auditoria (exclusivo coordenação)
        $this->actingAs($recepcao)->get('/configuracoes/salas')->assertForbidden();
        $this->actingAs($recepcao)->get('/configuracoes/auditoria')->assertForbidden();

        // Faturamento não pode acessar o PEP
        $this->actingAs($faturamento)->get('/prontuario')->assertForbidden();
    }

    public function test_renderizacao_do_layout_com_novo_sidebar_desktop_e_drawer_mobile(): void
    {
        $coordenacao = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();

        $response = $this->actingAs($coordenacao)->get('/dashboard');

        $response->assertOk();

        // Títulos de seção obrigatórios
        $response->assertSee('Início');
        $response->assertSee('Atendimento');
        $response->assertSee('Gestão');
        $response->assertSee('Cadastros');
        $response->assertSee('Administração');
        $response->assertSee('Conta e Suporte');

        // Itens essenciais
        $response->assertSee('Painel Inicial');
        $response->assertSee('Agenda Operacional');
        $response->assertSee('Pacientes');
        $response->assertSee('Prontuário Eletrônico');
        $response->assertSee('Painel de Chamadas');
        $response->assertSee('Relatórios e Indicadores');
        $response->assertSee('Faturamento BPA-i');
        $response->assertSee('Equipe e Profissionais');
        $response->assertSee('Salas e Espaços');
        $response->assertSee('CBOs e Especialidades');
        $response->assertSee('Unidades de Saúde (UBS)');
        $response->assertSee('Procedimentos SIGTAP');
        $response->assertSee('Dados do Estabelecimento');
        $response->assertSee('Integração e-SUS PEC');
        $response->assertSee('Configuração do Painel TV');
        $response->assertSee('Auditoria e Segurança');
        $response->assertSee('Minha Conta e PIN');
        $response->assertSee('Ajuda e Manual');

        // Logout e usuário
        $response->assertSee('Sair');
        $response->assertSee($coordenacao->name);
    }
}

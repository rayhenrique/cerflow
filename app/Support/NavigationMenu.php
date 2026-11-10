<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Services\VersionService;
use App\Support\Navigation\NavigationGroup;
use App\Support\Navigation\NavigationItem;
use Illuminate\Http\Request;

class NavigationMenu
{
    /**
     * Retorna a lista completa de grupos da nova hierarquia funcional do CER Digital:
     * 1. INÍCIO
     * 2. ATENDIMENTO
     * 3. GESTÃO
     * 4. CADASTROS
     * 5. ADMINISTRAÇÃO
     * 6. RODAPÉ — CONTA E SUPORTE
     *
     * @return array<int, NavigationGroup>
     */
    public function todosGrupos(): array
    {
        return [
            $this->grupoInicio(),
            $this->grupoAtendimento(),
            $this->grupoGestao(),
            $this->grupoCadastros(),
            $this->grupoAdministracao(),
            $this->grupoContaSuporte(),
        ];
    }

    /**
     * Retorna apenas os grupos que possuem ao menos um item autorizado para o usuário.
     * Grupos vazios são ocultados automaticamente.
     *
     * @return array<int, NavigationGroup>
     */
    public function gruposParaUsuario(?User $user): array
    {
        $grupos = array_filter(
            $this->todosGrupos(),
            fn (NavigationGroup $grupo): bool => $grupo->visivelPara($user)
        );

        usort($grupos, fn (NavigationGroup $a, NavigationGroup $b): int => $a->ordem <=> $b->ordem);

        return array_values($grupos);
    }

    /**
     * Retorna apenas os grupos principais de navegação (excluindo o rodapé de conta e suporte).
     *
     * @return array<int, NavigationGroup>
     */
    public function gruposPrincipaisParaUsuario(?User $user): array
    {
        return array_values(array_filter(
            $this->gruposParaUsuario($user),
            fn (NavigationGroup $grupo): bool => $grupo->id !== 'conta_suporte'
        ));
    }

    /**
     * Retorna o grupo de rodapé de conta e suporte para o usuário.
     */
    public function grupoContaSuporteParaUsuario(?User $user): ?NavigationGroup
    {
        foreach ($this->gruposParaUsuario($user) as $grupo) {
            if ($grupo->id === 'conta_suporte') {
                return $grupo;
            }
        }

        return null;
    }

    public function itemAtivo(Request $request, ?User $user = null): ?NavigationItem
    {
        foreach ($this->gruposParaUsuario($user) as $grupo) {
            foreach ($grupo->itensAutorizados($user) as $item) {
                if ($item->estaAtivo($request)) {
                    return $item;
                }
            }
        }

        return null;
    }

    public function grupoAtivo(Request $request, ?User $user = null): ?NavigationGroup
    {
        foreach ($this->gruposParaUsuario($user) as $grupo) {
            if ($grupo->temItemAtivo($request, $user)) {
                return $grupo;
            }
        }

        return null;
    }

    public function grupoAtivoId(Request $request, ?User $user = null): ?string
    {
        return $this->grupoAtivo($request, $user)?->id;
    }

    /**
     * Retorna o mapa de abertura inicial para cada grupo:
     * - INÍCIO, ATENDIMENTO, GESTÃO e CONTA_SUPORTE iniciam abertos.
     * - CADASTROS e ADMINISTRAÇÃO iniciam recolhidos, a menos que contenham o item ativo.
     *
     * @return array<string, bool>
     */
    public function estadoInicialGrupos(Request $request, ?User $user = null): array
    {
        $estados = [];
        foreach ($this->gruposParaUsuario($user) as $grupo) {
            $estados[$grupo->id] = $grupo->deveIniciarAberto($request, $user);
        }

        return $estados;
    }

    // =========================================================================
    // DEFINIÇÃO DOS GRUPOS E ITENS
    // =========================================================================

    public function grupoInicio(): NavigationGroup
    {
        $grupo = new NavigationGroup(
            id: 'inicio',
            titulo: 'Início',
            ordem: 10,
            expansivel: false,
            padraoAberto: true,
        );

        $grupo->adicionarItem(new NavigationItem(
            id: 'dashboard',
            grupo: 'inicio',
            rotulo: 'Painel Inicial',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />',
            rota: 'dashboard',
            perfis: ['recepcao', 'terapeuta', 'faturamento', 'coordenacao'],
            ordem: 10,
            tooltip: 'Painel Inicial',
            regraAtivo: fn (Request $r): bool => $r->routeIs('dashboard'),
        ));

        return $grupo;
    }

    public function grupoAtendimento(): NavigationGroup
    {
        $grupo = new NavigationGroup(
            id: 'atendimento',
            titulo: 'Atendimento',
            ordem: 20,
            expansivel: true,
            padraoAberto: true,
        );

        $grupo->adicionarItem(new NavigationItem(
            id: 'agenda',
            grupo: 'atendimento',
            rotulo: 'Agenda Operacional',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm3-3h.008v.008H15V12zm0 3h.008v.008H15V15zm-6-3h.008v.008H9V12zm0 3h.008v.008H9V15z" />',
            rota: 'agenda.operacional',
            perfis: ['recepcao', 'terapeuta', 'faturamento', 'coordenacao'],
            ordem: 10,
            tooltip: 'Agenda Operacional (Dia, Grade e Mês)',
            regraAtivo: fn (Request $r): bool => $r->routeIs('agenda.operacional')
                || $r->routeIs('grade.*')
                || $r->routeIs('agenda.mensal')
                || $r->routeIs('recepcao.*'),
        ));

        $grupo->adicionarItem(new NavigationItem(
            id: 'pacientes',
            grupo: 'atendimento',
            rotulo: 'Pacientes',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />',
            rota: 'configuracoes.secao',
            parametros: ['secao' => 'pacientes'],
            perfis: ['recepcao', 'coordenacao'],
            ordem: 20,
            tooltip: 'Pacientes SUS',
        ));

        $grupo->adicionarItem(new NavigationItem(
            id: 'prontuario',
            grupo: 'atendimento',
            rotulo: 'Prontuário Eletrônico',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7v6m-3-3h6" />',
            rota: 'prontuario.index',
            perfis: ['terapeuta', 'coordenacao'],
            ordem: 30,
            tooltip: 'Prontuário Eletrônico do Paciente (PEP Multi)',
            regraAtivo: fn (Request $r): bool => $r->routeIs('prontuario.*'),
        ));

        $grupo->adicionarItem(new NavigationItem(
            id: 'painel_chamadas',
            grupo: 'atendimento',
            rotulo: 'Painel de Chamadas',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />',
            rota: 'painel.chamadas',
            perfis: ['recepcao', 'terapeuta', 'coordenacao'],
            ordem: 40,
            target: '_blank',
            badge: 'TV',
            tooltip: 'Painel de Chamadas para TV (Abrir em nova aba)',
            regraAtivo: fn (Request $r): bool => $r->routeIs('painel.chamadas'),
        ));

        return $grupo;
    }

    public function grupoGestao(): NavigationGroup
    {
        $grupo = new NavigationGroup(
            id: 'gestao',
            titulo: 'Gestão',
            ordem: 30,
            expansivel: true,
            padraoAberto: true,
        );

        $grupo->adicionarItem(new NavigationItem(
            id: 'relatorios',
            grupo: 'gestao',
            rotulo: 'Relatórios e Indicadores',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h14.25M6 13.5l4.5-4.5 3.75 3.75 5.25-5.25" />',
            rota: 'relatorios.index',
            perfis: ['recepcao', 'terapeuta', 'faturamento', 'coordenacao'],
            ordem: 10,
            tooltip: 'Relatórios e Indicadores Gerenciais',
            regraAtivo: fn (Request $r): bool => $r->routeIs('relatorios.*'),
        ));

        $grupo->adicionarItem(new NavigationItem(
            id: 'faturamento',
            grupo: 'gestao',
            rotulo: 'Faturamento BPA-i',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />',
            rota: 'faturamento.bpai',
            perfis: ['faturamento', 'coordenacao'],
            ordem: 20,
            tooltip: 'Faturamento BPA-i',
            regraAtivo: fn (Request $r): bool => $r->routeIs('faturamento.*'),
        ));

        return $grupo;
    }

    public function grupoCadastros(): NavigationGroup
    {
        $grupo = new NavigationGroup(
            id: 'cadastros',
            titulo: 'Cadastros',
            ordem: 40,
            expansivel: true,
            padraoAberto: false,
        );

        $grupo->adicionarItem(new NavigationItem(
            id: 'profissionais',
            grupo: 'cadastros',
            rotulo: 'Equipe e Profissionais',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.364a4.125 4.125 0 00-6.338 0 .375.375 0 01-.295.136h6.928a.375.375 0 01-.295-.136z" />',
            rota: 'configuracoes.secao',
            parametros: ['secao' => 'profissionais'],
            perfis: ['coordenacao'],
            ordem: 10,
            tooltip: 'Equipe e Profissionais',
        ));

        $grupo->adicionarItem(new NavigationItem(
            id: 'salas',
            grupo: 'cadastros',
            rotulo: 'Salas e Espaços',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.25m11.25 0H18m2.25 0h.75m-18 0h.75m0 0V5.25A2.25 2.25 0 015.25 3h13.5A2.25 2.25 0 0121 5.25V21m-18 0h18" />',
            rota: 'configuracoes.secao',
            parametros: ['secao' => 'salas'],
            perfis: ['coordenacao'],
            ordem: 20,
            tooltip: 'Salas e Espaços',
        ));

        $grupo->adicionarItem(new NavigationItem(
            id: 'cbos',
            grupo: 'cadastros',
            rotulo: 'CBOs e Especialidades',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v1.069m7.5 0c.98.057 1.95.132 2.91.225m-13.32 0A48.655 48.655 0 0112 6.75c.98.057 1.95.132 2.91.225m-2.91-.225v.001M12 11.25v3.75m-1.875-1.875h3.75" />',
            rota: 'configuracoes.secao',
            parametros: ['secao' => 'cbos'],
            perfis: ['coordenacao'],
            ordem: 30,
            tooltip: 'CBOs e Especialidades',
        ));

        $grupo->adicionarItem(new NavigationItem(
            id: 'ubs',
            grupo: 'cadastros',
            rotulo: 'Unidades de Saúde (UBS)',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',
            rota: 'configuracoes.secao',
            parametros: ['secao' => 'ubs'],
            perfis: ['coordenacao'],
            ordem: 40,
            tooltip: 'Unidades de Saúde (UBS)',
        ));

        $grupo->adicionarItem(new NavigationItem(
            id: 'sigtap',
            grupo: 'cadastros',
            rotulo: 'Procedimentos SIGTAP',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />',
            rota: 'configuracoes.secao',
            parametros: ['secao' => 'sigtap'],
            perfis: ['faturamento', 'coordenacao'],
            ordem: 50,
            tooltip: 'Procedimentos SIGTAP',
        ));

        return $grupo;
    }

    public function grupoAdministracao(): NavigationGroup
    {
        $grupo = new NavigationGroup(
            id: 'administracao',
            titulo: 'Administração',
            ordem: 50,
            expansivel: true,
            padraoAberto: false,
        );

        $grupo->adicionarItem(new NavigationItem(
            id: 'estabelecimento',
            grupo: 'administracao',
            rotulo: 'Dados do Estabelecimento',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />',
            rota: 'configuracoes.secao',
            parametros: ['secao' => 'estabelecimento'],
            perfis: ['coordenacao'],
            ordem: 10,
            tooltip: 'Dados do Estabelecimento',
        ));

        $grupo->adicionarItem(new NavigationItem(
            id: 'esus',
            grupo: 'administracao',
            rotulo: 'Integração e-SUS PEC',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />',
            rota: 'configuracoes.secao',
            parametros: ['secao' => 'esus'],
            perfis: ['coordenacao'],
            ordem: 20,
            tooltip: 'Integração e-SUS PEC',
        ));

        $grupo->adicionarItem(new NavigationItem(
            id: 'painel_tv_config',
            grupo: 'administracao',
            rotulo: 'Configuração do Painel TV',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125z" />',
            rota: 'configuracoes.secao',
            parametros: ['secao' => 'chamadas'],
            perfis: ['coordenacao'],
            ordem: 30,
            tooltip: 'Configuração do Painel TV',
        ));

        $grupo->adicionarItem(new NavigationItem(
            id: 'auditoria',
            grupo: 'administracao',
            rotulo: 'Auditoria e Segurança',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
            rota: 'configuracoes.secao',
            parametros: ['secao' => 'auditoria'],
            perfis: ['coordenacao'],
            ordem: 40,
            tooltip: 'Auditoria e Segurança',
        ));

        return $grupo;
    }

    public function grupoContaSuporte(): NavigationGroup
    {
        $grupo = new NavigationGroup(
            id: 'conta_suporte',
            titulo: 'Conta e Suporte',
            ordem: 60,
            expansivel: false,
            padraoAberto: true,
        );

        $grupo->adicionarItem(new NavigationItem(
            id: 'perfil',
            grupo: 'conta_suporte',
            rotulo: 'Minha Conta e PIN',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />',
            rota: 'configuracoes.secao',
            parametros: ['secao' => 'perfil'],
            perfis: ['recepcao', 'terapeuta', 'faturamento', 'coordenacao'],
            ordem: 10,
            tooltip: 'Minha Conta e PIN',
        ));

        $versao = app(VersionService::class)->versaoAtual();

        $grupo->adicionarItem(new NavigationItem(
            id: 'ajuda',
            grupo: 'conta_suporte',
            rotulo: 'Ajuda e Manual',
            icone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />',
            rota: 'ajuda.sobre',
            perfis: ['recepcao', 'terapeuta', 'faturamento', 'coordenacao'],
            ordem: 20,
            badge: 'v'.$versao,
            tooltip: 'Ajuda e Manual',
            regraAtivo: fn (Request $r): bool => $r->routeIs('ajuda.*'),
        ));

        return $grupo;
    }
}

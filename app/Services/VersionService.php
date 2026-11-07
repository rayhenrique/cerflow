<?php

declare(strict_types=1);

namespace App\Services;

class VersionService
{
    /**
     * Retorna a versão atual lida do arquivo VERSION na raiz do projeto.
     */
    public function versaoAtual(): string
    {
        $caminho = base_path('VERSION');
        if (is_file($caminho)) {
            $conteudo = trim((string) file_get_contents($caminho));
            if ($conteudo !== '') {
                return $conteudo;
            }
        }

        return '1.3.1';
    }

    /**
     * Retorna os dados da versão mais recente para exibição no modal pós-login.
     *
     * @return array{versao: string, data: string, titulo: string, tipo: string, destaques: array<int, string>}
     */
    public function releaseAtual(): array
    {
        $historico = $this->historicoVersoes();

        return $historico[0];
    }

    /**
     * Retorna a linha do tempo completa de evolução das versões do CER Flow
     * em linguagem simples e acessível para todos os colaboradores da unidade.
     *
     * @return array<int, array{versao: string, data: string, titulo: string, tipo: string, destaques: array<int, string>}>
     */
    public function historicoVersoes(): array
    {
        return [
            [
                'versao' => '1.5.0',
                'data' => '07/11/2026',
                'titulo' => 'Novo Painel Inicial (Dashboard) com Estatísticas do CER em Tempo Real',
                'tipo' => 'Nova Funcionalidade',
                'destaques' => [
                    'Tela inicial com números atualizados da unidade: total de pacientes ativos, atendimentos agendados para hoje, presenças e faltas.',
                    'Gráfico de distribuição dos pacientes pelas 4 áreas do CER (Reabilitação Física, Intelectual/TEA, Auditiva e Visual).',
                    'Resumo mensal de comparecimento, faltas justificadas/injustificadas, ocupação das salas e situação do faturamento SUS (BPA-i).',
                    'Botões de acesso rápido para abrir a Grade Semanal, o Check-in da Recepção, o Prontuário e o Faturamento.',
                ],
            ],
            [
                'versao' => '1.4.0',
                'data' => '06/11/2026',
                'titulo' => 'Sistema 100% Adaptado para Celulares, Tablets e Computadores',
                'tipo' => 'Nova Funcionalidade',
                'destaques' => [
                    'Novo menu lateral deslizante no celular e tablet: basta tocar no ícone de três barras (☰) no topo para navegar por todos os módulos.',
                    'Tabelas, filtros, botões e janelas de cadastro ajustados para funcionar perfeitamente em telas pequenas sem cortar informações.',
                    'Melhoria na visualização da Grade Semanal, Check-in da Recepção, Prontuário e Configurações em dispositivos móveis.',
                ],
            ],
            [
                'versao' => '1.3.3',
                'data' => '06/11/2026',
                'titulo' => 'Aviso Automático de Nova Versão Mesmo para Quem Já Está Logado',
                'tipo' => 'Melhoria de Uso',
                'destaques' => [
                    'Agora, sempre que o sistema for atualizado para uma versão nova, a janela de novidades aparece automaticamente na tela mesmo para quem já estava com o sistema aberto.',
                ],
            ],
            [
                'versao' => '1.3.2',
                'data' => '06/11/2026',
                'titulo' => 'Sincronização Automática de Versões na Atualização do Servidor',
                'tipo' => 'Melhoria de Estabilidade',
                'destaques' => [
                    'Ajuste no processo de atualização do servidor para substituir automaticamente etiquetas de versões antigas sem interromper a instalação.',
                ],
            ],
            [
                'versao' => '1.3.1',
                'data' => '06/11/2026',
                'titulo' => 'Textos de Ajuda e Novidades Mais Simples e Fáceis de Entender',
                'tipo' => 'Melhoria de Uso',
                'destaques' => [
                    'Todos os avisos de novidades e textos de ajuda foram reescritos em uma linguagem simples e direta para o dia a dia da unidade.',
                    'Explicações práticas de como usar a Agenda, Recepção, Prontuário, Faturamento e Configurações sem termos técnicos complicados.',
                ],
            ],
            [
                'versao' => '1.3.0',
                'data' => '06/11/2026',
                'titulo' => 'Novo Menu de Configurações, Janelas de Cadastro Rápido e Avisos na Tela',
                'tipo' => 'Nova Funcionalidade',
                'destaques' => [
                    'O menu de Configurações na lateral agora abre opções separadas para Pacientes, Salas, Equipe, Procedimentos, Histórico de Acessos e Meu Perfil.',
                    'Agora você pode cadastrar, pesquisar, editar, ativar/inativar e excluir registros usando janelas rápidas sem sair da tela.',
                    'Novos avisos coloridos no canto da tela confirmam na hora quando uma ação deu certo ou quando precisa da sua atenção.',
                    'Proteção automática para evitar excluir por engano uma sala ou profissional que já tenha atendimentos registrados.',
                ],
            ],
            [
                'versao' => '1.2.0',
                'data' => '06/11/2026',
                'titulo' => 'Área de Configurações e Cadastros da Unidade',
                'tipo' => 'Nova Funcionalidade',
                'destaques' => [
                    'Criação da área de Configurações para cadastrar e organizar as Salas de Atendimento e Ginásios da unidade.',
                    'Cadastro completo de Pacientes (com conferência automática do número do Cartão SUS) e da Equipe de Profissionais.',
                    'Opção para cada colaborador atualizar facilmente sua própria senha de entrada e seu PIN de assinatura clínica.',
                ],
            ],
            [
                'versao' => '1.1.4',
                'data' => '06/11/2026',
                'titulo' => 'Atualização Mais Rápida e Segura do Sistema no Servidor',
                'tipo' => 'Correção e Estabilidade',
                'destaques' => [
                    'Melhoria no processo de atualização automática do sistema para evitar travamentos quando uma nova versão é publicada.',
                    'Retorno automático do sistema ao ar caso ocorra qualquer interrupção durante a manutenção.',
                ],
            ],
            [
                'versao' => '1.1.3',
                'data' => '06/11/2026',
                'titulo' => 'Correção no Fechamento da Janela de Novidades ao Entrar',
                'tipo' => 'Correção Rápida',
                'destaques' => [
                    'Corrigido o problema em que a janela de novidades não fechava ao clicar no botão "Entendi, continuar".',
                    'Agora você também pode fechar os avisos apertando a tecla ESC do teclado ou clicando fora da janela.',
                ],
            ],
            [
                'versao' => '1.1.2',
                'data' => '06/11/2026',
                'titulo' => 'Ajustes Visuais na Central de Ajuda',
                'tipo' => 'Melhoria Interna',
                'destaques' => [
                    'Pequenos ajustes de organização nas telas de Ajuda e no aviso de novidades da versão.',
                ],
            ],
            [
                'versao' => '1.1.1',
                'data' => '06/11/2026',
                'titulo' => 'Identificação Oficial KL Tecnologia e Manual de Instalação',
                'tipo' => 'Melhoria Institucional',
                'destaques' => [
                    'Inclusão das informações oficiais de suporte e propriedade da KL Tecnologia (kltecnologia.com).',
                    'Organização do guia de instalação do servidor de produção da unidade.',
                ],
            ],
            [
                'versao' => '1.1.0',
                'data' => '06/11/2026',
                'titulo' => 'Central de Ajuda e Aviso de Novidades no Login',
                'tipo' => 'Nova Funcionalidade',
                'destaques' => [
                    'Sempre que você entra no sistema, uma janela mostra de forma resumida o que mudou na versão mais recente.',
                    'Nova página de "Ajuda & Sobre" no menu lateral com um guia rápido de como funciona cada setor do CER.',
                    'Linha do tempo mostrando todas as melhorias feitas no sistema desde o primeiro dia.',
                ],
            ],
            [
                'versao' => '1.0.1',
                'data' => '06/11/2026',
                'titulo' => 'Preparação do Sistema para Funcionar na Internet',
                'tipo' => 'Melhoria de Estrutura',
                'destaques' => [
                    'Configuração para que o sistema funcione com rapidez e segurança no endereço oficial da unidade na internet.',
                ],
            ],
            [
                'versao' => '1.0.0',
                'data' => '06/11/2026',
                'titulo' => 'Lançamento Inicial do Sistema CER Flow',
                'tipo' => 'Lançamento Principal',
                'destaques' => [
                    'Grade Semanal: agenda visual organizada de 30 em 30 minutos por sala e profissional, permitindo atendimento individual ou em conjunto.',
                    'Recepção e Check-in: confirmação de presença dos pacientes com 1 clique e aviso automático quando o paciente tem muitas faltas.',
                    'Prontuário Eletrônico: registro das evoluções do paciente com assinatura por senha curta (PIN) e proteção após 24 horas.',
                    'Faturamento SUS (BPA-i): conferência automática de dados pendentes e geração do arquivo mensal para envio ao SUS.',
                ],
            ],
        ];
    }
}

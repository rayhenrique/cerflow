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

        return $this->releaseAtual()['versao'];
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
     * Retorna a linha do tempo completa de evolução das versões do CER Digital
     * em linguagem simples e acessível para todos os colaboradores da unidade.
     *
     * @return array<int, array{versao: string, data: string, titulo: string, tipo: string, destaques: array<int, string>}>
     */
    public function historicoVersoes(): array
    {
        return [
            [
                'versao' => '1.12.0',
                'data' => '07/11/2026',
                'titulo' => 'Otimização e-SUS PEC, Keyset Pagination e Segundo Plano na VPS',
                'tipo' => 'Nova Versão',
                'destaques' => [
                    'Implementação de Keyset Pagination por chave estável (co_seq_acomp_cidadaos_vinc), substituindo OFFSET/LIMIT para alta escalabilidade.',
                    'Projeção seletiva de colunas necessárias, reduzindo tráfego de rede e consumo de memória em mais de 60%.',
                    'Detecção inteligente de schema com suporte a modo incremental, checkpoint transacional por lote e janela de segurança.',
                    'Blindagem contra concorrência e execuções duplicadas com ShouldBeUnique e bloqueio no Livewire.',
                    'Painel com métricas isoladas de consulta SQL, processamento e gravação, além de seletor de modo e tamanho de lote.',
                    'Guia e ajustes para execução em segundo plano via Queue Worker e Supervisor na VPS.',
                ],
            ],
            [
                'versao' => '1.11.1',
                'data' => '07/11/2026',
                'titulo' => 'Correção Crítica no Mapeamento e Persistência do e-SUS PEC',
                'tipo' => 'Correção Crítica',
                'destaques' => [
                    'Correção no mapeamento de colunas do DW PEC (tb_acomp_cidadaos_vinculados), incluindo dt_nascimento_cidadao, nu_cpf_cidadao, nu_cns_cidadao e co_fat_cidadao_pec.',
                    'Eliminação do descarte indevido que impedia a gravação dos pacientes consultados na base do e-SUS PEC.',
                    'Otimização de alta performance com processamento em lotes e pré-carregamento em memória, acelerando o processamento de grandes bases.',
                    'Acompanhamento do progresso da sincronização em tempo real com etapas reais, métricas parciais e barra de progresso.',
                    'Contabilização detalhada de registros válidos, inválidos, sem documento, novos, atualizados e sem alterações.',
                ],
            ],
            [
                'versao' => '1.11.0',
                'data' => '07/11/2026',
                'titulo' => 'Integração com e-SUS APS PEC & Sincronização Unidirecional de Pacientes',
                'tipo' => 'Nova Versão',
                'destaques' => [
                    'Novo módulo de Integração com o e-SUS APS PEC em Configurações, restrito à Coordenação e Administração.',
                    'Conexão PostgreSQL isolada e segura em modo somente leitura (read-only), sem risco de escrita na base do PEC.',
                    'Armazenamento seguro de credenciais com criptografia nativa AES-256 e sanitização contra vazamento em logs.',
                    'Diagnóstico dinâmico de schema e validação da tabela oficial de integração do DW PEC (tb_acomp_cidadaos_vinculados).',
                    'Sincronização com regras de desduplicação SUS por ID externo, CPF e CNS, preservando integralmente dados clínicos locais.',
                    'Agendamento automático diário às 02:00 e disparo manual sob demanda com histórico detalhado de execuções.',
                ],
            ],
            [
                'versao' => '1.10.0',
                'data' => '07/11/2026',
                'titulo' => 'Reformulação do Cadastro de Pacientes & Separação de Dados Clínicos',
                'tipo' => 'Nova Versão',
                'destaques' => [
                    'Novo cadastro de pacientes em 3 seções: Identificação do Cidadão, Vínculo com a Atenção Primária e Contatos/Endereço.',
                    'A recepção agora pode cadastrar pacientes sem exigir CID-10 ou modalidades de reabilitação.',
                    'Vínculo com a Atenção Primária com UBS de referência, Agente Comunitário de Saúde (ACS) e código IBGE padrão.',
                    'Preparação do modelo de dados para integração com o e-SUS APS PEC e regras de desduplicação.',
                    'Gestão de diagnóstico clínico e modalidades transferida para o Prontuário Eletrônico do Paciente (PEP Multi).',
                ],
            ],
            [
                'versao' => '1.9.0',
                'data' => '07/11/2026',
                'titulo' => 'Novo Módulo — Mapa Mensal da Agenda e Gestão Panorâmica',
                'tipo' => 'Nova Versão',
                'destaques' => [
                    'Novo Mapa Mensal da Agenda para visualização gerencial completa da escala, distribuição de atendimentos e ocupação do CER.',
                    'Cards de indicadores com volume de agendamentos, presenças confirmadas, taxa de comparecimento, absenteísmo e média diária por dia ativo.',
                    'Calendário dinâmico com navegação mensal, filtros por especialidade, profissional e turno, e botão para abrir qualquer dia diretamente na grade operacional.',
                    'Preservação e conformidade total com a LGPD na visualização executiva.',
                ],
            ],
            [
                'versao' => '1.8.0',
                'data' => '07/11/2026',
                'titulo' => 'Identificação do Estabelecimento, Logo Oficial e Estabilização do Layout',
                'tipo' => 'Nova Versão',
                'destaques' => [
                    'Nova seção de Identificação do Estabelecimento em Configurações (exclusiva para Coordenação), permitindo cadastrar o CNES oficial e o código IBGE padrão da unidade.',
                    'Envio, visualização e remoção da Logomarca Oficial do estabelecimento, exibida dinamicamente na tela de login e nos menus do sistema.',
                    'Utilização automática do CNES configurado no cabeçalho do sistema e nas linhas da exportação oficial BPA-i (SIA/SUS).',
                    'Ajustes de estabilidade no carregamento do sistema e rotas institucionais protegidas.',
                ],
            ],
            [
                'versao' => '1.7.0',
                'data' => '07/11/2026',
                'titulo' => 'Identidade CER Digital, Tradução Completa e Janela de Novidades Individual',
                'tipo' => 'Nova Versão',
                'destaques' => [
                    'O sistema agora se chama oficialmente CER Digital, com padronização completa em português brasileiro (Painel Inicial) e novas mensagens do sistema.',
                    'A janela com as novidades de cada versão agora é salva diretamente no seu usuário: você vê os avisos uma única vez e ela não reaparece mais após você fechar, mesmo trocando de computador ou celular.',
                    'Você sempre pode rever as novidades a qualquer momento clicando no botão de versão no topo da tela ou na Central de Ajuda.',
                ],
            ],
            [
                'versao' => '1.6.2',
                'data' => '07/11/2026',
                'titulo' => 'Correção no Carregamento do Painel Inicial',
                'tipo' => 'Correção Rápida',
                'destaques' => [
                    'Corrigida a consulta dos números de produção SUS (BPA-i) no Painel Inicial para abrir sem erro no servidor de produção.',
                ],
            ],
            [
                'versao' => '1.6.1',
                'data' => '07/11/2026',
                'titulo' => 'Otimização dos Cálculos do Painel Inicial',
                'tipo' => 'Melhoria de Desempenho',
                'destaques' => [
                    'Organização e otimização interna das consultas de estatísticas do Painel Inicial para carregamento ainda mais rápido.',
                ],
            ],
            [
                'versao' => '1.6.0',
                'data' => '07/11/2026',
                'titulo' => 'Menu Lateral Retrátil (Recolher/Expandir) e Novos Ícones Visuais em Todo o Menu',
                'tipo' => 'Nova Funcionalidade',
                'destaques' => [
                    'Novo botão para recolher ou expandir a barra lateral no computador, ganhando mais espaço na tela para a Grade Semanal e o Prontuário.',
                    'Todos os itens e subitens do menu agora contam com ícones visuais próprios que facilitam encontrar cada setor rapidamente.',
                    'Quando o menu está recolhido, basta passar o mouse sobre qualquer ícone para ver o nome do módulo.',
                ],
            ],
            [
                'versao' => '1.5.0',
                'data' => '07/11/2026',
                'titulo' => 'Novo Painel Inicial com Estatísticas do CER em Tempo Real',
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
                'titulo' => 'Lançamento Inicial do Sistema CER Digital',
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

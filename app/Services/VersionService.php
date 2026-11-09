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
                'versao' => '1.18.0',
                'data' => '08/11/2026',
                'titulo' => 'Pareamento Seguro de Smart TVs por Código de Ativação',
                'tipo' => 'Novidade / Integração TV',
                'destaques' => [
                    'Substituição de senhas manuais e URLs compartilhadas por ativação individual de Smart TVs via código de pareamento de 6 a 7 dígitos (RFC 8628).',
                    'Entrega segura da credencial criptográfica por Cookie persistente HttpOnly, Secure, Lax com isolamento estrito de rotas e conformidade LGPD.',
                    'Gestão centralizada pela Coordenação em Configurações para autorizar, renomear, consultar últimos acessos e revogar televisores em tempo real.',
                    'Proteção contra adivinhação de códigos por força bruta (rate limiting), expiração de 10 minutos e descarte após uso único.',
                    'Compatibilidade transitória com opção de manter painéis legados via URL enquanto a transição de dispositivos é concluída.',
                ],
            ],
            [
                'versao' => '1.17.1',
                'data' => '08/11/2026',
                'titulo' => 'Otimização de Alta Performance e Consultas no Banco de Dados',
                'tipo' => 'Desempenho e Infraestrutura',
                'destaques' => [
                    'Eliminação de varreduras completas (full table scan) na base de pacientes com suporte otimizado a mais de 40 mil registros.',
                    'Busca ultrarrápida de pacientes com projeção seletiva de colunas, índices dedicados para CPF e CNS e pesquisa de nomes em duas etapas (prefixo + FULLTEXT).',
                    'Isolamento estrito de consultas no módulo de Configurações, executando apenas as buscas da aba ativa.',
                    'Armazenamento em cache de alta velocidade para lista de UBS e indicadores analíticos do Painel Inicial Operacional.',
                    'Novos índices de banco de dados para UBS, busca textual e filtragem de registros ativos.',
                ],
            ],
            [
                'versao' => '1.17.0',
                'data' => '08/11/2026',
                'titulo' => 'Painel de Chamadas para TV Integrado à Agenda e Recepção',
                'tipo' => 'Nova Funcionalidade',
                'destaques' => [
                    'Módulo dedicado para Smart TV em /painel-chamadas com proporção 16:9, relógio digital e layout de alto contraste.',
                    'Aviso sonoro em dois tons (Web Audio API) e sintetização de voz nativa pt-BR (Web Speech API) sem custos externos.',
                    'Chamada e repetição de chamadas a partir da Recepção e do Prontuário Multiprofissional (PEP), restrito a agendamentos PRESENTE.',
                    'Proteção de dados e conformidade com a LGPD: modos de exibição de nome (Completo, Primeiro e Último, Abreviado ou Primeiro Nome) e acesso seguro por token revogável.',
                    'Parâmetros ajustáveis em Configurações > Painel de Chamadas para controle de volume, voz, tempo de destaque e histórico.',
                ],
            ],
            [
                'versao' => '1.16.1',
                'data' => '07/11/2026',
                'titulo' => 'Correção de Deserialização de Cache no Painel Inicial (Dashboard)',
                'tipo' => 'Correção Crítica',
                'destaques' => [
                    'Correção na serialização de dados de ocupação de salas no cache do Dashboard, convertendo modelos Eloquent em estruturas puras (arrays/escalares).',
                    'Tratamento preventivo na View Blade para suportar propriedades de salas tanto em formato de objeto quanto array.',
                    'Limpeza e renovação automática de chaves de cache para evitar erro 500 no retorno ao módulo após navegação.',
                ],
            ],
            [
                'versao' => '1.16.0',
                'data' => '07/11/2026',
                'titulo' => 'Manual Completo e Didático do Usuário para Todos os Colaboradores',
                'tipo' => 'Documentação e Usabilidade',
                'destaques' => [
                    'Publicação do Manual Oficial do Usuário do CER Digital abrangendo todas as áreas (Recepção, Terapeutas/PEP, Faturamento SUS e Coordenação).',
                    'Central de Ajuda interativa com navegação rápida por setor, checklists operacionais diários, perguntas frequentes e glossário SUS.',
                    'Instruções detalhadas passo a passo para busca inteligente sob demanda, assinatura eletrônica com PIN, regra de 24h, termos de adendo e exportação BPA-i.',
                    'Disponibilização do manual para leitura online e download em arquivo estruturado diretamente pelo sistema.',
                ],
            ],
            [
                'versao' => '1.15.3',
                'data' => '07/11/2026',
                'titulo' => 'Otimização de Alta Performance no Painel Inicial Operacional (Dashboard)',
                'tipo' => 'Performance e Otimização',
                'destaques' => [
                    'Eliminação da hidratação em massa de dezenas de milhares de modelos de pacientes no cálculo de modalidades de reabilitação.',
                    'Agregação direta em SQL para métricas de modalidades, agendamentos mensais e produção BPA-i.',
                    'Carregamento seletivo de colunas (eager loading) em agendamentos do dia (paciente, sala e profissionais).',
                    'Camada inteligente de cache de curta duração (60s) para contagens e métricas gerenciais do painel.',
                ],
            ],
            [
                'versao' => '1.15.2',
                'data' => '07/11/2026',
                'titulo' => 'Busca Dinâmica de Pacientes no Prontuário Multiprofissional (PEP)',
                'tipo' => 'Performance e Usabilidade',
                'destaques' => [
                    'Eliminação do carregamento forçado do primeiro paciente e de todos os pacientes ativos ao abrir o Prontuário Eletrônico (/prontuario).',
                    'Pesquisa em tempo real com debounce de 400ms e ativação a partir de 3 caracteres por nome, CPF ou CNS.',
                    'Dropdown inteligente de resultados limitados a 20 registros com identificação clara (Nome, CPF, CNS e Nascimento).',
                    'Estado inicial com orientações claras e card de paciente selecionado com opção rápida de troca.',
                ],
            ],
            [
                'versao' => '1.15.1',
                'data' => '07/11/2026',
                'titulo' => 'Paginação na Listagem de Pacientes do CER Digital',
                'tipo' => 'Performance e Usabilidade',
                'destaques' => [
                    'Paginação server-side reativa na tela de Configurações e Cadastros Base (/configuracoes/pacientes).',
                    'Seletor configurável de quantidade de itens por página (15, 25, 50 ou 100 registros).',
                    'Contador dinâmico de registros totais e filtrados com feedback de busca em tempo real.',
                    'Navegação entre páginas integrada ao Livewire com reinicialização automática ao aplicar filtros de pesquisa.',
                ],
            ],
            [
                'versao' => '1.15.0',
                'data' => '07/11/2026',
                'titulo' => 'Busca Sob Demanda de Pacientes no Novo Agendamento',
                'tipo' => 'Performance e Usabilidade',
                'destaques' => [
                    'Abertura instantânea do modal de agendamento na grade semanal, eliminando a carga antecipada de todos os pacientes ativos.',
                    'Pesquisa em tempo real com debounce de 400ms e ativação a partir de 3 caracteres por nome, CPF ou CNS.',
                    'Normalização automática de pontuação de documentos para localização ágil.',
                    'Exibição de resultados em lista compacta com limite de 20 registros e dados essenciais de identificação (Nome, CPF, CNS e Nascimento).',
                    'Card visual de confirmação do paciente selecionado com ação rápida de troca ou limpeza.',
                ],
            ],
            [
                'versao' => '1.14.0',
                'data' => '07/11/2026',
                'titulo' => 'Complementação de Vínculos de UBS e ACS do e-SUS PEC',
                'tipo' => 'Nova Versão',
                'destaques' => [
                    'Resolução relacional da UBS de referência vinculada via dimensões do PEC sem confundir equipes com unidades de saúde.',
                    'Resolução individual e por equipe do Agente Comunitário de Saúde (ACS) validado estritamente pelos CBOs 515105 e 322255.',
                    'Novo modo --apenas-vinculos no comando esus:sincronizar para enriquecimento de pacientes sem criar registros ou alterar dados pessoais.',
                    'Novo comando php artisan esus:diagnostico para validação prévia de conexões, contagens e schemas do PostgreSQL do e-SUS.',
                ],
            ],
            [
                'versao' => '1.13.1',
                'data' => '07/11/2026',
                'titulo' => 'Desbloqueio e Recuperação Automática de Sincronizações Órfãs do e-SUS',
                'tipo' => 'Correção e Melhoria',
                'destaques' => [
                    'Recuperação automática de sincronizações anteriores estagnadas há mais de 2 horas, marcando-as como INTERROMPIDO e liberando a execução.',
                    'Nova opção --limpar-bloqueio no comando php artisan esus:sincronizar para liberação pontual de locks e encerramento de processos travados.',
                    'Suporte à flag --force para forçar a execução imediata sobrescrevendo bloqueios de concorrência anteriores.',
                ],
            ],
            [
                'versao' => '1.13.0',
                'data' => '07/11/2026',
                'titulo' => 'Comando Artisan CLI para Sincronização do e-SUS APS PEC',
                'tipo' => 'Nova Versão',
                'destaques' => [
                    'Novo comando de console php artisan esus:sincronizar para execução direta pelo terminal da VPS, sem depender de navegador ou worker.',
                    'Processamento em lotes configuráveis (--lote=2000) e limite opcional (--max-registros) para homologação por etapas.',
                    'Modo simulação (--dry-run) que executa validação, matching e desduplicação completa sem persistir dados no banco local.',
                    'Controle e retomada automática por checkpoint a partir do último identificador processado, com opção de reinício (--reiniciar).',
                    'Bloqueio atômico de concorrência com Cache Lock e validação de status ativo contra execuções simultâneas.',
                    'Saída rica no terminal em tempo real com métricas lote a lote e tabela consolidada de resultados do Symfony Console.',
                ],
            ],
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

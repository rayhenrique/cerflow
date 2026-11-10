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
                'versao' => '1.23.1',
                'data' => '10/11/2026',
                'titulo' => 'Recuperação de CBOs Legados e Importação do Catálogo Oficial MTE',
                'tipo' => 'Novidades e Integridade',
                'destaques' => [
                    'Novidades: Adicionamos o comando seguro cer:recuperar-cbos-legados para resgatar os CBOs e Especialidades dos 39 profissionais já cadastrados sem perder dados históricos.',
                    'Novidades: Adicionamos o comando oficial cer:importar-cbos com o catálogo completo CBO 2002 do Ministério do Trabalho e Emprego (2.614 ocupações válidas).',
                    'Melhorias: O seletor de CBO nos cadastros de profissionais e especialidades agora conta com busca rápida em tempo real, evitando lentidão ao navegar no catálogo.',
                    'Segurança: Garantimos a vinculação formal da Neuropsicologia ao CBO 251545 e a preservação total de dados de faturamento e produção BPA-i do SUS.',
                ],
            ],
            [
                'versao' => '1.23.0',
                'data' => '10/11/2026',
                'titulo' => 'Reorganização do Menu Lateral por Áreas Funcionais',
                'tipo' => 'Melhorias de Usabilidade',
                'destaques' => [
                    'Melhorias: Reorganizamos o menu lateral em áreas funcionais intuitivas (Início, Atendimento, Gestão, Cadastros, Administração e Conta & Suporte), reduzindo a poluição visual.',
                    'Melhorias: Os grupos de Cadastros e Administração iniciam recolhidos para economizar espaço e abrem automaticamente ao navegar para qualquer tela dessas categorias.',
                    'Melhorias: Aprimoramos a experiência em celulares e tablets com gaveta lateral que bloqueia a rolagem indevida do fundo e fecha automaticamente após escolher uma rotina.',
                    'Melhorias: No computador, o menu compacto exibe identificadores e descrições acessíveis para cada item, mantendo o destaque verde-água da rotina ativa.',
                    'Segurança: Cada colaborador visualiza apenas as opções autorizadas para seu perfil, preservando todas as regras e bloqueios de acesso do sistema.',
                ],
            ],
            [
                'versao' => '1.22.0',
                'data' => '10/11/2026',
                'titulo' => 'Cadastros Dinâmicos de CBOs, Especialidades e Unidades Básicas de Saúde (UBS)',
                'tipo' => 'Novidades e Gestão',
                'destaques' => [
                    'Novidades: Adicionamos o gerenciamento dinâmico de CBOs oficiais (6 dígitos) com verificação de conformidade MTE/SUS e prevenção de duplicações.',
                    'Novidades: Implementamos o catálogo dinâmico de Especialidades com vinculação N:N com ocupações CBO e deduplicação inteligente.',
                    'Novidades: Criamos o cadastro da Rede de Atenção Primária / UBS com código CNES (7 dígitos), código IBGE e sincronização bidirecional com o e-SUS PEC.',
                    'Novidades: Cadastramos o CBO oficial 251545 (Neuropsicólogo) e a especialidade Neuropsicologia, respeitando rigorosamente as regras de faturamento ambulatorial BPA-i.',
                    'Segurança: Bloqueio automático de exclusão para CBOs, Especialidades e UBSs que possuam vínculos ativos com profissionais ou pacientes, garantindo a integridade dos prontuários.',
                ],
            ],
            [
                'versao' => '1.21.2',
                'data' => '10/11/2026',
                'titulo' => 'Correção de Segurança e Privacidade no Campo de Senha do Login',
                'tipo' => 'Correções e Segurança',
                'destaques' => [
                    'Correções: Garantimos que o campo de senha na tela de entrada do sistema permaneça sempre protegido e oculto por padrão em qualquer computador, celular ou navegador.',
                    'Melhorias: O botão para visualizar a senha agora funciona de forma mais rápida, segura e permite navegação pelo teclado para maior acessibilidade.',
                    'Melhorias: Aprimoramos o aviso de carregamento ao entrar no sistema, evitando cliques repetidos durante a conferência dos dados de acesso.',
                ],
            ],
            [
                'versao' => '1.21.1',
                'data' => '10/11/2026',
                'titulo' => 'Atualização Integral do Manual do Usuário e Central de Ajuda',
                'tipo' => 'Documentação e Ajuda',
                'destaques' => [
                    'Melhorias: Atualizamos integralmente o Manual do Usuário para explicar em detalhes o novo painel de relatórios, o sistema de chamadas na TV e os avisos do Cartão SUS.',
                    'Melhorias: Agora você pode navegar pelos capítulos do manual diretamente na Central de Ajuda ou baixar o arquivo atualizado com um único clique.',
                    'Melhorias: Incluímos orientações passo a passo para recepção, terapeutas, faturamento e coordenação, com rotinas diárias e respostas para as dúvidas mais comuns.',
                ],
            ],
            [
                'versao' => '1.21.0',
                'data' => '10/11/2026',
                'titulo' => 'Novo Módulo de Relatórios e Indicadores Gerenciais',
                'tipo' => 'Novidades e Gestão',
                'destaques' => [
                    'Novidades: Criamos um painel completo de relatórios e indicadores para acompanhar o dia a dia da unidade, a frequência dos pacientes, o absenteísmo e a produção assistencial.',
                    'Novidades: Adicionamos a identificação de faltas recorrentes, permitindo que a recepção e a coordenação localizem rapidamente pacientes que precisam de apoio ou busca ativa.',
                    'Novidades: Agora você pode exportar qualquer relatório para planilha ou imprimir em formato limpo e organizado para reuniões e prestação de contas.',
                    'Melhorias: O sistema agora diferencia com total precisão quem já realizou o check-in na recepção de quem já concluiu o atendimento com o terapeuta.',
                    'Melhorias: Cancelamentos e horários futuros não entram mais no cálculo de faltas, garantindo indicadores justos e confiáveis para toda a equipe.',
                ],
            ],
            [
                'versao' => '1.20.1',
                'data' => '10/11/2026',
                'titulo' => 'Estabilidade e Confiabilidade na Exibição do Logotipo Institucional',
                'tipo' => 'Correções e Estabilidade',
                'destaques' => [
                    'Correções: Garantimos a exibição estável e contínua do logotipo oficial da instituição na tela de entrada, nos menus do sistema, no painel de TV e nas configurações.',
                    'Melhorias: Aprimoramos a confiabilidade interna do carregamento de imagens institucionais em todo o sistema.',
                ],
            ],
            [
                'versao' => '1.20.0',
                'data' => '10/11/2026',
                'titulo' => 'Nova Página Institucional, Login Elegante e Visual Adaptado para Celulares',
                'tipo' => 'Novidades e Visual',
                'destaques' => [
                    'Novidades: Criamos uma nova página inicial pública e institucional do CER Digital, apresentando com clareza o propósito do sistema, as linhas de reabilitação e o fluxo de cuidado do SUS.',
                    'Novidades: A tela de entrada no sistema foi totalmente redesenhada, oferecendo um visual mais moderno, opção de visualizar ou ocultar a senha digitada e navegação fácil de retorno à página inicial.',
                    'Melhorias: Todos os módulos do sistema (Painel Inicial, Agenda, Prontuário, Faturamento e Cadastros) agora se adaptam de forma perfeita a telas de celulares e tablets, com botões confortáveis para o toque.',
                    'Melhorias: Listas e tabelas foram adaptadas para o celular em formato de cartões fáceis de ler, permitindo consultar e agir sem a necessidade de arrastar a tela para os lados.',
                    'Melhorias: Menus e janelas de confirmação ganharam rolagem vertical automática e ajuste ao teclado do celular, facilitando o uso por recepcionistas e terapeutas em movimento na unidade.',
                ],
            ],
            [
                'versao' => '1.19.4',
                'data' => '09/11/2026',
                'titulo' => 'Avisos Claros sobre o Cartão SUS no Agendamento',
                'tipo' => 'Melhorias e Clareza',
                'destaques' => [
                    'Melhorias: Ao selecionar um paciente sem o número do Cartão SUS (CNS) ou com número inválido, o sistema agora exibe um aviso claro explicando exatamente qual informação precisa ser preenchida.',
                    'Melhorias: Usuários autorizados contam com um atalho para abrir e atualizar o cadastro do paciente, além de um botão para revalidar os dados na hora sem perder as informações já preenchidas no agendamento.',
                    'Melhorias: Caso falte a data de nascimento do paciente, o sistema indica com clareza a pendência antes da confirmação do horário.',
                    'Correções: O formulário de agendamento não perde mais os dados preenchidos quando uma pendência cadastral precisa ser corrigida.',
                ],
            ],
            [
                'versao' => '1.19.3',
                'data' => '09/11/2026',
                'titulo' => 'Consolidação da Agenda Operacional e Precisão nos Agendamentos',
                'tipo' => 'Correções e Praticidade',
                'destaques' => [
                    'Correções: Cada paciente agendado agora aparece estritamente no dia correto da semana. Agendamentos de quarta-feira não aparecem mais na segunda-feira.',
                    'Melhorias: A navegação da agenda foi unificada em um único local no menu, permitindo alternar entre o dia, a semana e o mês com apenas um clique.',
                    'Melhorias: O Mapa Mensal agora conta com painéis informativos de atendimentos realizados, faltas e dias com agenda ativa, além de filtros por especialidade, profissional e turno.',
                    'Melhorias: Ao cadastrar um novo horário na visão de semana completa, você pode escolher a sala desejada com total liberdade, sem seleções automáticas.',
                    'Melhorias: Navegação mais rápida e ágil ao alternar entre os dias e meses de atendimento.',
                ],
            ],
            [
                'versao' => '1.19.2',
                'data' => '09/11/2026',
                'titulo' => 'Navegação por Dias na Grade Semanal e Visualização Completa',
                'tipo' => 'Correções e Melhorias',
                'destaques' => [
                    'Correções: Corrigimos a navegação da agenda semanal. Agora você pode alternar entre Segunda, Terça, Quarta, Quinta e Sexta e visualizar os agendamentos na data correta.',
                    'Melhorias: O cabeçalho da grade agora exibe com clareza o dia selecionado e o período completo da semana.',
                    'Melhorias: Os botões dos dias da semana mostram a data correspondente para facilitar a conferência rápida.',
                    'Melhorias: Ao avançar ou voltar semanas, o sistema mantém o dia da semana em que você estava.',
                    'Novidades: Nova opção para alternar a grade entre a visualização por salas e a visualização da semana completa.',
                ],
            ],
            [
                'versao' => '1.19.1',
                'data' => '09/11/2026',
                'titulo' => 'Mais Estabilidade no Painel de Chamadas da Smart TV',
                'tipo' => 'Correções e Confiabilidade',
                'destaques' => [
                    'Correções: O Painel de Chamadas da Smart TV agora abre e atualiza a lista de pacientes de forma contínua e sem travamentos.',
                    'Melhorias: Maior segurança para garantir que apenas aparelhos de TV autorizados pela Coordenação tenham acesso aos nomes dos pacientes.',
                    'Melhorias: Carregamento mais rápido das informações de chamadas da recepção.',
                ],
            ],
            [
                'versao' => '1.19.0',
                'data' => '09/11/2026',
                'titulo' => 'Agenda Operacional Unificada e Lista do Dia no Prontuário',
                'tipo' => 'Novidades e Praticidade',
                'destaques' => [
                    'Novidades: Nova Agenda Operacional que reúne o fluxo do dia, a grade semanal e o mapa mensal em um único lugar.',
                    'Novidades: Os profissionais agora podem visualizar os pacientes que chegaram à recepção e abrir seus prontuários com mais facilidade.',
                    'Melhorias: Filtros práticos por turno (Manhã, Tarde e Noite) com contagem de pacientes em tempo real.',
                    'Melhorias: Marcação automática do horário exato de chegada do paciente quando a recepção confirma sua presença.',
                    'Melhorias: No atendimento realizado por mais de um terapeuta, o atendimento é concluído automaticamente após todos assinarem suas evoluções.',
                    'Melhorias: Melhoramos a velocidade de carregamento da agenda e da lista de pacientes.',
                ],
            ],
            [
                'versao' => '1.18.1',
                'data' => '09/11/2026',
                'titulo' => 'Proteção e Estabilidade no Painel de Chamadas',
                'tipo' => 'Segurança e Melhorias',
                'destaques' => [
                    'Melhorias: Proteção reforçada para garantir que informações de pacientes nunca sejam exibidas em telas não autorizadas.',
                    'Melhorias: Quando a Coordenação desativa um aparelho de televisão, o acesso é cancelado de forma imediata.',
                    'Melhorias: Chamadas de pacientes mais ágeis e estáveis mesmo com vários televisores conectados na sala de espera.',
                ],
            ],
            [
                'versao' => '1.18.0',
                'data' => '08/11/2026',
                'titulo' => 'Conexão Fácil de Smart TVs por Código na Tela',
                'tipo' => 'Novidades para a Sala de Espera',
                'destaques' => [
                    'Novidades: Agora você conecta uma Smart TV digitando apenas o código rápido exibido na tela da TV, sem precisar digitar senhas no controle remoto.',
                    'Novidades: A Coordenação pode autorizar, dar nomes para cada televisão e acompanhar quais estão conectadas na área de Configurações.',
                    'Melhorias: Os códigos de conexão possuem tempo de validade para garantir total segurança na ativação.',
                    'Melhorias: Televisores já configurados continuam funcionando normalmente durante a transição.',
                ],
            ],
            [
                'versao' => '1.17.1',
                'data' => '08/11/2026',
                'titulo' => 'Busca Rápida de Pacientes e Telas Mais Ágeis',
                'tipo' => 'Melhorias de Rapidez',
                'destaques' => [
                    'Melhorias: Localização instantânea de pacientes por nome, CPF ou Cartão SUS na recepção e no prontuário.',
                    'Melhorias: As telas de Configurações agora abrem com muito mais rapidez.',
                    'Melhorias: Maior agilidade ao abrir o Painel Inicial e consultar a lista de unidades de saúde.',
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

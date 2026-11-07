# Manual do Usuário — CER Digital (CER Flow)
**Sistema Integrado de Gestão Especializada em Reabilitação & Faturamento SIA/SUS**

> **Versão do Sistema:** v1.16.0  
> **Público-alvo:** Recepção, Terapeutas e Equipe Clínica Multiprofissional, Faturamento SUS, Coordenação Geral e Tecnologia da Informação  
> **Homologação:** Ministério da Saúde / Datasus (BPA-i / SIA-SUS) e e-SUS APS PEC  
> **Desenvolvido por:** KL Tecnologia

---

## Sumário

1. [Apresentação e Visão Geral do Sistema](#1-apresentação-e-visão-geral-do-sistema)
2. [Primeiros Passos, Login e Segurança](#2-primeiros-passos-login-e-segurança)
   - [2.1 Acessando o Sistema](#21-acessando-o-sistema)
   - [2.2 Primeiro Acesso e Troca de Senha](#22-primeiro-acesso-e-troca-de-senha)
   - [2.3 O PIN Eletrônico de Assinatura](#23-o-pin-eletrônico-de-assinatura)
   - [2.4 Perfis de Acesso e Matriz de Permissões](#24-perfis-de-acesso-e-matriz-de-permissões)
3. [Módulo Recepção & Gestão de Agendamentos](#3-módulo-recepção--gestão-de-agendamentos)
   - [3.1 A Grade Semanal por Salas e Terapeutas](#31-a-grade-semanal-por-salas-e-terapeutas)
   - [3.2 Novo Agendamento com Busca Inteligente](#32-novo-agendamento-com-busca-inteligente)
   - [3.3 Atendimento Compartilhado (Co-atendimento)](#33-atendimento-compartilhado-co-atendimento)
   - [3.4 Horários Cativos / Recorrentes](#34-horários-cativos--recorrentes)
   - [3.5 Check-in e Acolhimento Diário](#35-check-in-e-acolhimento-diário)
   - [3.6 Alerta de Faltas e Controle de Absenteísmo](#36-alerta-de-faltas-e-controle-de-absenteísmo)
   - [3.7 Mapa Mensal de Ocupação](#37-mapa-mensal-de-ocupação)
4. [Módulo Prontuário Eletrônico do Paciente (PEP Multiprofissional)](#4-módulo-prontuário-eletrônico-do-paciente-pep-multiprofissional)
   - [4.1 Abertura e Busca Sob Demanda no PEP](#41-abertura-e-busca-sob-demanda-no-pep)
   - [4.2 Ficha Cadastral e Diagnóstico do Paciente](#42-ficha-cadastral-e-diagnóstico-do-paciente)
   - [4.3 Registro de Evolução Clínica Estruturada](#43-registro-de-evolução-clínica-estruturada)
   - [4.4 Assinatura Eletrônica com PIN e Hash SHA-256](#44-assinatura-eletrônica-com-pin-e-hash-sha-256)
   - [4.5 A Regra de 24 Horas e a Imutabilidade Clínica](#45-a-regra-de-24-horas-e-a-imutabilidade-clínica)
   - [4.6 Termos de Adendo Retificadores](#46-termos-de-adendo-retificadores)
   - [4.7 Linha do Tempo e Leitura Interdisciplinar](#47-linha-do-tempo-e-leitura-interdisciplinar)
5. [Módulo Faturamento SUS & BPA-i (SIA/SUS)](#5-módulo-faturamento-sus--bpa-i-siasus)
   - [5.1 O que é o BPA-i e o Fluxo de Faturamento](#51-o-que-é-o-bpa-i-e-o-fluxo-de-faturamento)
   - [5.2 Regras Automáticas da Tabela SIGTAP](#52-regras-automáticas-da-tabela-sigtap)
   - [5.3 Painel de Auditoria Prévia de Produção](#53-painel-de-auditoria-prévia-de-produção)
   - [5.4 Exportação do Arquivo Magnético Datasus (.txt)](#54-exportação-do-arquivo-magnético-datasus-txt)
   - [5.5 Importação no Software Oficial BPA Magnético](#55-importação-no-software-oficial-bpa-magnético)
6. [Módulo Gestão, Cadastros e Configurações](#6-módulo-gestão-cadastros-e-configurações)
   - [6.1 Painel Inicial Operacional (Dashboard)](#61-painel-inicial-operacional-dashboard)
   - [6.2 Cadastro e Manutenção de Pacientes (com Paginação)](#62-cadastro-e-manutenção-de-pacientes-com-paginação)
   - [6.3 Salas e Espaços Terapêuticos](#63-salas-e-espaços-terapêuticos)
   - [6.4 Profissionais, Conselhos e CBOs SUS](#64-profissionais-conselhos-e-cbos-sus)
   - [6.5 Integração e Sincronização com o e-SUS APS PEC](#65-integração-e-sincronização-com-o-e-sus-aps-pec)
   - [6.6 Trilha de Auditoria e Segurança (LGPD)](#66-trilha-de-auditoria-e-segurança-lgpd)
7. [Checklists Diários por Perfil de Usuário](#7-checklists-diários-por-perfil-de-usuário)
   - [7.1 Checklist da Recepção](#71-checklist-da-recepção)
   - [7.2 Checklist do Terapeuta / Especialista](#72-checklist-do-terapeuta--especialista)
   - [7.3 Checklist do Faturamento SUS](#73-checklist-do-faturamento-sus)
   - [7.4 Checklist da Coordenação e Gestão](#74-checklist-da-coordenação-e-gestão)
8. [Perguntas Frequentes (FAQ) & Resolução de Problemas](#8-perguntas-frequentes-faq--resolução-de-problemas)
9. [Glossário de Termos do SUS e do Sistema](#9-glossário-de-termos-do-sus-e-do-sistema)

---

## 1. Apresentação e Visão Geral do Sistema

O **CER Digital** (CER Flow) é uma plataforma integrada desenvolvida sob medida para a rotina de Centros Especializados em Reabilitação (CER II, CER III e CER IV).

O sistema foi concebido para resolver quatro desafios críticos das unidades de reabilitação:
1. **Substituição de ferramentas paralelas:** Elimina planilhas de agendamento em Excel, fichas em papel e formulários avulsos do Google Forms.
2. **Segurança Jurídica e Clínica:** Prontuário Eletrônico Multiprofissional (PEP) com assinatura via PIN individual, registro de hash SHA-256 e trava legal de imutabilidade após 24 horas, conforme resoluções dos conselhos de classe (CFM, COFFITO, CFP, CFFa, CRESS) e Lei Geral de Proteção de Dados (LGPD).
3. **Agilidade no Atendimento:** Interfaces rápidas e responsivas, busca de pacientes em milissegundos sem travamento do navegador, mesmo em bases com mais de 38.000 cadastros.
4. **Faturamento SUS Automatizado (BPA-i):** Conversão automática dos atendimentos clínicos validados em arquivo magnético padrão Datasus (SIA/SUS), com pré-auditoria das regras SIGTAP (CBO, faixa etária, sexo e CID-10), evitando glosas e rejeições de faturamento.

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        CICLO OPERACIONAL DO CER DIGITAL                 │
└─────────────────────────────────────────────────────────────────────────┘
        │
        ▼
 1. RECEPÇÃO & AGENDAMENTO
    • Agendamento individual ou compartilhado (Grade Semanal de 30 min)
    • Busca dinâmica do paciente (Nome, CPF ou Cartão SUS)
    • Check-in diário de presença / falta
        │
        ▼
 2. ATENDIMENTO CLÍNICO (PEP)
    • Leitura do histórico multiprofissional integrado
    • Registro da evolução clínica estruturada
    • Assinatura eletrônica com PIN individual (Hash SHA-256)
    • Trava legal após 24 horas (Retificações apenas via Termo de Adendo)
        │
        ▼
 3. PRODUÇÃO & FATURAMENTO SUS
    • Validação automática SIGTAP (CBO x Procedimento x CID-10 x Idade)
    • Conferência prévia do lote sem inconsistências
    • Exportação magnética oficial BPA-i (.txt Datasus)
        │
        ▼
 4. GESTÃO E INTEGRAÇÃO
    • Painel de indicadores de produção, modalidades e absenteísmo
    • Sincronização direta com banco de dados do e-SUS APS PEC
```

---

## 2. Primeiros Passos, Login e Segurança

### 2.1 Acessando o Sistema
- Abra seu navegador web de preferência (**Google Chrome**, **Microsoft Edge**, **Mozilla Firefox** ou **Safari**).
- Digite o endereço web oficial fornecido pela coordenação da sua unidade (exemplo: `https://cer.sua-unidade.gov.br`).
- O sistema opera em conexão criptografada segura HTTPS (`cadeado verde`).

### 2.2 Primeiro Acesso e Troca de Senha
1. Na tela de login, insira seu **E-mail institucional** e a **Senha provisória** fornecida pela coordenação.
2. Clique em **Entrar no Sistema**.
3. No primeiro login, o sistema pode apresentar o modal com as novidades da versão mais recente. Leia os destaques e clique em **Entendi e Quero Continuar**.
4. Para alterar sua senha a qualquer momento:
   - Clique em **Configurações** no menu lateral e selecione **Minha Conta & PIN**.
   - No painel "Trocar Minha Senha de Acesso", informe sua senha atual, digite a nova senha desejada (mínimo de 8 caracteres) e confirme.
   - Clique em **Salvar Nova Senha**.

### 2.3 O PIN Eletrônico de Assinatura
> 🔒 **O que é o PIN?**  
> O PIN é uma senha numérica secreta (de 4 a 6 dígitos) de uso pessoal e intransferível. Ele é exigido **exclusivamente no momento em que você assina uma evolução clínica ou um termo de adendo** no Prontuário Eletrônico.

- **Por que ele é necessário?**  
  O PIN substitui a assinatura física em papel e o carimbo de conselho, garantindo autenticidade jurídica à sua nota técnica e impedindo que outra pessoa assine em seu nome.
- **Como cadastrar ou alterar seu PIN:**
  1. Acesse o menu lateral: **Configurações** → **Minha Conta & PIN**.
  2. Localize o bloco **PIN Eletrônico de Assinatura Clínica**.
  3. Digite o seu novo PIN numérico (ex: `1234` ou `567890`) e confirme no campo ao lado.
  4. Digite a sua senha de login atual para confirmar que é você mesmo.
  5. Clique em **Salvar PIN de Assinatura**.

> ⚠️ **Aviso de Segurança Ética:**  
> Nunca compartilhe seu PIN com colegas de trabalho, estagiários ou recepcionistas. Todo atendimento assinado com seu PIN é de sua inteira responsabilidade profissional e legal perante o Conselho de Classe (CREFITO, CRP, CRFa, etc.) e o SUS.

### 2.4 Perfis de Acesso e Matriz de Permissões
O CER Digital opera com controle estrito de papéis (RBAC):

| Perfil de Usuário | O que pode acessar e fazer | O que NÃO pode acessar |
| :--- | :--- | :--- |
| **Recepção** (`recepcao`) | Visualiza Grade Semanal e Mapa Mensal; realiza novo agendamento; registra Check-in (presença/falta); consulta e cadastra pacientes; visualiza alertas de faltas. | Não tem acesso a anotações clínicas detalhadas do prontuário nem a telas de faturamento BPA-i. |
| **Terapeuta / Clínico** (`terapeuta`) | Acessa Grade Semanal; consulta histórico clínico no PEP; preenche e assina evoluções clínicas com PIN; lança termos de adendo; atualiza seu perfil e PIN. | Não altera configurações do sistema nem exporta lotes financeiros do SUS. |
| **Faturamento** (`faturamento`) | Audita a produção ambulatorial do mês; confere críticas SIGTAP de procedimentos; exporta arquivo magnético BPA-i (.txt); cadastra/consulta tabela SIGTAP. | Não assina evoluções clínicas de pacientes. |
| **Coordenação / Direção** (`coordenacao`) | Acesso irrestrito a todos os módulos: Dashboard de indicadores, gestão de usuários, salas, profissionais, pacientes, auditoria LGPD e sincronização e-SUS PEC. | — |

---

## 3. Módulo Recepção & Gestão de Agendamentos

O módulo de agendamento é o coração operacional da unidade. Ele organiza o fluxo diário, elimina conflitos de salas e registra a assiduidade dos usuários.

### 3.1 A Grade Semanal por Salas e Terapeutas
- **Como acessar:** Clique em **Grade Semanal** no menu lateral.
- **Estrutura:**
  - A tela divide a semana de **Segunda a Sexta-feira**.
  - O horário é organizado em **blocos de 30 minutos** (das 08:00 às 12:00 e das 13:00 às 17:00).
  - É possível filtrar a visualização por **Sala / Consultório** ou por **Terapeuta**.
  - Botões de navegação no topo permitem avançar ou retroceder as semanas do ano.

### 3.2 Novo Agendamento com Busca Inteligente
> ⚡ **Abertura Imediata:**  
> O modal de agendamento abre instantaneamente porque não carrega a lista pesada de dezenas de milhares de pacientes antecipadamente.

**Passo a passo para agendar:**
1. Na Grade Semanal, clique no botão azul **Novo Agendamento** (ou clique diretamente em um slot livre de horário).
2. O modal será exibido imediatamente com o campo de paciente limpo.
3. **Buscar o Paciente:**
   - Digite pelo menos **3 letras do nome**, ou o **CPF** (com ou sem pontos/traços), ou os **15 números do Cartão SUS (CNS)**.
   - O sistema buscará no banco de dados e exibirá até 20 opções correspondentes com: Nome Completo, CPF, CNS e Data de Nascimento.
   - Clique sobre o paciente desejado para selecioná-lo.
4. **Preencher os Parâmetros da Sessão:**
   - **Profissional Responsável:** Selecione o terapeuta que irá atender.
   - **Sala / Espaço:** Escolha a sala onde ocorrerá o atendimento.
   - **Procedimento SUS:** Selecione o procedimento que será realizado (o sistema lista os procedimentos compatíveis com o CBO do profissional).
   - **Data e Horário:** Defina o dia e o horário de início (slots de 30 min).
5. **Prevenção de Choques de Horário:**
   - O sistema verifica automaticamente se o paciente ou a sala já possuem outro agendamento no mesmo horário. Caso haja conflito, um aviso em vermelho impedirá a colisão, sugerindo outro horário.
6. Clique em **Confirmar Agendamento**.

### 3.3 Atendimento Compartilhado (Co-atendimento)
Muitas terapias em reabilitação exigem a atuação conjunta de duas ou mais especialidades (por exemplo: Fisioterapeuta e Terapeuta Ocupacional na Sala Sensorial, ou Fonoaudiólogo e Psicólogo na estimulação precoce).

**Como agendar um Co-atendimento:**
1. No modal de Novo Agendamento, selecione o primeiro profissional.
2. No campo **Profissionais Adicionais / Co-atendimento**, marque o segundo profissional participante.
3. O sistema registrará o agendamento compartilhado.
4. **Benefício no Faturamento SUS:** O CER Digital desmembrará essa sessão em duas linhas independentes de BPA-i (uma para cada profissional/CBO), sem duplicar o paciente nem acusar choque de agenda!

### 3.4 Horários Cativos / Recorrentes
Pacientes de reabilitação geralmente realizam tratamento continuado (ex: toda terça e quinta-feira às 09:00).
- Ao cadastrar o agendamento, marque a opção **Horário Cativo / Recorrente**.
- O sistema reservará aquele slot semanalmente para o paciente, facilitando a previsibilidade da unidade sem a necessidade de recadastrar o agendamento toda semana.

### 3.5 Check-in e Acolhimento Diário
Quando o paciente ou seu responsável chega à recepção:
1. No menu lateral, acesse **Recepção (Check-in)** ou visualize a grade do dia.
2. Localize o agendamento do paciente.
3. Alterne o status com um clique no botão correspondente:
   - 🔵 **Agendado:** Paciente marcado, mas ainda não se apresentou.
   - 🟡 **Presente / Na Sala de Espera:** Paciente chegou à unidade e está aguardando chamada do terapeuta.
   - 🟢 **Atendido:** Sessão realizada com sucesso pelo terapeuta.
   - 🟠 **Falta Justificada:** Paciente avisou com antecedência e apresentou atestado ou justificativa plausível.
   - 🔴 **Falta Injustificada:** Paciente não compareceu e não avisou.
   - ⚪ **Cancelado:** Atendimento desmarcado por motivo de força maior da unidade ou do profissional.

### 3.6 Alerta de Faltas e Controle de Absenteísmo
Para evitar vagas ociosas na reabilitação e garantir o cumprimento das filas de espera do SUS:
- **Regra das 3 faltas seguidas:** Se o paciente acumular 3 faltas consecutivas sem justificativa, o sistema exibirá uma tarja de **Alerta Vermelho de Absenteísmo**.
- **Regra das 5 faltas no mês:** Se o paciente acumular 5 faltas alternadas no mesmo mês, o alerta também é acionado.
- **Ação da Recepção:** Ao identificar o alerta, a recepção deve acionar o serviço social ou a coordenação para avaliar se o paciente necessita de transporte sanitário, mudança de turno ou se a vaga deve ser cedida para o próximo da fila regulada.

### 3.7 Mapa Mensal de Ocupação
- Acesse **Mapa Mensal** no menu lateral.
- Visualize o calendário completo do mês com a taxa percentual de ocupação de cada dia, volume de atendimentos concluídos e índice de absenteísmo global da unidade.

---

## 4. Módulo Prontuário Eletrônico do Paciente (PEP Multiprofissional)

O PEP do CER Digital é um prontuário eletrônico unificado onde todos os terapeutas da equipe (Fisioterapia, Fonoaudiologia, Terapia Ocupacional, Psicologia, Nutrição, Enfermagem e Serviço Social) registram suas condutas de forma integrada.

### 4.1 Abertura e Busca Sob Demanda no PEP
- Acesse **Prontuário (PEP)** no menu lateral.
- A tela inicial é limpa e intuitiva, convidando o profissional a pesquisar o paciente.
- No campo de busca:
  - Digite **ao menos 3 letras do nome**, **CPF** ou os **15 dígitos do Cartão SUS**.
  - O sistema responde em tempo real com os pacientes encontrados.
  - Clique sobre o paciente para abrir instantaneamente o seu histórico clínico completo.

### 4.2 Ficha Cadastral e Diagnóstico do Paciente
No topo do prontuário, você tem acesso rápido a:
- **Dados Pessoais:** Nome completo, Nome social, Nome da mãe, Idade e Data de nascimento, Município de residência e Telefone/WhatsApp do responsável.
- **Documentação SUS:** CNS (Cartão Nacional de Saúde validado) e CPF.
- **Diagnóstico Clínico Principal:** Código e descrição do CID-10 (ex: `G80.0 - Paralisia cerebral espástica quadriplégica`).
- **Modalidades de Reabilitação:** Ícones indicando as deficiências atendidas na unidade:
  - 🏃 **Física**
  - 🧠 **Intelectual**
  - 👁️ **Visual**
  - 👂 **Auditiva**
- **Vínculo com a Atenção Primária:** UBS de referência do município e nome do Agente Comunitário de Saúde (ACS) responsável pela microárea da família.

### 4.3 Registro de Evolução Clínica Estruturada
Para lançar o atendimento realizado no dia:
1. No prontuário do paciente, clique no botão verde **Nova Evolução Clínica**.
2. Preencha os campos estruturados da sessão:
   - **Especialidade / CBO:** Selecione sua área de atuação (ex: `2236-05 - Fisioterapeuta geral`).
   - **Procedimento SIGTAP Realizado:** Escolha o procedimento executado na sessão (ex: `03.01.07.011-3 - Atendimento em reabilitação física`).
   - **Queixa / Estado Atual:** Como o paciente se apresentou na sessão (sinais vitais, queixa de dor, tônus muscular, nível de atenção/cooperação).
   - **Conduta Terapêutica Realizada:** Descrição técnica dos exercícios, técnicas de estimulação, treino de marcha, atividades de vida diária (AVD) ou manobras aplicadas.
   - **Intercorrências / Observações:** Reações adversas, cansaço excessivo, recusa ou orientações dadas ao acompanhante para estímulo domiciliar.
3. Se desejar salvar um rascunho antes de finalizar, clique em **Salvar Rascunho**.

### 4.4 Assinatura Eletrônica com PIN e Hash SHA-256
Quando o relato clínico estiver completo e revisado:
1. Clique no botão azul **Assinar Evolução Eletronicamente**.
2. O sistema solicitará o seu **PIN Eletrônico** (4 a 6 dígitos numéricos).
3. Digite seu PIN e confirme.
4. **O que acontece nos bastidores:**
   - O sistema valida seu PIN criptografado.
   - É gerado um **Hash Criptográfico SHA-256** exclusivo com base no texto da evolução, data, hora e seu identificador profissional.
   - O carimbo oficial é gravado com data e hora exatas do sistema.
   - Um registro na produção BPA-i do faturamento é criado automaticamente em segundo plano.

### 4.5 A Regra de 24 Horas e a Imutabilidade Clínica
> ⏱️ **Regra de Ouro da Legislação em Saúde:**  
> Por determinação dos Conselhos Federais de Saúde e da legislação do SUS sobre prontuários eletrônicos:
> - **Dentro das primeiras 24 horas após a assinatura:** O próprio profissional que assinou pode editar eventuais erros de digitação na sua própria evolução.
> - **Após 24 horas da assinatura:** A evolução torna-se **100% IMUTÁVEL**. O botão de edição é desativado permanentemente e ninguém (nem mesmo a coordenação ou a equipe de TI) pode alterar ou apagar o texto original.

### 4.6 Termos de Adendo Retificadores
Se você precisar acrescentar uma informação clínica importante ou retificar um dado de uma evolução que já ultrapassou o prazo de 24 horas:
1. Localize a evolução assinada na linha do tempo do paciente.
2. Clique no botão **Adicionar Termo de Adendo (Observação Retificadora)**.
3. Digite o esclarecimento ou complemento (exemplo: *"Em complemento à evolução do dia 12/10, registra-se que a mãe trouxe a receita do novo anticonvulsivante prescrito pelo neuropediatra..."*).
4. Informe o seu **PIN Eletrônico** e confirme.
5. O Termo de Adendo ficará anexado visualmente à evolução original, com seu próprio carimbo de data, hora e assinatura eletrônica, preservando a transparência ética e pericial do prontuário.

### 4.7 Linha do Tempo e Leitura Interdisciplinar
Abaixo da ficha do paciente, a **Linha do Tempo Multiprofissional** exibe em ordem cronológica reversa (da mais recente para a mais antiga) todas as evoluções realizadas por todas as especialidades.
- Você pode ler com facilidade o que o Fonoaudiólogo relatou ontem antes de iniciar a sessão de Terapia Ocupacional hoje.
- Utilize os filtros rápidos no topo da linha do tempo para filtrar por:
  - Todas as especialidades;
  - Apenas minha especialidade;
  - Período de datas.

---

## 5. Módulo Faturamento SUS & BPA-i (SIA/SUS)

O faturamento do CER é realizado através do **Boletim de Produção Ambulatorial Individualizado (BPA-i)** do Ministério da Saúde. O CER Digital automatiza todo o processo, eliminando a digitação manual de planilhas no final do mês.

### 5.1 O que é o BPA-i e o Fluxo de Faturamento
Cada vez que um terapeuta realiza um atendimento e assina sua evolução clínica com PIN no PEP, o CER Digital cria automaticamente uma linha de produção ambulatorial contendo:
- Código do Estabelecimento de Saúde (CNES);
- Mês/Ano de competência;
- Cartão Nacional de Saúde (CNS) do profissional executante;
- Código Brasileiro de Ocupações (CBO) do profissional;
- Data do atendimento;
- Cartão Nacional de Saúde (CNS) e dados demográficos do paciente;
- Código do Procedimento SIGTAP (10 dígitos);
- Código do Diagnóstico Principal (CID-10);
- Caráter do atendimento e autorização.

### 5.2 Regras Automáticas da Tabela SIGTAP
O CER Digital valida preventivamente contra as regras oficiais do Ministério da Saúde:
1. **Compatibilidade CBO x Procedimento:** O profissional só pode faturar procedimentos autorizados para o seu CBO na tabela SIGTAP.
2. **Faixa Etária Permitida:** Verifica se a idade do paciente na data da sessão está dentro dos limites mínimo e máximo estipulados para o procedimento.
3. **Compatibilidade de Sexo:** Procedimentos restritos a determinado sexo não podem ser aplicados indevidamente.
4. **Validação Matemática do Cartão SUS:** O CNS do paciente deve ter 15 dígitos e respeitar o algoritmo do Ministério da Saúde (iniciados com 1 ou 2, ou provisórios iniciados com 7, 8 ou 9).
5. **CID-10 Primário:** O CID do paciente deve constar na relação de CIDs compatíveis com o procedimento no SIGTAP.

### 5.3 Painel de Auditoria Prévia de Produção
- Acesse **Faturamento BPA-i** no menu lateral.
- Selecione o **Mês / Competência** que deseja auditar (ex: `11/2026`).
- O painel exibe dois cartões principais:
  - 🟢 **Atendimentos Validados e Prontos para Envio:** Registros que atendem a 100% das regras SIGTAP e estão aptos para exportação.
  - 🔴 **Atendimentos com Inconsistências / Pendências:** Registros que necessitam de correção antes de exportar (exemplo: *"Paciente sem Cartão SUS cadastrado"* ou *"Idade do paciente incompatível com procedimento infantil"*).
- Clique no registro com pendência para abrir o atalho de correção cadastral.

### 5.4 Exportação do Arquivo Magnético Datasus (.txt)
Quando todas as pendências da competência forem sanadas:
1. No painel de Faturamento BPA-i, confira o valor total e a quantidade de procedimentos do lote.
2. Clique no botão azul **Exportar Arquivo Magnético BPA-i (.txt)**.
3. O sistema gerará instantaneamente o arquivo em formato oficial exigido pelo Datasus, contendo:
   - **Registro Tipo 01 (Header):** Cabeçalho identificador `#BPA#`, CNES da unidade, competência, quantidade de folhas e hash de controle.
   - **Registros Tipo 02 (Linhas de Produção Individualizada):** Cada atendimento com seus respectivos campos posicionais padronizados.
4. O download do arquivo (ex: `PA022611.TXT`) começará automaticamente em seu computador.

### 5.5 Importação no Software Oficial BPA Magnético
1. Abra o software oficial do Datasus instalado no computador do Faturamento (**BPA Magnético - SIA/SUS**).
2. Acesse o menu **Movimento** → **Importar Produção**.
3. Selecione o arquivo `.txt` que você baixou do CER Digital.
4. Clique em **Processar Importação**.
5. O sistema do Datasus emitirá o comprovante de consistência com 0 (zero) erros!

---

## 6. Módulo Gestão, Cadastros e Configurações

Exclusivo para coordenadores, diretores e administradores de TI.

### 6.1 Painel Inicial Operacional (Dashboard)
Ao fazer login, a coordenação visualiza um painel gerencial de alta performance contendo:
- Total de pacientes ativos atendidos na unidade;
- Atendimentos agendados, realizados e faltas no mês corrente;
- Distribuição percentual de pacientes por **Modalidade de Reabilitação** (Física, Intelectual, Visual e Auditiva);
- Atendimentos previstos para o dia com horários e salas;
- Gráficos de absenteísmo e taxa de ocupação dos consultórios.

### 6.2 Cadastro e Manutenção de Pacientes (com Paginação)
- Acesse **Configurações** → **Pacientes**.
- **Visualização Rápida com Paginação:** A listagem apresenta paginação inteligente (15, 25, 50 ou 100 pacientes por página), permitindo navegar com velocidade mesmo com mais de 38 mil registros cadastrados.
- **Filtros Dinâmicos:** Pesquise pacientes por Nome, CPF, Cartão SUS ou filtre por Modalidade de Reabilitação.
- **Cadastrar Novo Paciente:**
  - Clique em **Novo Paciente**.
  - Preencha os campos obrigatórios (Nome, Data de Nascimento, Sexo, Nome da Mãe, CPF, CNS de 15 dígitos e Município IBGE).
  - Selecione as **Modalidades de Reabilitação** vinculadas (pode marcar mais de uma: Física, Intelectual, Visual, Auditiva).
  - Informe o CID-10 principal e secundário.
  - Selecione a **UBS de Referência** e o **Agente Comunitário de Saúde (ACS)**.
  - Clique em **Salvar Paciente**.

### 6.3 Salas e Espaços Terapêuticos
- Acesse **Configurações** → **Salas & Espaços**.
- Permite cadastrar, ativar ou inativar salas da unidade (ex: *Consultório 01 - Fonoaudiologia*, *Ginásio de Fisioterapia Adulto*, *Sala de Estimulação Precoce e Sensorial*, *Consultório de Psicologia*).
- Salas inativadas deixam de aparecer como opção para novos agendamentos na grade semanal, preservando o histórico dos atendimentos passados.

### 6.4 Profissionais, Conselhos e CBOs SUS
- Acesse **Configurações** → **Equipe & Perfis**.
- Vincule cada usuário do sistema ao seu cadastro profissional:
  - Nome completo e CPF;
  - Conselho de Classe e Número de Inscrição (ex: CREFITO 12345-F, CRP 06/7890);
  - CNS do Profissional (obrigatório para o BPA-i);
  - CBO Principal (Classificação Brasileira de Ocupações no SUS).

### 6.5 Integração e Sincronização com o e-SUS APS PEC
O CER Digital possui integração nativa com o banco de dados oficial do **e-SUS Atenção Primária à Saúde (PEC)** do município.
- **Para que serve?**  
  Permite importar a base de cidadãos já cadastrados pelas Unidades Básicas de Saúde (UBS) e equipes de Saúde da Família (eSF), vinculando automaticamente a UBS de origem e o ACS de cada paciente do CER.
- **Como executar a sincronização:**
  1. Acesse **Configurações** → **Integração e-SUS PEC**.
  2. O painel informa o status da conexão com o banco PostgreSQL do PEC e a data da última importação.
  3. Clique no botão azul **Sincronizar Pacientes e Vínculos Agora**.
  4. O sistema executará a leitura e complementação de dados com segurança e sem duplicar cadastros.
- **Sincronização Automatizada via Terminal (TI):**  
  A equipe técnica pode agendar a sincronização periódica no servidor via cron:
  ```bash
  php artisan esus:sincronizar-pec --dry-run   # Simulação prévia
  php artisan esus:sincronizar-pec             # Execução efetiva
  ```

### 6.6 Trilha de Auditoria e Segurança (LGPD)
- Acesse **Configurações** → **Auditoria LGPD**.
- Em conformidade estrita com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018), o sistema registra de forma imutável:
  - Todo acesso a prontuários clínicos de pacientes (quem acessou, qual paciente, data e hora);
  - Todas as assinaturas eletrônicas e termos de adendo;
  - Exportações de lotes de faturamento;
  - Tentativas de login malsucedidas.
- Garante total transparência e rastreabilidade para auditorias do Ministério Público, Conselho Tutelar, Vigilância Sanitária ou Auditoria do SUS (DENASUS).

---

## 7. Checklists Diários por Perfil de Usuário

Utilize os roteiros práticos abaixo no dia a dia da unidade:

### 7.1 Checklist da Recepção
- [ ] **Abertura do Turno (07:45):** Acessar a **Grade Semanal** e verificar a lista de atendimentos previstos para a manhã.
- [ ] **Acolhimento na Chegada:** Ao receber o paciente ou acompanhante, localizar o agendamento e marcar o status como **Presente**.
- [ ] **Novos Encaminhamentos:** Cadastrar novos pacientes conferindo cuidadosamente Nome da Mãe, Data de Nascimento e os 15 números do Cartão SUS.
- [ ] **Monitoramento de Faltas (11:30 e 16:30):** Identificar pacientes agendados que não compareceram e registrar **Falta Injustificada** ou **Falta Justificada**.
- [ ] **Aviso de Absenteísmo:** Ao notar alerta de 3 faltas consecutivas, repassar a ficha para o Serviço Social da unidade.

### 7.2 Checklist do Terapeuta / Especialista
- [ ] **Início do Atendimento:** Acessar a **Grade Semanal** ou o **PEP** para conferir seu cronograma e o histórico do paciente.
- [ ] **Leitura Interdisciplinar:** Verificar se outros profissionais da equipe (Fisioterapia, TO, Fono, Psicologia) registraram observações recentes.
- [ ] **Registro da Sessão:** Logo após a sessão, abrir o prontuário do paciente e clicar em **Nova Evolução Clínica**.
- [ ] **Preenchimento Estruturado:** Descrever o estado atual, a conduta motora/sensorial/cognitiva realizada e eventuais intercorrências.
- [ ] **Assinatura com PIN:** Digitar seu PIN eletrônico de 4 a 6 dígitos para assinar e homologar o atendimento no SUS.
- [ ] **Revisão:** Lembrar que correções simples de digitação podem ser feitas em até 24 horas; após esse prazo, utilizar **Termo de Adendo**.

### 7.3 Checklist do Faturamento SUS
- [ ] **Semanalmente:** Acessar o painel **Faturamento BPA-i** e filtrar a competência em andamento.
- [ ] **Auditoria Preventiva:** Verificar a lista de "Atendimentos com Inconsistências" e solicitar à recepção ou terapeutas o ajuste de cadastros (ex: CNS incorreto, CBO divergente).
- [ ] **Fechamento Mensal (primeiros dias úteis):** Confirmar que 100% dos atendimentos concluídos estão no grupo "Prontos para Envio".
- [ ] **Exportação Datasus:** Clicar em **Exportar Arquivo Magnético BPA-i (.txt)**.
- [ ] **Importação no BPA Magnético:** Importar o arquivo no sistema oficial do Datasus, emitir o relatório de consistência e arquivar o comprovante.

### 7.4 Checklist da Coordenação e Gestão
- [ ] **Painel Inicial:** Acompanhar diariamente o volume de atendimentos e a taxa de absenteísmo global.
- [ ] **Gestão de Equipe:** Cadastrar novos colaboradores e atribuir os perfis de acesso adequados.
- [ ] **Sincronização e-SUS:** Conferir periodicamente se a integração com o banco do e-SUS PEC está atualizada.
- [ ] **Auditoria e LGPD:** Monitorar os logs de segurança e zelar pela confidencialidade dos dados de saúde dos usuários.

---

## 8. Perguntas Frequentes (FAQ) & Resolução de Problemas

### 1. Esqueci meu PIN de assinatura eletrônica. O que devo fazer?
**Resposta:** Acesse o menu lateral em **Configurações** → **Minha Conta & PIN**. No bloco do PIN, digite o novo PIN de sua escolha, informe sua senha de login atual e salve. O novo PIN passará a valer imediatamente.

### 2. Errei uma informação na evolução clínica e já se passaram mais de 24 horas. Como corrigir?
**Resposta:** Por determinação ética e legal, evoluções com mais de 24 horas não podem ser alteradas. Localize a evolução na linha do tempo do paciente, clique no botão **Adicionar Termo de Adendo (Observação Retificadora)**, descreva a correção ou acréscimo necessário e assine com seu PIN.

### 3. O paciente é recém-nascido e ainda não possui CPF ou Cartão SUS próprio. Posso agendar?
**Resposta:** Para o atendimento inicial de acolhimento, a recepção pode cadastrar os dados provisórios informando o Cartão SUS da mãe (desde que autorizado pelo protocolo municipal) ou solicitar urgência à UBS de origem para a geração do CNS definitivo do bebê, necessário para o faturamento BPA-i.

### 4. Dois terapeutas atenderam a mesma criança ao mesmo tempo. Como lançar?
**Resposta:** Na Grade Semanal, ao criar o agendamento, selecione o primeiro profissional e adicione o segundo no campo **Co-atendimento / Profissionais Adicionais**. No PEP, cada profissional abrirá a sessão e registrará sua respectiva nota técnica assinando com seu próprio PIN. O sistema gerará duas linhas de BPA-i sem duplicar a sessão.

### 5. O arquivo BPA-i gerou erro no software do Datasus acusando "CBO incompatível". Como resolver?
**Resposta:** Isso ocorre quando o procedimento realizado não é permitido para aquela categoria profissional segundo a tabela oficial SIGTAP do Ministério da Saúde. O faturamento ou a coordenação deve revisar a amarração em **Configurações** → **Tabela SIGTAP x CBO** ou corrigir o procedimento lançado na sessão.

### 6. Por que o sistema não carrega mais a lista inteira de pacientes ao abrir o agendamento ou prontuário?
**Resposta:** Nas versões anteriores, carregar mais de 38 mil pacientes de uma só vez deixava o navegador lento e travava computadores mais antigos. O CER Digital agora utiliza **busca dinâmica sob demanda**: basta digitar 3 letras ou números para o sistema trazer exatamente os resultados desejados em fração de segundo.

### 7. O que fazer quando o paciente acumula 3 faltas consecutivas?
**Resposta:** O sistema exibe automaticamente uma etiqueta vermelha de alerta na recepção. O fluxo recomendado é encaminhar o caso para o Serviço Social da unidade para contato telefônico com a família, investigação das causas (falta de transporte, internação, mudança) e orientação antes do desligamento da vaga.

### 8. Posso usar o CER Digital no celular ou tablet?
**Resposta:** Sim. O CER Digital é totalmente responsivo e adapta sua interface automaticamente para tablets e smartphones, com menu retrátil (drawer) facilitando anotações clínicas à beira do leito ou nos ginásios de reabilitação.

### 9. Como imprimir a ficha e as evoluções de um paciente para uma solicitação judicial ou junta médica?
**Resposta:** No Prontuário do Paciente (PEP), clique no botão **Imprimir Prontuário Completo** (ou utilize o atalho de impressão do navegador `Ctrl + P`). O sistema formata um documento limpo, com cabeçalho oficial do estabelecimento, dados cadastrais, histórico cronológico de todas as evoluções assinadas, carimbos de data/hora e identificação dos profissionais executantes.

### 10. O que significa o código de município IBGE no cadastro do paciente?
**Resposta:** É o código oficial de 6 dígitos do Instituto Brasileiro de Geografia e Estatística que identifica a cidade onde o paciente reside (ex: `270430` para Maceió). Esse campo é obrigatório para que o Ministério da Saúde valide o faturamento da produção ambulatorial.

---

## 9. Glossário de Termos do SUS e do Sistema

- **BPA-i (Boletim de Produção Ambulatorial Individualizado):** Instrumento de registro magnético do SIA/SUS onde cada atendimento ambulatorial é informado individualmente com o CNS do paciente e do profissional.
- **CBO (Classificação Brasileira de Ocupações):** Código numérico de 6 dígitos que identifica a ocupação profissional no SUS (ex: `2236-05` para Fisioterapeuta, `2238-10` para Fonoaudiólogo, `2239-05` para Terapeuta Ocupacional, `2515-10` para Psicólogo).
- **CER (Centro Especializado em Reabilitação):** Unidade de saúde ambulatorial especializada no diagnóstico, tratamento, concessão e adaptação de tecnologias assistivas para pessoas com deficiência.
- **CID-10 (Classificação Internacional de Doenças):** Código alfanumérico padronizado pela OMS que identifica a patologia ou condição clínica do paciente (ex: `F84.0` - Autismo infantil, `G80.0` - Paralisia cerebral).
- **CNES (Cadastro Nacional de Estabelecimentos de Saúde):** Código numérico de 7 dígitos que identifica a unidade de saúde perante o Ministério da Saúde.
- **CNS (Cartão Nacional de Saúde):** Número de 15 dígitos que identifica unicamente cada usuário e profissional no Sistema Único de Saúde.
- **e-SUS APS PEC (Prontuário Eletrônico do Cidadão):** Software oficial do Ministério da Saúde utilizado nas Unidades Básicas de Saúde da Atenção Primária.
- **Hash SHA-256:** Algoritmo matemático criptográfico que gera uma impressão digital única e inviolável de um documento eletrônico, garantindo que o texto não foi adulterado após a assinatura.
- **Horário Cativo:** Vaga de atendimento recorrente reservada semanalmente para um paciente que realiza terapia contínua de reabilitação.
- **Imutabilidade Clínica:** Princípio ético e legal que impede a alteração ou destruição de anotações médicas e terapêuticas após a sua finalização e assinatura.
- **LGPD (Lei Geral de Proteção de Dados - Lei 13.709/2018):** Legislação federal que disciplina o tratamento ético e seguro de dados pessoais e dados sensíveis de saúde.
- **PEP (Prontuário Eletrônico do Paciente):** Repositório digital que reúne todas as informações de saúde, condutas clínicas e diagnósticos do paciente.
- **PIN (Personal Identification Number):** Senha numérica pessoal utilizada pelo profissional de saúde para assinar digitalmente suas evoluções no sistema.
- **SIA/SUS (Sistema de Informações Ambulatoriais do SUS):** Sistema federal onde é processada toda a produção ambulatorial da rede pública de saúde.
- **SIGTAP (Sistema de Gerenciamento da Tabela de Procedimentos, Medicamentos e OPM do SUS):** Tabela mestre oficial do Ministério da Saúde que define regras, valores e compatibilidades de cada procedimento médico e terapêutico.
- **Termo de Adendo:** Nota técnica complementar ou retificadora anexada formalmente a uma evolução clínica após o encerramento do prazo de 24 horas.
- **UBS (Unidade Básica de Saúde):** Posto de saúde da Atenção Primária responsável pelo acolhimento inicial e território de residência da família.

---
*Manual elaborado pela equipe de desenvolvimento e homologado para a rede de Centros Especializados em Reabilitação (CER Digital — v1.16.0).*


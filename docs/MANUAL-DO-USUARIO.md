# Manual do Usuário — CER Digital

**Plataforma Integrada de Gestão Especializada em Reabilitação, Grade Multiprofissional e Faturamento SIA/SUS**

> **Versão do Sistema:** v1.21.2  
> **Público-alvo:** Recepção, Terapeutas e Equipe Clínica Multiprofissional, Faturamento SUS, Coordenação Geral e Tecnologia da Informação  
> **Homologação:** Ministério da Saúde / Datasus (BPA-i / SIA-SUS) e e-SUS APS PEC  
> **Desenvolvido por:** [KL Tecnologia](https://kltecnologia.com)

---

## Sumário

1. [Apresentação e Visão Geral do Sistema](#1-apresentação-e-visão-geral-do-sistema)
2. [Primeiros Passos, Login e Segurança](#2-primeiros-passos-login-e-segurança)
   - [2.1 Acessando a Plataforma e a Nova Landing Page](#21-acessando-a-plataforma-e-a-nova-landing-page)
   - [2.2 Nova Tela de Login Institucional](#22-nova-tela-de-login-institucional)
   - [2.3 Primeiro Acesso, Troca de Senha e Segurança](#23-primeiro-acesso-troca-de-senha-e-segurança)
   - [2.4 O PIN Eletrônico de Assinatura Clínica (Hash SHA-256)](#24-o-pin-eletrônico-de-assinatura-clínica-hash-sha-256)
   - [2.5 Perfis de Acesso e Matriz de Permissões (RBAC)](#25-perfis-de-acesso-e-matriz-de-permissões-rbac)
   - [2.6 Navegação Adaptada para Celulares e Tablets (Mobile-First)](#26-navegação-adaptada-para-celulares-e-tablets-mobile-first)
3. [Agenda Operacional Unificada & Recepção Inteligente](#3-agenda-operacional-unificada--recepção-inteligente)
   - [3.1 Visões Flexíveis: Dia, Grade Semanal e Mapa Mensal](#31-visões-flexíveis-dia-grade-semanal-e-mapa-mensal)
   - [3.2 Novo Agendamento com Alertas do Cartão SUS e Data de Nascimento](#32-novo-agendamento-com-alertas-do-cartão-sus-e-data-de-nascimento)
   - [3.3 Horários Cativos Recorrentes](#33-horários-cativos-recorrentes)
   - [3.4 Atendimento Compartilhado (Co-atendimento)](#34-atendimento-compartilhado-co-atendimento)
   - [3.5 Anti-choque de Horários e Trabalho Interno (Evolução)](#35-anti-choque-de-horários-e-trabalho-interno-evolução)
   - [3.6 Check-in Rápido, Acolhimento Diário e Horário de Chegada](#36-check-in-rápido-acolhimento-diário-e-horário-de-chegada)
   - [3.7 Alerta de Faltas e Controle Preventivo de Absenteísmo](#37-alerta-de-faltas-e-controle-preventivo-de-absenteísmo)
4. [Sistema de Chamada de Pacientes por Painel / TV](#4-sistema-de-chamada-de-pacientes-por-painel--tv)
   - [4.1 Como Funciona o Painel de TV](#41-como-funciona-o-painel-de-tv)
   - [4.2 Chamada por Voz com Alerta Sonoro](#42-chamada-por-voz-com-alerta-sonoro)
   - [4.3 Disparo pela Recepção e pelo Consultório](#43-disparo-pela-recepção-e-pelo-consultório)
   - [4.4 Pareamento Seguro de Dispositivos de TV via Código](#44-pareamento-seguro-de-dispositivos-de-tv-via-código)
5. [Prontuário Eletrônico Multiprofissional (PEP Multi)](#5-prontuário-eletrônico-multiprofissional-pep-multi)
   - [5.1 Busca Ágil e Abertura do PEP](#51-busca-ágil-e-abertura-do-pep)
   - [5.2 Ficha Cadastral, Diagnóstico CID-10 e Linhas de Cuidado](#52-ficha-cadastral-diagnóstico-cid-10-e-linhas-de-cuidado)
   - [5.3 Registro de Evolução Clínica Estruturada](#53-registro-de-evolução-clínica-estruturada)
   - [5.4 Assinatura Eletrônica com PIN e Hash SHA-256](#54-assinatura-eletrônica-com-pin-e-hash-sha-256)
   - [5.5 A Regra das 24 Horas e a Imutabilidade Clínica](#55-a-regra-das-24-horas-e-a-imutabilidade-clínica)
   - [5.6 Termos de Adendo Retificadores](#56-termos-de-adendo-retificadores)
   - [5.7 Linha do Tempo e Interdisciplinaridade](#57-linha-do-tempo-e-interdisciplinaridade)
   - [5.8 Impressão Oficial de Prontuário em Folha A4](#58-impressão-oficial-de-prontuário-em-folha-a4)
   - [5.9 Sub-aba "Meus Atendimentos": Consulta Rápida da Agenda do Terapeuta](#59-sub-aba-meus-atendimentos-consulta-rápida-da-agenda-do-terapeuta)
   - [5.10 Agendamento de Retorno / Novo Atendimento diretamente pelo PEP](#510-agendamento-de-retorno--novo-atendimento-diretamente-pelo-pep)
6. [Faturamento SUS & Lote Magnético BPA-i (SIA/SUS)](#6-faturamento-sus--lote-magnético-bpa-i-siasus)
   - [6.1 Fluxo Automático de Produção a partir do PEP](#61-fluxo-automático-de-produção-a-partir-do-pep)
   - [6.2 Validação Prévia e Regras SIGTAP](#62-validação-prévia-e-regras-sigtap)
   - [6.3 Painel de Auditoria de Lote por Competência](#63-painel-de-auditoria-de-lote-por-competência)
   - [6.4 Exportação do Arquivo Magnético Oficial (.txt Datasus)](#64-exportação-do-arquivo-magnético-oficial-txt-datasus)
   - [6.5 Importação no Software Oficial BPA Magnético](#65-importação-no-software-oficial-bpa-magnético)
7. [Módulo de Relatórios e Indicadores Gerenciais](#7-módulo-de-relatórios-e-indicadores-gerenciais)
   - [7.1 Apresentação e Caráter Exclusivamente Consultivo](#71-apresentação-e-caráter-exclusivamente-consultivo)
   - [7.2 Filtros Globais Dinâmicos e Atalhos de Período](#72-filtros-globais-dinâmicos-e-atalhos-de-período)
   - [7.3 Área 1: Visão Geral e Indicadores Executivos](#73-área-1-visão-geral-e-indicadores-executivos)
   - [7.4 Área 2: Análise de Atendimentos e Horários de Pico](#74-área-2-análise-de-atendimentos-e-horários-de-pico)
   - [7.5 Área 3: Monitoramento de Pacientes e Busca Ativa de Casos Críticos (3+ Faltas)](#75-área-3-monitoramento-de-pacientes-e-busca-ativa-de-casos-críticos-3-faltas)
   - [7.6 Área 4: Produtividade e Ocupação dos Profissionais](#76-área-4-produtividade-e-ocupação-dos-profissionais)
   - [7.7 Área 5: Taxa de Ocupação de Salas e Otimização da Grade](#77-área-5-taxa-de-ocupação-de-salas-e-otimização-da-grade)
   - [7.8 Área 6: Produção SUS / BPA-i e Estimativa Financeira SIGTAP](#78-área-6-produção-sus--bpa-i-e-estimativa-financeira-sigtap)
   - [7.9 Exportação em Planilha Universal (CSV) e Impressão Limpa em PDF](#79-exportação-em-planilha-universal-csv-e-impressão-limpa-em-pdf)
8. [Cadastros, Parâmetros e Integração e-SUS APS PEC](#8-cadastros-parâmetros-e-integração-e-sus-aps-pec)
   - [8.1 Painel Inicial Operacional (Dashboard)](#81-painel-inicial-operacional-dashboard)
   - [8.2 Dados do Estabelecimento e Logotipo Oficial](#82-dados-do-estabelecimento-e-logotipo-oficial)
   - [8.3 Cadastro e Manutenção de Pacientes com Paginação Ágil](#83-cadastro-e-manutenção-de-pacientes-com-paginação-ágil)
   - [8.4 Salas e Espaços Terapêuticos](#84-salas-e-espaços-terapêuticos)
   - [8.5 Gestão Unificada de Profissões e CBOs](#85-gestão-unificada-de-profissões-e-cbos)
   - [8.6 Cadastro da Rede de Atenção Primária / UBS](#86-cadastro-da-rede-de-atenção-primária--ubs)
   - [8.7 Integração e Sincronização com o e-SUS APS PEC](#87-integração-e-sincronização-com-o-e-sus-aps-pec)
   - [8.8 Trilha de Auditoria e Segurança (LGPD)](#88-trilha-de-auditoria-e-segurança-lgpd)
9. [Central de Ajuda & Linha do Tempo SemVer](#9-central-de-ajuda--linha-do-tempo-semver)
   - [9.1 Modal Pós-Login de Novidades da Versão](#91-modal-pós-login-de-novidades-da-versão)
   - [9.2 Central de Ajuda Navegável e Download do Manual](#92-central-de-ajuda-navegável-e-download-do-manual)
10. [Checklists Diários Operacionais por Função](#10-checklists-diários-operacionais-por-função)
    - [10.1 Checklist da Recepção](#101-checklist-da-recepção)
    - [10.2 Checklist do Terapeuta / Especialista](#102-checklist-do-terapeuta--especialista)
    - [10.3 Checklist do Faturamento SUS](#103-checklist-do-faturamento-sus)
    - [10.4 Checklist da Coordenação e Gestão](#104-checklist-da-coordenação-e-gestão)
11. [Perguntas Frequentes (FAQ) & Resolução de Dúvidas](#11-perguntas-frequentes-faq--resolução-de-dúvidas)
12. [Glossário de Termos do SUS e do Sistema](#12-glossário-de-termos-do-sus-e-do-sistema)

---

## 1. Apresentação e Visão Geral do Sistema

O **CER Digital** é uma plataforma corporativa web desenvolvida pela **KL Tecnologia** para atender às rotinas assistenciais, de agendamento, prontuário multiprofissional e faturamento ambulatorial de Centros Especializados em Reabilitação (CER II, CER III e CER IV) no âmbito do Sistema Único de Saúde (SUS).

A plataforma foi arquitetada com foco em quatro objetivos fundamentais:
1. **Substituição de processos manuais e planilhas avulsas:** Centraliza em um único ambiente web seguro o cronograma semanal, os formulários de produção e as fichas de evolução clínica em papel.
2. **Segurança Jurídica e Clínica para a Equipe:** Prontuário Eletrônico Multiprofissional (PEP) com assinatura eletrônica por PIN individual, cálculo de hash criptográfico SHA-256 e aplicação estrita da trava legal de 24 horas para imutabilidade clínica, conforme resoluções dos conselhos de classe (CFM, COFFITO, CFP, CFFa, CRESS) e diretrizes da LGPD.
3. **Agilidade no Acolhimento e Alta Performance:** Interfaces responsivas e leves, busca dinâmica de pacientes em milissegundos sem travamento de tela e painel audiovisual de chamada de pacientes na sala de espera.
4. **Faturamento SUS Automatizado (BPA-i):** Conversão imediata das evoluções clínicas assinadas em linhas de produção ambulatorial validadas preventivamente contra as regras da tabela SIGTAP do Ministério da Saúde (CBO, idade, sexo, CID-10 e Cartão SUS), permitindo a exportação oficial do arquivo magnético sem glosas.
5. **Governança e Inteligência Assistencial:** Módulo completo de relatórios e indicadores gerenciais exclusivamente consultivo para apoiar a coordenação na tomada de decisões, monitoramento de absenteísmo, ocupação das salas e cumprimento de metas contratuais com o SUS.

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      CICLO OPERACIONAL DO CER DIGITAL                   │
└─────────────────────────────────────────────────────────────────────────┘
        │
        ▼
 1. ACOLHIMENTO, AGENDAMENTO & RECEPÇÃO
    • Modos Dia, Grade Semanal e Mapa Mensal (turnos e blocos de 30 min)
    • Horário Cativo Recorrente e Co-atendimento compartilhado
    • Alerta imediato sobre Cartão SUS ausente ou inválido no agendamento
    • Check-in diário com registro do horário de chegada
    • Chamada sonora e visual de pacientes no Painel de TV
        │
        ▼
 2. ATENDIMENTO CLÍNICO MULTIPROFISSIONAL (PEP)
    • Linha do tempo multidisciplinar integrada (Fisio, TO, Fono, Psico, etc.)
    • Registro de evolução estruturada com Procedimento SIGTAP executado
    • Assinatura eletrônica mediante confirmação de PIN pessoal (SHA-256)
    • Trava legal de 24 horas (Retificações posteriores via Termo de Adendo)
        │
        ▼
 3. PRODUÇÃO & FATURAMENTO SUS (BPA-i)
    • Desmembramento automático de sessões compartilhadas
    • Pré-auditoria em memória contra regras oficiais do SIGTAP
    • Fechamento e exportação do arquivo magnético posicional (.txt Datasus)
        │
        ▼
 4. GESTÃO, INDICADORES & INTEGRAÇÃO
    • Relatórios gerenciais de produtividade, absenteísmo e ocupação
    • Exportação de dados para planilhas (CSV) e impressão limpa
    • Sincronização direta com a base de dados do e-SUS APS PEC
```

---

## 2. Primeiros Passos, Login e Segurança

### 2.1 Acessando a Plataforma e a Nova Landing Page
- Abra o navegador de sua preferência (**Google Chrome**, **Microsoft Edge**, **Mozilla Firefox** ou **Safari**). O sistema opera em conexão criptografada segura HTTPS (`cadeado de segurança`).
- Digite o endereço oficial fornecido pela coordenação da sua unidade (exemplo: `https://cer.municipio.gov.br`).
- Visitantes não autenticados visualizam a **Página Institucional Pública (Landing Page)**, que apresenta:
  - As modalidades de reabilitação atendidas pelo CER (Física, Intelectual, Auditiva e Visual);
  - Os princípios do cuidado humanizado interdisciplinar no SUS;
  - O botão de acesso restrito **Acessar o Sistema**, que direciona para a tela de login.

### 2.2 Nova Tela de Login Institucional
A tela de entrada foi concebida com design institucional contemporâneo, proporcionando clareza e segurança:
- Digite seu **E-mail institucional**.
- Digite sua **Senha de acesso**. Você pode utilizar o ícone de olho no campo de senha para **visualizar ou ocultar** os caracteres digitados, evitando erros de digitação.
- Clique em **Entrar no Sistema**.

### 2.3 Primeiro Acesso, Troca de Senha e Segurança
1. No primeiro acesso, utilize o e-mail e a senha provisória cadastrados pela coordenação.
2. Para alterar sua senha a qualquer momento:
   - Acesse o menu lateral em **Configurações** → **Minha Conta & PIN**.
   - No painel "Trocar Minha Senha de Acesso", informe sua senha atual, digite a nova senha desejada (mínimo de 8 caracteres) e confirme.
   - Clique em **Salvar Nova Senha**.
3. Se esquecer sua senha, solicite a redefinição à Coordenação Geral ou à equipe de TI da unidade.

### 2.4 O PIN Eletrônico de Assinatura Clínica (Hash SHA-256)
> 🔒 **O que é o PIN Eletrônico?**  
> É uma senha numérica pessoal de 4 a 6 dígitos solicitada **exclusivamente no momento em que você assina uma evolução clínica ou um termo de adendo** no Prontuário Eletrônico.

- **Validade Jurídica:** O PIN substitui o carimbo físico e a assinatura em papel. Ao confirmar o PIN, o sistema processa a nota clínica e gera uma assinatura criptográfica com **Hash SHA-256 de 64 caracteres**, garantindo autenticidade perante os Conselhos de Classe e a Auditoria do SUS.
- **Como cadastrar ou alterar seu PIN:**
  1. Acesse **Configurações** → **Minha Conta & PIN**.
  2. No painel "PIN Eletrônico de Assinatura Clínica", informe o novo PIN desejado (ex.: `4829` ou `192837`) e confirme.
  3. Digite sua senha de login atual para confirmar sua identidade e clique em **Salvar PIN de Assinatura**.

> ⚠️ **Aviso Ético:** O PIN é estritamente pessoal e intransferível. Jamais repasse seu PIN a recepcionistas, colegas ou estagiários. Qualquer anotação clínica assinada sob seu PIN é de sua inteira responsabilidade profissional e legal.

### 2.5 Perfis de Acesso e Matriz de Permissões (RBAC)

O CER Digital protege os dados dos pacientes por meio de controle rigoroso de papéis de acesso:

| Perfil de Usuário | Atribuições e Acessos Permitidos | Restrições de Sigilo e Segurança |
| :--- | :--- | :--- |
| **Recepção** (`recepcao`) | Visualiza Agenda nos modos Dia, Grade e Mês; realiza novos agendamentos; executa check-in diário; chama pacientes na TV; cadastra pacientes; consulta alertas de absenteísmo. | **Acesso estritamente bloqueado** a anotações clínicas, diagnósticos detalhados do prontuário e telas de faturamento financeiro. |
| **Terapeuta** (`terapeuta`) | Visualiza a agenda de atendimentos; consulta histórico clínico no PEP; preenche e assina evoluções clínicas estruturadas com PIN; adiciona termos de adendo; chama pacientes na TV pelo consultório. | Não pode alterar parâmetros do sistema, cadastros de salas ou exportar lotes do BPA-i. |
| **Faturamento** (`faturamento`) | Audita a produção ambulatorial do mês; revisa consistências SIGTAP; consulta tabelas de procedimentos; exporta arquivo magnético BPA-i (.txt) para o Datasus. | Não assina evoluções clínicas nem tem acesso de edição a anotações terapêuticas. |
| **Coordenação** (`coordenacao`) | Acesso integral: relatórios e indicadores gerenciais, gestão de usuários, salas, profissionais, pacientes, auditoria LGPD e integração com o e-SUS PEC. | Acesso total com trilha de auditoria contínua de todas as ações. |

### 2.6 Estrutura do Menu Lateral por Áreas Funcionais

Para proporcionar uma experiência de uso ágil, intuitiva e sem sobrecarga visual, o menu lateral do CER Digital organiza suas rotinas em seis áreas de trabalho bem delimitadas:

1. **INÍCIO**
   - **Painel Inicial:** Visão executiva diária, taxa de ocupação, sessões confirmadas e absenteísmo.
2. **ATENDIMENTO**
   - **Agenda Operacional:** Acesso consolidado aos modos Dia (Check-in), Grade Semanal e Mapa Mensal.
   - **Pacientes:** Prontuário cadastral demográfico, validação Módulo 11 do Cartão SUS e UBS de referência.
   - **Prontuário Eletrônico:** Prontuário Multiprofissional (PEP Multi), histórico unificado e evoluções clínicas.
   - **Painel de Chamadas:** Painel de chamada de pacientes na TV da sala de espera (abertura em nova aba).
3. **GESTÃO**
   - **Relatórios e Indicadores:** Relatórios gerenciais de atendimentos, pacientes, profissionais, salas e BPA-i.
   - **Faturamento BPA-i:** Auditoria prévia, validação SIGTAP e geração do arquivo magnético SUS.
4. **CADASTROS** (recolhido inicialmente para poupar espaço)
   - **Equipe e Profissionais:** Gestão de usuários, senhas, papéis RBAC e CBOs vinculados.
   - **Salas e Espaços:** Consultórios e ginásios da unidade.
   - **Profissões e CBOs:** Classificação Brasileira de Ocupações oficial do MTE/SUS.
   - **Unidades de Saúde (UBS):** Rede de atenção primária de referência da região.
   - **Procedimentos SIGTAP:** Parametrização da tabela SUS e matriz de compatibilidade CBO.
5. **ADMINISTRAÇÃO** (recolhido inicialmente para poupar espaço)
   - **Dados do Estabelecimento:** CNES, código IBGE municipal e logotipo da unidade.
   - **Integração e-SUS PEC:** Conexão segura somente-leitura com PostgreSQL para sincronização de cadastros.
   - **Configuração do Painel TV:** Parâmetros de síntese de voz, áudio, privacidade e token de smart TV.
   - **Auditoria e Segurança:** Trilha de auditoria imutável (LGPD) com hash SHA-256 e rastreabilidade total.
6. **RODAPÉ — CONTA E SUPORTE**
   - **Minha Conta e PIN:** Troca pessoal de senha de login e definição do PIN eletrônico de assinatura clínica.
   - **Ajuda e Manual:** Manual interativo, atalhos de navegação e histórico detalhado de versões.
   - **Identificação do Usuário e Sair:** Exibição do perfil ativo e botão de encerramento seguro de sessão.

> 💡 **Comportamento Inteligente dos Grupos:** Os grupos **Cadastros** e **Administração** permanecem recolhidos por padrão para manter a tela limpa. No entanto, ao acessar diretamente uma tela pertencente a esses grupos (como `/configuracoes/salas` ou `/configuracoes/esus`), o sistema expande automaticamente o grupo correspondente e destaca o item ativo em verde-água.

### 2.7 Navegação Adaptada para Celulares e Tablets (Mobile-First)
Todas as telas do CER Digital foram aprimoradas para operar de maneira perfeita em dispositivos móveis (smartphones e tablets):
- **Menu Lateral Retrátil (Drawer):** Gaveta lateral deslizante que trava a rolagem do fundo da página enquanto aberta e fecha automaticamente ao tocar em qualquer opção de navegação.
- **Tabelas em Formato de Cartões:** As listas de agendamentos, pacientes e relatórios adaptam-se automaticamente em cartões de leitura fácil, eliminando a necessidade de rolar a tela horizontalmente.
- **Formulários e Modais Otimizados:** Campos confortáveis para o toque (alvo mínimo de 44px), com rolagem vertical suave e teclados numéricos apropriados para campos de CPF, CNS e PIN.

---

## 3. Agenda Operacional Unificada & Recepção Inteligente

### 3.1 Visões Flexíveis: Dia, Grade Semanal e Mapa Mensal
Acesse **Agenda** no menu lateral. No topo da tela, você pode alternar facilmente entre três modos de visualização:
1. **Modo Dia:** Lista sequencial cronológica dos atendimentos do dia selecionado, organizada por horários e salas, com status de presença e ações rápidas.
2. **Modo Grade Semanal:** Visualização matricial de Segunda a Sexta-feira, com slots de 30 minutos (08h às 12h e 13h às 17h) distribuídos por salas e profissionais.
3. **Modo Mapa Mensal:** Calendário completo do mês com percentuais de ocupação diária, volume de sessões agendadas e taxa de absenteísmo global.

### 3.2 Novo Agendamento com Alertas do Cartão SUS e Data de Nascimento
Ao clicar em **Novo Agendamento** (ou clicar em um horário livre da grade):
1. O formulário abre instantaneamente sem lentidão.
2. **Buscar o Paciente:** Digite ao menos 3 letras do nome, CPF ou os 15 dígitos do Cartão SUS. O sistema localiza o paciente em tempo real.
3. **Validação Imediata de Dados Essenciais SUS:**
   - **Se o paciente não possui Cartão SUS cadastrado:** O sistema exibe um aviso claro em destaque:  
     > **Atenção: paciente sem Cartão SUS**  
     > *"Este paciente não possui o número do Cartão SUS (CNS) cadastrado. Para realizar o agendamento, atualize o cadastro do paciente."*  
     O formulário apresenta um atalho direto para atualizar o cadastro e revalidar os dados na hora sem perder as informações já preenchidas!
   - **Se o Cartão SUS estiver cadastrado, porém for matematicamente inválido:**  
     > **Atenção: Cartão SUS inválido**  
     > *"O número do Cartão SUS cadastrado para este paciente é inválido. Confira e atualize essa informação antes de continuar."*
   - **Se faltar a Data de Nascimento:** O sistema informa com clareza o dado ausente antes da confirmação do agendamento.
4. **Parâmetros da Sessão:** Selecione o Profissional, a Sala e o Procedimento SUS compatível com o CBO.
5. Clique em **Confirmar Agendamento**.

### 3.3 Horários Cativos Recorrentes
Pacientes em tratamento contínuo de reabilitação necessitam de horários fixos na semana.
- Ao criar o agendamento, marque a opção **Horário Cativo / Recorrente** e selecione a quantidade de semanas (ex.: 4, 8 ou 12 semanas).
- O sistema aloca o paciente no mesmo dia e horário ao longo do período selecionado, poupando o trabalho de recriar o agendamento toda semana.

### 3.4 Atendimento Compartilhado (Co-atendimento)
Quando dois terapeutas de especialidades distintas atendem o mesmo usuário na mesma sala (ex.: Fisioterapeuta e Terapeuta Ocupacional na Sala de Integração Sensorial):
- No modal de agendamento, selecione o primeiro profissional e adicione o segundo no campo **Co-atendimento / Profissionais Adicionais**.
- O sistema registra o atendimento sem conflito de sala e desmembra automaticamente a produção de faturamento em duas linhas independentes de BPA-i.

### 3.5 Anti-choque de Horários e Trabalho Interno (Evolução)
- **Anti-choque Inteligente:** O sistema impede que o mesmo paciente ou a mesma sala física receba dois agendamentos no mesmo horário (exceto quando configurado expressamente como Co-atendimento).
- **Horário de Trabalho Interno ("EVOLUÇÃO"):** É possível reservar horários na grade para trabalho administrativo, discussão de casos clínicos e reuniões de equipe, identificados visualmente com bloqueio de novos agendamentos de pacientes.

### 3.6 Check-in Rápido, Acolhimento Diário e Horário de Chegada
Ao acolher o paciente na recepção da unidade:
- Localize o paciente na lista do dia e clique no status desejado:
  - 🔵 **Agendado:** Horário marcado, paciente ainda não se apresentou.
  - 🟡 **Presente / Chegada:** Registra o comparecimento e grava automaticamente o carimbo de data e hora de chegada (`chegada_em`), indicando que o paciente está na sala de espera aguardando chamada.
  - 🟢 **Atendido:** Sessão clínica realizada e concluída com o terapeuta.
  - 🟠 **Falta Justificada:** Paciente ausente com justificativa médica ou aviso prévio documentado.
  - 🔴 **Falta Injustificada:** Paciente ausente sem comunicação prévia.
  - ⚪ **Cancelado:** Horário desmarcado pela unidade ou pelo profissional por motivo de força maior.

### 3.7 Alerta de Faltas e Controle Preventivo de Absenteísmo
O sistema monitora continuamente a assiduidade dos pacientes para evitar ociosidade na rede pública:
- **Alerta de 3 Faltas Consecutivas:** Acionado imediatamente quando o usuário falta a 3 sessões seguidas sem justificativa.
- **Alerta de 5 Faltas Alternadas:** Acionado quando o usuário acumula 5 faltas no mês corrente.
- **Ação:** O agendamento recebe uma tarja vermelha de destaque preventivo, alertando a equipe para acionar o Serviço Social para contato telefônico, averiguação das causas e busca ativa antes da perda da vaga regulada.

---

## 4. Sistema de Chamada de Pacientes por Painel / TV

### 4.1 Como Funciona o Painel de TV
O CER Digital disponibiliza uma tela dedicada para Smart TVs e monitores instalados na sala de espera (`/painel-chamadas`).
- A tela exibe o logotipo oficial do estabelecimento, o relógio digital sincronizado, o histórico das últimas chamadas e o destaque principal do paciente chamado no momento.
- Funciona em qualquer Smart TV com navegador moderno ou conectada a um mini-computador/TV Box via cabo HDMI ou rede Wi-Fi.

### 4.2 Chamada por Voz com Alerta Sonoro
Ao realizar a chamada de um paciente:
1. O painel emite uma campainha harmônica sonora de atenção para a sala de espera.
2. A voz sintetizada em português brasileiro (Web Speech API) anuncia com clareza:  
   *"Atenção: [Nome do Paciente], favor dirigir-se à [Sala / Consultório]."*
3. O nome do paciente e a sala piscam em destaque na tela, ficando registrados na lateral como última chamada.

### 4.3 Disparo pela Recepção e pelo Consultório
- **Pela Recepção:** Ao marcar o paciente como **Presente** na recepção, a recepcionista pode acionar o botão de chamada de acolhimento.
- **Pelo Consultório do Terapeuta:** Dentro da tela de atendimento ou prontuário, o terapeuta conta com o botão **Chamar Paciente na TV**. Ao clicar, o painel da sala de espera anuncia imediatamente a chamada para o consultório correspondente.

### 4.4 Pareamento Seguro de Dispositivos de TV via Código
Para evitar que telas não autorizadas acessem a chamada de pacientes:
1. Ao abrir o endereço `/painel-chamadas` em uma nova Smart TV, a tela exibe um **Código Numérico de Pareamento de 6 Dígitos** (ex.: `849 201`).
2. O coordenador acessa **Configurações** → **Painel de Chamadas / TVs** no computador da unidade.
3. Informa o código exibido na TV, atribui um nome para o aparelho (ex.: *TV Sala de Espera Principal*) e clica em **Autorizar Dispositivo**.
4. A TV é pareada instantaneamente e passa a transmitir as chamadas em tempo real.

---

## 5. Prontuário Eletrônico Multiprofissional (PEP Multi)

### 5.1 Busca Ágil e Abertura do PEP
- Acesse **Prontuário (PEP)** no menu lateral.
- No campo de pesquisa instantânea, digite 3 letras do nome do paciente, o CPF ou os 15 dígitos do Cartão SUS.
- Clique sobre o paciente encontrado para carregar instantaneamente seu histórico clínico completo.

### 5.2 Ficha Cadastral, Diagnóstico CID-10 e Linhas de Cuidado
No cabeçalho do prontuário, você encontra um resumo clínico essencial:
- **Identificação Completa:** Nome completo, Nome Social (se houver), Nome da Mãe, Idade/Data de nascimento e Município de residência.
- **Documentos SUS:** CPF e Cartão Nacional de Saúde (CNS).
- **Diagnóstico CID-10:** Código e descrição da patologia primária e secundária.
- **Modalidades de Reabilitação:** Emblemas visuais indicando as linhas de cuidado vinculadas (Física, Intelectual, Auditiva e Visual).
- **Rede Básica:** Unidade Básica de Saúde (UBS) de referência e nome do Agente Comunitário de Saúde (ACS).

### 5.3 Registro de Evolução Clínica Estruturada
Para registrar o atendimento do dia:
1. No prontuário do paciente, clique no botão verde **Nova Evolução Clínica**.
2. Preencha os campos estruturados da sessão:
   - **Especialidade / CBO:** Sua ocupação profissional no atendimento.
   - **Procedimento SIGTAP Executado:** Selecione o procedimento SUS correspondente (ex.: *03.01.07.011-3 - Atendimento em reabilitação física*).
   - **Queixa / Estado Atual:** Relato do estado funcional e queixas apresentadas pelo paciente na chegada.
   - **Conduta Realizada:** Descrição técnica das intervenções, exercícios, treino funcional ou orientações aplicadas.
   - **Intercorrências / Observações:** Reações adversas, cansaço, orientações à família ou encaminhamentos.
3. Se precisar interromper momentaneamente o preenchimento, utilize **Salvar Rascunho**.

### 5.4 Assinatura Eletrônica com PIN e Hash SHA-256
Após revisar as informações registradas:
1. Clique em **Assinar Evolução Eletronicamente**.
2. Digite seu **PIN Numérico de Assinatura** (4 a 6 dígitos) e confirme.
3. O sistema valida seu PIN, carimba a data e hora exatas do atendimento, gera a assinatura digital com **Hash SHA-256 de 64 caracteres** e envia automaticamente os dados para a produção mensal do faturamento BPA-i.

### 5.5 A Regra das 24 Horas e a Imutabilidade Clínica
> ⏱️ **Garantia de Segurança Pericial e Legal:**  
> - **Primeiras 24 horas:** O próprio profissional que assinou pode efetuar correções pontuais de digitação em sua própria anotação.
> - **Após 24 horas da assinatura:** A anotação torna-se **estritamente IMUTÁVEL**. O sistema bloqueia qualquer modificação ou exclusão do texto original.

### 5.6 Termos de Adendo Retificadores
Caso seja necessário retificar ou complementar uma evolução clínica após o encerramento do prazo de 24 horas:
1. Localize a evolução assinada na linha do tempo.
2. Clique no botão **Adicionar Termo de Adendo (Observação Retificadora)**.
3. Escreva a nota complementar técnica informando o dado a ser retificado ou acrescentado.
4. Digite seu PIN de assinatura e confirme.
5. O termo de adendo ficará visualmente vinculado à evolução original com carimbo próprio de data, hora e assinatura, mantendo a integridade histórica exigida por lei.

### 5.7 Linha do Tempo e Interdisciplinaridade
A linha do tempo reúne todas as anotações clínicas de todas as especialidades em ordem cronológica reversa.
- Antes de iniciar a sessão, o terapeuta pode conferir o que os demais membros da equipe (Fisioterapeuta, Fonoaudiólogo, Psicólogo, Terapeuta Ocupacional, Assistente Social) anotaram nas sessões anteriores.
- Filtros rápidos permitem visualizar todas as especialidades ou isolar anotações de uma área específica.

### 5.8 Impressão Oficial de Prontuário em Folha A4
Para fornecer relatórios a juntas médicas, Ministério Público ou auditorias do SUS:
- Clique no botão **Imprimir Prontuário** no topo da tela do PEP (ou utilize `Ctrl + P`).
- O sistema formata um relatório clínico limpo, organizado em folhas A4 com logotipo oficial da unidade, cabeçalho institucional, dados demográficos, histórico completo de evoluções assinadas, carimbos de PIN e hashes SHA-256.

### 5.9 Sub-aba "Meus Atendimentos": Consulta Rápida da Agenda do Terapeuta
Para consultar os atendimentos sob sua responsabilidade diretamente pelo PEP, sem precisar transitar para a Agenda:
1. No topo da tela do Prontuário, clique na sub-aba **Meus Atendimentos**.
2. Utilize os filtros integrados:
   - **Período:** Hoje, Próximos atendimentos, Atendimentos anteriores ou Todos os períodos.
   - **Situação:** Todos os status, Agendado, Presente, Atendido, Falta ou Cancelado.
3. Cada linha exibe o nome do paciente, data e horário, sala alocada, se há atendimento compartilhado e o status da evolução clínica (Registrada ou Pendente).
4. O botão **Abrir Prontuário →** carrega o paciente selecionado na aba clínica.

### 5.10 Agendamento de Retorno / Novo Atendimento diretamente pelo PEP
Quando o terapeuta estiver em atendimento e acordar a data do próximo retorno com o paciente ou acompanhante:
1. Clique no botão **Novo Atendimento** no cabeçalho do PEP (ou no atalho **+ Agendar retorno** na tabela de atendimentos do paciente).
2. O sistema pré-seleciona automaticamente o paciente atual e o profissional autenticado.
3. Escolha a data, horário, sala e, se necessário, adicione outros colegas para atendimento compartilhado ou configure repetição semanal.
4. O sistema valida instantaneamente eventuais conflitos de horário e a regularidade do Cartão SUS do paciente.
5. Ao confirmar, o agendamento é registrado com status inicial `AGENDADO`, integrando-se à grade geral.
> ⚠️ **Aviso de Presença:** O registro de evolução clínica e faturamento BPA-i requer que o paciente tenha sua presença confirmada na recepção (`PRESENTE` ou `ATENDIDO`). Agendamentos futuros podem ser consultados, mas não permitem evolução antecipada indevida.

---

## 6. Faturamento SUS & Lote Magnético BPA-i (SIA/SUS)

### 6.1 Fluxo Automático de Produção a partir do PEP
No CER Digital, a equipe de faturamento não precisa redigitar fichas de produção no fim do mês:
- Cada evolução clínica assinada com PIN no PEP alimenta diretamente a tabela de produção ambulatorial do mês.
- Atendimentos compartilhados (Co-atendimento) são desmembrados automaticamente em registros independentes para cada terapeuta com seu respectivo CBO e procedimento SIGTAP.

### 6.2 Validação Prévia e Regras SIGTAP
Antes de qualquer fechamento, o motor de auditoria do sistema confere cada registro contra as regras oficiais do Ministério da Saúde:
- **Compatibilidade `CBO x Procedimento`:** Impede que um profissional fature procedimentos não autorizados para sua categoria profissional.
- **Faixa Etária:** Valida se a idade do paciente é compatível com os limites estabelecidos pelo Ministério da Saúde.
- **Compatibilidade de Sexo:** Verifica restrições de sexo do procedimento.
- **Cartão SUS (CNS):** Valida matematicamente os 15 dígitos do CNS do paciente e do profissional executante.
- **Compatibilidade de CID-10:** Verifica se o diagnóstico informado é aceito pelo procedimento no SIGTAP.

### 6.3 Painel de Auditoria de Lote por Competência
- Acesse **Faturamento BPA-i** no menu lateral.
- Selecione o mês/ano de competência desejado (ex.: `202611`).
- O painel divide a produção em dois blocos claros:
  - 🟢 **Registros Validados e Prontos:** Atendimentos consistentes e aptos para remessa ao SUS.
  - 🔴 **Registros com Inconsistências:** Atendimentos com pendências cadastrais (ex.: paciente sem CNS ou divergência de CBO). O painel oferece atalho imediato para regularização cadastral.

### 6.4 Exportação do Arquivo Magnético Oficial (.txt Datasus)
Após sanar todas as pendências da competência:
1. No painel de Faturamento, revise o total de atendimentos e o valor apurado.
2. Clique no botão **Exportar Arquivo Magnético BPA-i (.txt)**.
3. O sistema gera em transmissão direta o arquivo posicional oficial do Datasus:
   - **Linha Tipo 01 (Header `#BPA#`):** 130 colunas com CNES da unidade, competência e checksum de integridade.
   - **Linhas Tipo 03 (Produção Individualizada):** 350 colunas com os campos posicionais de cada procedimento, paginados em folhas de até 20 linhas por profissional.
4. O arquivo (exemplo: `PA022611.TXT`) é descarregado diretamente no computador.

### 6.5 Importação no Software Oficial BPA Magnético
1. Abra o software oficial do Ministério da Saúde (**BPA Magnético - SIA/SUS**) no computador do faturamento.
2. Acesse o menu **Movimento** → **Importar Produção**.
3. Selecione o arquivo `.txt` descarregado do CER Digital e confirme.
4. O software do Datasus processará a importação com 100% de consistência.

---

## 7. Módulo de Relatórios e Indicadores Gerenciais

O novo módulo de **Relatórios** (`/relatorios`) é uma ferramenta analítica de alto nível desenvolvida especificamente para apoiar a coordenação e a direção do CER.

### 7.1 Apresentação e Caráter Exclusivamente Consultivo
- **Segurança Operacional:** O módulo de Relatórios é **estritamente consultivo**. A consulta ou exportação de relatórios não altera em hipótese alguma o status de nenhum agendamento, evolução clínica ou lote de faturamento.
- **Desempenho Otimizado:** Consultas construídas com agregações diretas no banco de dados, garantindo respostas rápidas sem onerar a memória da VPS.

### 7.2 Filtros Globais Dinâmicos e Atalhos de Período
No topo do módulo de relatórios, utilize a barra de filtros compartilhada entre todas as abas:
- **Atalhos Rápidos de Período:** Clique nos botões **Hoje**, **Últimos 7 dias**, **Mês Atual**, **Mês Anterior** ou **Ano Atual** para preenchimento imediato das datas.
- **Seleção Manual de Datas:** Defina livremente a Data Inicial e a Data Final desejadas.
- **Filtros por Profissional, Especialidade e Sala:** Filtre por profissional específico, por conselho/CBO ou por espaço terapêutico da unidade.
- **Filtro por Modalidade:** Analise dados isolados de reabilitação Física, Intelectual, Auditiva ou Visual.
- **Competência BPA-i:** Para dados de faturamento, selecione a competência no formato `AAAAMM`.
- **Botão Limpar Filtros:** Restaura a visualização padrão com um único clique.

### 7.3 Área 1: Visão Geral e Indicadores Executivos
A aba **Visão Geral** consolida os números mais expressivos do período:
- **Cartões Executivos:**
  - Total de Agendamentos no Período;
  - Taxa Percentual de Presença (pacientes acolhidos e atendidos);
  - Taxa Percentual de Absenteísmo (faltas injustificadas e justificadas);
  - Novos Pacientes Inseridos em Tratamento;
  - Volume de Produção SUS Gerada no Período.
- **Gráfico de Evolução Diária:** Curva diária de comparecimentos, atendimentos e faltas.
- **Distribuição por Modalidade de Reabilitação:** Participação percentual de cada deficiência no volume assistencial.
- **Quadro de Atenção Imediata:** Resumo de indicadores críticos para intervenção da coordenação.

### 7.4 Área 2: Análise de Atendimentos e Horários de Pico
A aba **Atendimentos** aprofunda os registros da recepção:
- **Distribuição Detalhada de Status:** Contagem e percentual de sessões nos estados: Atendido, Presente, Agendado, Falta Justificada, Falta Injustificada e Cancelado.
- **Causas e Motivos de Falta / Cancelamento:** Levantamento dos motivos mais recorrentes para suporte à busca ativa.
- **Horários de Pico:** Comparativo de fluxo de pacientes entre o turno da manhã (`08h–12h`) e o turno da tarde (`13h–17h`), permitindo balancear a ocupação das salas.

### 7.5 Área 3: Monitoramento de Pacientes e Busca Ativa de Casos Críticos (3+ Faltas)
A aba **Pacientes** oferece instrumentos poderosos para o Serviço Social e para a gestão da fila de espera:
- **Lista Prioritária de Pacientes Críticos:** Relação nominal dos usuários que atingiram **3 ou mais faltas consecutivas** ou **5 ou mais faltas no mês**, com dados de contato, telefone/WhatsApp do responsável e modalidade vinculada para realização imediata de busca ativa.
- **Perfil Demográfico:** Análise de pacientes em acompanhamento por faixa etária (crianças, adolescentes, adultos e idosos), sexo e municípios de residência.

### 7.6 Área 4: Produtividade e Ocupação dos Profissionais
A aba **Profissionais** apresenta o desempenho de cada membro da equipe clínica:
- **Tabela Comparativa de Produtividade:** Relação por profissional contendo Especialidade/CBO, Total de Agendamentos, Atendimentos Concluídos, Faltas Registradas e Taxa de Ocupação da Agenda.
- **Identificação de Sobrecarga ou Ociosidade:** Permite identificar terapeutas com agendas saturadas ou vagas ociosas para remanejamento inteligente de novos pacientes.

### 7.7 Área 5: Taxa de Ocupação de Salas e Otimização da Grade
A aba **Salas e Ocupação** analisa o uso da infraestrutura física do CER:
- **Taxa Média de Ocupação por Sala:** Percentual de horas em que cada consultório, ginásio ou sala sensorial esteve efetivamente ocupado por atendimentos.
- **Turnos Mais Disputados:** Análise comparativa entre turnos matutino e vespertino por espaço.
- **Gestão de Espaços:** Subsídio técnico para a coordenação remanejar atendimentos de salas sobrecarregadas para espaços disponíveis.

### 7.8 Área 6: Produção SUS / BPA-i e Estimativa Financeira SIGTAP
A aba **Produção SUS / BPA-i** conecta a prática clínica à sustentabilidade financeira da unidade:
- **Total de Procedimentos Realizados:** Volume total de atos ambulatoriais registrados pelas evoluções clínicas assinadas.
- **Estimativa de Faturamento Financeiro (R$):** Valor monetário estimado com base na tabela SIGTAP do Ministério da Saúde para os procedimentos executados no período.
- **Procedimentos Mais Frequentes:** Ranking dos códigos SIGTAP mais faturados pela unidade.
- **Consistência de Lote:** Resumo de procedimentos prontos versus inconsistências cadastrais pendentes.

### 7.9 Exportação em Planilha Universal (CSV) e Impressão Limpa em PDF
- **Exportar Planilha (CSV):** No canto superior direito do módulo, clique em **Exportar Dados (CSV)**. O sistema compila os dados da aba ativa respeitando estritamente os filtros aplicados e gera um arquivo CSV universal, compatível com **Microsoft Excel**, **Google Planilhas** e **LibreOffice Calc**.
- **Imprimir Relatório / Salvar em PDF:** Clique no botão **Imprimir** (ou `Ctrl + P`). O sistema formata um documento limpo em folha A4 com cabeçalho institucional, dados do estabelecimento, período filtrado, data/hora de emissão e paginação adequada, pronto para reuniões de colegiado ou prestação de contas à Secretaria Municipal de Saúde.

---

## 8. Cadastros, Parâmetros e Integração e-SUS APS PEC

### 8.1 Painel Inicial Operacional (Dashboard)
Acessível em `/dashboard`, exibe o panorama executivo da unidade em tempo real com contagem de pacientes ativos, sessões do dia, absenteísmo e produção ambulatorial do mês.

### 8.2 Dados do Estabelecimento e Logotipo Oficial
Em **Configurações** → **Estabelecimento**:
- Cadastre e consulte os dados oficiais da unidade de saúde: Razão Social, Nome Fantasia, Código CNES de 7 dígitos (`0795690` — Centro Especializado em Reabilitação Jailson Barbosa Lins), Município IBGE padrão (`2709152` — Teotônio Vilela/AL) e dados de contato.
- **Padrão de CNES Oficial (`0795690`):** O CNES do estabelecimento é mantido como string de 7 caracteres com preservação rigorosa do zero à esquerda, sendo utilizado na identificação visual, cabeçalhos, Central de Ajuda e exportações BPA-i.
- **Código IBGE do Município Sede (`2709152`) vs. Exportação BPA-i (`270915`):**
  - Nos cadastros do sistema (estabelecimento, pacientes e unidades de saúde), o código IBGE padrão adota o formato oficial completo de 7 dígitos com dígito verificador módulo 10 (`2709152` para Teotônio Vilela).
  - No layout de exportação magnética do **BPA-i (SIA/SUS)**, a especificação técnica do DATASUS exige exatamente 6 posições numéricas. O sistema realiza a conversão automática dos 7 dígitos para as 6 posições (`270915`), garantindo conformidade sem intervenção manual.
  - **Município de Residência dos Pacientes:** O código padrão `2709152` é sugerido por conveniência operacional, mas o sistema aceita livremente o cadastro e a validação de códigos IBGE de 6 ou 7 dígitos de qualquer município do Brasil (ex.: Maceió `2704302`, Arapiraca `2700300`), assegurando que pacientes oriundos de outros municípios tenham sua procedência preservada.
- Faça o upload do **Logotipo Oficial** da unidade ou da prefeitura. O logotipo é exibido automaticamente na tela de entrada, nos cabeçalhos de impressão de prontuário, no painel de TV e nos relatórios gerenciais.

### 8.3 Cadastro e Manutenção de Pacientes com Paginação Ágil
Em **Configurações** → **Pacientes**:
- Navegue com velocidade mesmo em bases volumosas de dezenas de milhares de pacientes graças à **paginação configurável** (15, 25, 50 ou 100 registros por página).
- Localize cidadãos por Nome, CPF, Cartão SUS ou filtre por Modalidade de Reabilitação vinculada.
- Ao cadastrar um novo paciente, informe Nome Completo, Data de Nascimento, Sexo, Nome da Mãe, CPF, CNS de 15 dígitos, Código IBGE da cidade, endereço e as modalidades de deficiência atendidas.

### 8.4 Salas e Espaços Terapêuticos
Em **Configurações** → **Salas & Espaços**:
- Cadastre e gerencie as salas da unidade: Consultórios de Fonoaudiologia e Psicologia, Sala de Integração Sensorial, Sala de AVD, Ginásios de Fisioterapia Adulto e Pediátrico e Anexos.
- Salas inativadas deixam de aparecer para novos agendamentos, preservando o histórico passado.

### 8.5 Gestão Unificada de Profissões e CBOs
Em **Configurações** → **Profissões e CBOs**:
- **Catálogo Oficial Unificado de Profissões e CBOs:** Gerenciamento simplificado de ocupações oficiais reconhecidas pelo Ministério do Trabalho e Emprego (MTE) e Ministério da Saúde/SUS.
  - O código CBO contém rigorosamente **6 dígitos numéricos**, impedindo duplicações.
  - Cada registro possui metadados de auditoria e verificação oficial (`fonte_referencia`, `versao_referencia` e data de verificação).
  - Inclui por padrão o CBO oficial `251545` (Neuropsicólogo).
  - Ações disponíveis: cadastrar nova profissão, editar, consultar quantidade de profissionais vinculados e inativar/ativar.
- **Novo Fluxo de Cadastro de Profissionais:**
  - O formulário de colaboradores foi simplificado: os campos separados de CBO e Especialidade foram substituídos por um único campo **Profissão / CBO**.
  - O seletor oferece pesquisa rápida em tempo real por código numérico ou título oficial, exibindo o selo de validação do MTE.
  - Não é mais exigido o cadastro separado de especialidades clínicas para novos profissionais.
- **Preservação de Dados Históricos:** Especialidades assistenciais anteriormente cadastradas, relatórios gerenciais e dados de atendimentos históricos permanecem 100% preservados no banco de dados.
- **Comandos Artisan para Produção:**
  - `php artisan cer:recuperar-cbos-legados`: Concilia com segurança e idempotência os CBOs dos profissionais já cadastrados na base, com suporte a `--dry-run`.
  - `php artisan cer:importar-cbos`: Importa o catálogo oficial completo CBO 2002 do MTE (2.614 ocupações válidas) em lotes otimizados de 250 registros.
- **Proteção Estrita do Faturamento BPA-i:** O cadastro ou importação de uma ocupação CBO não autoriza automaticamente procedimentos para faturamento. O sistema mantém rigorosamente a matriz oficial de compatibilidade `CBO x SIGTAP`.
- **Bloqueio de Exclusão:** Profissões e CBOs com profissionais vinculados têm exclusão física bloqueada, garantindo a integridade dos registros.

### 8.6 Cadastro da Rede de Atenção Primária / UBS
Em **Configurações** → **Unidades de Saúde (UBS)**:
- Cadastre e mantenha as Unidades Básicas de Saúde (UBS), Centros de Saúde e Unidades de Saúde da Família (USF) que referenciam usuários para o CER.
- Campos estruturados: Nome Oficial, Código CNES de 7 dígitos (opcional e único), Município IBGE (6 dígitos), Bairro, Endereço completo, Telefone e Origem do cadastro (`MANUAL` ou `ESUS`).
- **Associação de Pacientes:** No cadastro e edição de pacientes, a recepção pode selecionar diretamente a UBS a partir da rede cadastrada, sincronizando o campo legado de referência.
- **Proteção de Integridade:** UBSs com pacientes vinculados têm exclusão física bloqueada.

### 8.7 Integração e Sincronização com o e-SUS APS PEC
O CER Digital conecta-se via rede segura ao banco de dados PostgreSQL do **e-SUS Atenção Primária (PEC)** municipal:
- **Finalidade:** Importa os cidadãos já cadastrados pelas Unidades Básicas de Saúde (UBS) e equipes de Saúde da Família (eSF), vinculando automaticamente a UBS de origem e o Agente Comunitário de Saúde (ACS) responsável pelo território de cada família.
- **Sincronização pela Interface:** Acesse **Configurações** → **Integração e-SUS PEC** e clique em **Sincronizar Pacientes e Vínculos Agora**.
- **Sincronização Automatizada via Terminal:** A equipe de TI pode agendar a sincronização incremental periódica no servidor através do comando:
  ```bash
  php artisan esus:sincronizar-pec
  ```

### 8.8 Trilha de Auditoria e Segurança (LGPD)
Em **Configurações** → **Auditoria LGPD**:
- O sistema registra de forma perene e imutável todas as ações sensíveis:
  - Consulta a prontuários clínicos (`ACESSO_PEP`);
  - Assinaturas de evolução e termos de adendo com registro de IP (`ASSINATURA_EVOLUCAO`);
  - Cadastros, alterações e exclusões de CBOs, Especialidades e UBSs (`CADASTRO_CBO`, `CADASTRO_ESPECIALIDADE`, `CADASTRO_UBS`);
  - Exportações de lotes de faturamento magnético (`EXPORTACAO_BPA`).
- Garante total conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018) para auditorias do Ministério Público, Conselhos Tutelares e Vigilância em Saúde.

---

## 9. Central de Ajuda & Linha do Tempo SemVer

### 9.1 Modal Pós-Login de Novidades da Versão
A cada nova versão publicada no CER Digital, os usuários recebem automaticamente um modal pós-login com as novidades, melhorias e correções da versão, explicadas em **linguagem simples e acessível**.
- O sistema grava individualmente no banco de dados quando cada usuário visualiza e fecha o modal (`ultima_versao_novidades_vista`), garantindo que a tela não reapareça repetidamente.

### 9.2 Central de Ajuda Navegável e Download do Manual
Acessível a qualquer momento em `/ajuda` no menu lateral:
- **Aba Novidades de Cada Versão:** Linha do tempo interativa com o histórico completo de evolução do CER Digital em linguagem clara.
- **Aba Manual do Usuário:** Capítulos didáticos organizados por área (Acesso e PIN, Recepção e Agendamento, Terapeutas e PEP, Faturamento SUS, Relatórios Gerenciais, Coordenação, Checklists, FAQ e Glossário).
- **Botão Baixar Manual (.md):** Permite descarregar o arquivo completo do manual atualizado (`Manual-do-Usuario-CER-Digital-v1.21.0.md`).

---

## 10. Checklists Diários Operacionais por Função

### 10.1 Checklist da Recepção
- [ ] **07:45 - Abertura:** Acessar a **Agenda** (modo Dia ou Grade Semanal) e conferir os horários marcados para o turno da manhã.
- [ ] **Acolhimento Imediato:** Ao receber o paciente ou acompanhante, localizar o agendamento e clicar em **Presente** para registrar o horário de chegada.
- [ ] **Chamada de Paciente:** Utilizar o botão de chamada na TV para avisar a chegada na sala de espera.
- [ ] **Novos Encaminhamentos:** Cadastrar novos usuários conferindo Nome da Mãe, Data de Nascimento e os 15 dígitos do Cartão SUS.
- [ ] **Verificação de Faltas (11:30 e 16:30):** Identificar quem não compareceu e registrar **Falta Justificada** ou **Falta Injustificada**.
- [ ] **Alerta de Absenteísmo:** Ao notar alerta de 3 faltas consecutivas ou 5 no mês, repassar o caso para o Serviço Social.

### 10.2 Checklist do Terapeuta / Especialista
- [ ] **Início do Turno:** Abrir a **Agenda** para conferir a lista de pacientes do dia.
- [ ] **Leitura Interdisciplinar no PEP:** Consultar as anotações registradas pelos demais profissionais da equipe antes do início do atendimento.
- [ ] **Chamada pelo Consultório:** Clicar em **Chamar Paciente na TV** para convocar o próximo atendimento na sala de espera.
- [ ] **Registro da Sessão:** Imediatamente após o atendimento, clicar em **Nova Evolução Clínica** e descrever Queixa/Estado Atual, Conduta e Intercorrências.
- [ ] **Assinatura com PIN:** Digitar o PIN de 4 a 6 dígitos para assinar eletronicamente e gerar o faturamento SUS.
- [ ] **Revisão:** Lembrar que pequenas retificações de digitação são permitidas em até 24 horas; após esse prazo, utilizar **Termo de Adendo**.

### 10.3 Checklist do Faturamento SUS
- [ ] **Acompanhamento Semanal:** Acessar **Faturamento BPA-i** e filtrar a competência em andamento.
- [ ] **Auditoria Prévia:** Identificar atendimentos na aba de inconsistências e solicitar os ajustes cadastrais necessários.
- [ ] **Fechamento Mensal (primeiros dias do mês subsequente):** Confirmar que 100% dos atendimentos concluídos estão na condição "Prontos para Envio".
- [ ] **Exportação do Lote:** Clicar em **Exportar Arquivo Magnético BPA-i (.txt)**.
- [ ] **Importação no Datasus:** Importar o arquivo `.txt` no software oficial **BPA Magnético (SIA/SUS)**, emitir o relatório de consistência e arquivar o recibo da remessa.

### 10.4 Checklist da Coordenação e Gestão
- [ ] **Acompanhamento no Painel Inicial:** Monitorar taxa de presença, absenteísmo e volume de atendimentos diários.
- [ ] **Gestão com o Módulo de Relatórios:** Utilizar as 6 áreas de relatórios para avaliar produtividade da equipe, horários de pico, ocupação das salas e cumprimento de metas contratuais com o SUS.
- [ ] **Busca Ativa de Pacientes Críticos:** Acompanhar na aba de Pacientes os usuários com 3+ faltas seguidas e coordenar ações com o Serviço Social.
- [ ] **Parâmetros e Cadastros:** Manter equipe de profissionais, salas e especialidades devidamente atualizadas.
- [ ] **Sincronização e-SUS:** Acompanhar a execução periódica da sincronização de cadastros com a Atenção Primária.
- [ ] **Auditoria e LGPD:** Revisar periodicamente os logs de acesso e zelar pela privacidade e segurança dos dados.

---

## 11. Perguntas Frequentes (FAQ) & Resolução de Dúvidas

### 1. Esqueci meu PIN de assinatura eletrônica. Como devo proceder?
**Resposta:** Acesse **Configurações** → **Minha Conta & PIN**. No bloco do PIN, digite o novo PIN numérico desejado, informe sua senha de login atual e salve. O novo PIN passa a valer imediatamente.

### 2. Posso alterar uma evolução clínica após 24 horas da assinatura?
**Resposta:** Não. Por exigência ética dos Conselhos Federais de Saúde e legislação de prontuário eletrônico do SUS, anotações clínicas com mais de 24 horas tornam-se estritamente imutáveis. Para acrescentar informações ou corrigir dados passados, clique em **Adicionar Termo de Adendo (Observação Retificadora)** na evolução original e assine com seu PIN.

### 3. O que fazer quando o paciente não tem Cartão SUS no momento do agendamento?
**Resposta:** O sistema exibe um alerta claro explicando a pendência. Caso o paciente seja recém-nascido ou acolhimento inicial, o cadastro pode ser aberto pelo atalho fornecido para inserção do CNS provisório ou do responsável, ou solicitada a emissão com prioridade junto à UBS de origem, já que o CNS é mandatório para o faturamento BPA-i.

### 4. Dois terapeutas atenderam a mesma criança juntos. Como registrar?
**Resposta:** No agendamento, selecione o primeiro profissional e inclua o segundo no campo **Co-atendimento**. Cada profissional abrirá o prontuário e registrará sua respectiva evolução assinando com seu próprio PIN. O sistema gerará duas linhas independentes de BPA-i sem duplicar a sessão.

### 5. O módulo de Relatórios pode alterar o status de algum agendamento ou produção?
**Resposta:** Não. O módulo de Relatórios e Indicadores é **estritamente consultivo**. Ele apenas realiza leituras agregadas no banco de dados para visualização, análise gráfica, impressão e exportação em planilha CSV, sem modificar registros.

### 6. Como funciona a chamada de pacientes na Smart TV da sala de espera?
**Resposta:** A Smart TV é aberta no endereço `/painel-chamadas` e conectada mediante pareamento seguro por código de 6 dígitos autorizado no menu de configurações. As chamadas podem ser disparadas pela recepção ou pelos terapeutas dentro dos consultórios, emitindo campainha e voz sintetizada em português.

### 7. Por que a busca de pacientes é instantânea mesmo com dezenas de milhares de cadastros?
**Resposta:** O CER Digital utiliza consultas dinâmicas sob demanda com índices otimizados no banco de dados. Basta digitar ao menos 3 caracteres (Nome, CPF ou Cartão SUS) para o sistema consultar diretamente o servidor e retornar os resultados em milissegundos.

### 8. Posso utilizar o CER Digital em celulares e tablets?
**Resposta:** Sim. O sistema foi desenvolvido com arquitetura responsiva Mobile-First. Em telas de smartphones e tablets, a navegação conta com menu inferior adaptado ao toque, cartões verticais fáceis de ler e formulários confortáveis.

### 9. Como exportar os dados dos relatórios para o Excel?
**Resposta:** No módulo de Relatórios (`/relatorios`), aplique os filtros desejados e clique no botão **Exportar Dados (CSV)**. O arquivo descarregado pode ser aberto diretamente no Microsoft Excel, Google Planilhas ou qualquer software compatível.

### 10. Como imprimir um prontuário para uma junta médica ou determinação judicial?
**Resposta:** Abra o prontuário do paciente no PEP e clique no botão de impressão (ou use `Ctrl + P`). O sistema aplica formatação em folha A4 limpa com cabeçalho oficial do estabelecimento, identificação do cidadão, histórico cronológico de todas as evoluções, carimbos de validação e assinaturas digitais com hash SHA-256.

---

## 12. Glossário de Termos do SUS e do Sistema

- **BPA-i (Boletim de Produção Ambulatorial Individualizado):** Instrumento de registro magnético oficial do SIA/SUS no qual cada procedimento assistencial é identificado individualmente com o CNS do paciente e do profissional executante.
- **CBO (Classificação Brasileira de Ocupações):** Código numérico oficial de 6 dígitos que identifica a ocupação profissional no SUS (ex.: `2236-05` - Fisioterapeuta, `2238-10` - Fonoaudiólogo, `2239-05` - Terapeuta Ocupacional, `2515-10` - Psicólogo).
- **CER (Centro Especializado em Reabilitação):** Estabelecimento ambulatorial de saúde habilitado pelo Ministério da Saúde para atenção especializada a pessoas com deficiência física, intelectual, auditiva e/ou visual.
- **CID-10 (Classificação Internacional de Doenças):** Padrão internacional da Organização Mundial da Saúde para codificação de patologias e condições de saúde.
- **CNES (Cadastro Nacional de Estabelecimentos de Saúde):** Identificador numérico de 7 dígitos da unidade perante o Ministério da Saúde.
- **CNS (Cartão Nacional de Saúde):** Número oficial de 15 dígitos com validação matemática do Ministério da Saúde que identifica o cidadão e o profissional de saúde.
- **e-SUS APS PEC (Prontuário Eletrônico do Cidadão):** Sistema oficial do Ministério da Saúde para informatização das Unidades Básicas de Saúde da Atenção Primária.
- **Hash SHA-256:** Assinatura criptográfica matemática de 64 caracteres que assegura a integridade de um registro eletrônico, comprovando que o texto não foi adulterado após a assinatura.
- **Horário Cativo:** Vaga semanal fixa reservada na grade para pacientes que necessitam de acompanhamento contínuo em reabilitação.
- **Imutabilidade Clínica:** Princípio ético e legal que determina que prontuários eletrônicos assinados não podem ser sobrescritos, modificados ou apagados.
- **LGPD (Lei Geral de Proteção de Dados - Lei nº 13.709/2018):** Legislação federal brasileira que regula a privacidade e o tratamento de dados pessoais e dados sensíveis de saúde.
- **PEP Multi (Prontuário Eletrônico Multiprofissional):** Ambiente digital que reúne o histórico de saúde, diagnósticos e condutas de todas as especialidades terapêuticas da unidade em uma única linha do tempo integrada.
- **PIN (Personal Identification Number):** Senha numérica pessoal utilizada pelo terapeuta para assinar digitalmente suas evoluções no PEP.
- **SIA/SUS (Sistema de Informações Ambulatoriais do SUS):** Sistema de processamento da produção ambulatorial pública federal.
- **SIGTAP (Sistema de Gerenciamento da Tabela de Procedimentos do SUS):** Catálogo oficial do Ministério da Saúde contendo os códigos de 10 dígitos, valores, CBOs autorizados e regras de compatibilidade dos procedimentos ambulatoriais.
- **Termo de Adendo:** Anotação técnica complementar ou retificadora anexada formalmente a uma evolução clínica cujo prazo de 24 horas já foi encerrado.
- **UBS (Unidade Básica de Saúde):** Posto de saúde da Atenção Primária que coordena o cuidado no território de moradia da família.

---

*Manual elaborado pela equipe de desenvolvimento e homologado para a rede de Centros Especializados em Reabilitação (CER Digital — v1.21.2).*

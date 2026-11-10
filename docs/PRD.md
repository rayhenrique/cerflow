# Product Requirements Document (PRD) — CER Digital

## 1. Visão Geral do Produto
O **CER Digital** é uma aplicação corporativa monolítica desenvolvida com **Laravel 13.x**, **Livewire 4.x**, **Alpine.js 3.x** e **Tailwind CSS v4.x**, projetada sob medida para centralizar e digitalizar toda a operação clínica, assistencial e regulatória de Centros Especializados em Reabilitação (CER II, CER III e CER IV) no Sistema Único de Saúde (SUS).

A plataforma unifica a grade de agendamento por salas e terapeutas, recepção e check-in, painel de chamada de pacientes na sala de espera por Smart TV, prontuário eletrônico multiprofissional (PEP) com assinatura eletrônica e trava de 24 horas, módulo de relatórios e indicadores gerenciais, integração com e-SUS APS PEC e geração automatizada do lote magnético de faturamento BPA-i (SIA/SUS).

---

## 2. Atores e Matriz de Permissões (RBAC)

| Perfil | Identificador | Atribuições e Acessos |
| :--- | :--- | :--- |
| **Recepção** | `recepcao` | Visualiza agenda nos modos Dia, Grade e Mês; realiza check-in (presença/falta) com carimbo de chegada; aciona chamadas de TV; cadastra pacientes; visualiza alertas de faltas. **Acesso bloqueado a anotações clínicas e faturamento.** |
| **Terapeuta / Clínico** | `terapeuta` | Visualiza agenda de atendimentos; consulta histórico multiprofissional no PEP; preenche e assina eletronicamente a evolução clínica com seleção do procedimento executado; aciona chamadas de TV pelo consultório. |
| **Faturamento / Auditoria** | `faturamento` | Audita inconsistências cadastrais e regras SIGTAP (CBO, idade, sexo, CID-10); parametriza procedimentos; exporta o arquivo magnético oficial `.txt` do BPA-i para o SIA/SUS. |
| **Coordenação Geral** | `coordenacao` | Acesso irrestrito: módulo de relatórios e indicadores gerenciais, gestão de usuários, salas, profissionais, pacientes, trilha de auditoria LGPD e integração com e-SUS PEC. |

---

## 3. Requisitos Funcionais

### Módulo 1: Cadastros Base & Parâmetros SUS
* **RF1.1 - Cadastro de Pacientes:** CNS (15 dígitos com validação Módulo 11 do Ministério da Saúde), CPF, RG, Nome, Data de Nascimento, Sexo, Nome da Mãe, Nome do Responsável, Município de Residência (Código IBGE de 6 dígitos), Endereço, Bairro, Contato/WhatsApp do responsável, UBS de referência, Nome do ACS, CID-10 principal e secundário, Modalidades de Reabilitação (Física, Intelectual, Auditiva, Visual).
* **RF1.2 - Cadastro de Profissionais:** Vínculo com usuário (`users`), Nome, CPF, CNS, Registro do Conselho de Classe (CREFITO, CRP, CRFa, etc.), CBO principal de atuação e credencial de assinatura eletrônica (PIN hash).
* **RF1.3 - Cadastro de Salas e Espaços:** Identificação e tipo de espaço (Consultórios, Sala Sensorial, Sala AVD, Fisioterapia Pediátrica/Adulto, Anexos).
* **RF1.4 - Tabela Parametrizada SIGTAP/CBO:** Catálogo com os procedimentos de reabilitação faturados pelo CER, vinculando código de 10 dígitos, CBOs autorizados, faixa etária permitida e compatibilidade de sexo.
* **RF1.5 - Dados do Estabelecimento:** Configuração de Razão Social, Nome Fantasia, CNES oficial, Município IBGE e logotipo institucional.

### Módulo 2: Grade de Agendamento & Recepção
* **RF2.1 - Agenda Operacional Flexível:** Alternância entre os modos Dia, Grade Semanal (Segunda a Sexta, slots de 30 minutos das 08h às 12h e 13h às 17h por sala/terapeuta) e Mapa Mensal.
* **RF2.2 - Atendimentos Compartilhados (Co-atendimento):** Capacidade de associar 2 ou mais profissionais ao mesmo agendamento e sala sem disparar conflito de agenda.
* **RF2.3 - Horários Cativos Recorrentes:** Agendamento automatizado de pacientes fixos para múltiplas semanas consecutivas.
* **RF2.4 - Validação Cadastral Imediata:** Avisos claros e automáticos ao selecionar paciente sem Cartão SUS ou com Cartão SUS inválido, com atalho para atualização rápida.
* **RF2.5 - Check-in Rápido com Registro de Chegada:** Alternância de status em 1 clique (`AGENDADO`, `PRESENTE`, `ATENDIDO`, `FALTA_JUSTIFICADA`, `FALTA_INJUSTIFICADA`, `CANCELADO`) com gravação de `chegada_em`.
* **RF2.6 - Alerta Preventivo de Absenteísmo:** Destaque visual caso o paciente atinja 3 faltas consecutivas ou 5 alternadas no mês vigente para busca ativa pelo Serviço Social.

### Módulo 3: Sistema de Chamadas por TV / Painel Audiovisual
* **RF3.1 - Exibição em Smart TV:** Interface dedicada para salas de espera (`/painel-chamadas`) com logotipo oficial, relógio e histórico de chamadas.
* **RF3.2 - Alerta Sonoro e Voz Sintetizada:** Emissão de aviso sonoro harmônico e anúncio por voz (Web Speech API) em português: *"Atenção: [Nome], favor dirigir-se à [Sala]"*.
* **RF3.3 - Pareamento Seguro de Dispositivos:** Autorização de telas mediante código numérico de 6 dígitos gerado na TV e validado pela coordenação.

### Módulo 4: Prontuário Eletrônico do Paciente (PEP Multi)
* **RF4.1 - Linha do Tempo Multiprofissional:** Visualização cronológica integrada de todas as evoluções clínicas do usuário, permitindo leitura cruzada entre diferentes especialidades.
* **RF4.2 - Evolução Clínica Estruturada:** Formulário contendo Queixa/Estado Atual, Conduta Terapêutica Realizada e Intercorrências, vinculado ao procedimento SIGTAP executado.
* **RF4.3 - Assinatura Eletrônica e Imutabilidade:** Confirmação de PIN individual, gravação de hash SHA-256 e bloqueio irrestrito de edição após 24 horas.
* **RF4.4 - Termos de Adendo Retificadores:** Registro de notas retificadoras vinculadas à evolução original para ajustes após o bloqueio de 24 horas.
* **RF4.5 - Impressão Oficial de Prontuário:** Geração de documento padronizado em folha A4 com cabeçalho institucional e assinaturas digitais.

### Módulo 5: Produção & Faturamento BPA-i (SIA/SUS)
* **RF5.1 - Geração Automática de Produção:** Ao assinar a evolução, o sistema cria o registro de produção associando Profissional, CBO, Paciente, Procedimento SIGTAP, CID-10 e Data.
* **RF5.2 - Desmembramento de Co-atendimento:** Sessões com múltiplos profissionais geram linhas de faturamento individuais para cada participante.
* **RF5.3 - Pré-Auditoria de Lote:** Relatório preventivo identificando previamente falhas no lote (CNS inválido ou ausente, idade incompatível, CBO desautorizado, sexo incompatível).
* **RF5.4 - Exportação Magnética BPA-i:** Geração de arquivo texto (.txt) posicional em conformidade rigorosa com o layout do DATASUS (Header `#BPA#` + Linhas Tipo `03`).

### Módulo 6: Relatórios e Indicadores Gerenciais (`/relatorios`)
* **RF6.1 - Caráter Exclusivamente Consultivo:** Operações de consulta, agregação e exportação sem efeitos colaterais de escrita.
* **RF6.2 - Filtros Globais Dinâmicos:** Filtro por período (com atalhos rápidos), profissional, especialidade, sala, modalidade e competência.
* **RF6.3 - Áreas Temáticas Especializadas:**
  - *Visão Geral:* Indicadores executivos, taxa de presença/absenteísmo, gráfico diário de atendimentos e distribuição por modalidade.
  - *Atendimentos:* Distribuição por status, motivos de faltas e horários de pico.
  - *Pacientes:* Acompanhamento global e lista prioritária de pacientes críticos (3+ faltas seguidas ou 5+ no mês).
  - *Profissionais:* Ranking de produtividade e taxa de ocupação da agenda.
  - *Salas:* Taxa de ocupação de espaços e análise de turnos.
  - *Produção SUS:* Procedimentos executados, estimativa financeira SIGTAP e inconsistências pendentes.
* **RF6.4 - Exportação e Impressão:** Download de relatórios em formato CSV universal e impressão formatada para folha A4/PDF.

### Módulo 7: Integração com e-SUS APS PEC & Governança
* **RF7.1 - Sincronização com o e-SUS PEC:** Conexão direta com a base de dados PostgreSQL do e-SUS PEC para importação de cidadãos cadastrados e vinculação de UBS e ACS.
* **RF7.2 - Trilha de Auditoria LGPD:** Gravação imutável de logs para acessos ao PEP, assinaturas clínicas e exportações de dados com IP do usuário.
* **RF7.3 - Central de Ajuda & Linha do Tempo:** Modal pós-login persistente por usuário e manual do usuário navegável com download em Markdown.

---

## 4. Requisitos Não-Funcionais

* **RNF1 - Segurança & Sigilo (LGPD):** Criptografia de senhas (Bcrypt/Argon2); PIN com hash; isolamento estrito de permissões por Policies; bloqueio de leitura de anotações clínicas por perfis administrativos/recepção.
* **RNF2 - Trilha de Auditoria:** Gravação imutável de logs para acessos ao PEP, assinaturas clínicas e exportações de dados.
* **RNF3 - Performance:** Renderização da grade diária e listas de pacientes em menos de 500ms utilizando Livewire 4.x com Eager Loading estrito (`with(['paciente', 'profissionais', 'sala'])`) e paginação no banco de dados.
* **RNF4 - Design System Mobile-First:** Interface contemporânea, limpa e responsiva com Tailwind CSS v4.x, dotada de navegação inferior em celulares (Bottom Nav) e cartões verticais legíveis.

---

## 5. Regras de Negócio Críticas

* **RN01 - Anti-Conflito de Grade:** Um paciente ou sala não pode ter dois agendamentos simultâneos, exceto se configurado como Atendimento Compartilhado no mesmo registro.
* **RN02 - Validação Cadastral para Atendimento:** O agendamento valida a presença de CNS e Data de Nascimento, emitindo alertas imediatos e claros sem códigos internos para correção cadastral.
* **RN03 - Trava de 24 Horas:** Passadas 24 horas da assinatura da evolução, a edição é bloqueada por Policy, admitindo exclusivamente termos de adendo retificadores.
* **RN04 - Desmembramento de Produção Compartilhada:** Atendimentos com mais de um profissional geram linhas de BPA-i independentes, associando o CBO e o procedimento específico de cada categoria.
* **RN05 - Integridade de Relatórios:** O módulo de relatórios é estritamente consultivo e deve computar faltas exclusivamente a partir de horários passados, excluindo horários futuros ou cancelados pelo serviço.

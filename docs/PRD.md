# Product Requirements Document (PRD) - CER Flow

## 1. Visão Geral do Produto
O **CER Flow** é uma aplicação monolítica desenvolvida com **Laravel 13.x**, **Livewire 4.x**, **Alpine.js 3.x** e **Tailwind CSS v4.3**, projetada para centralizar e substituir três ferramentas legadas em um Centro Especializado em Reabilitação (CER): planilha de cronograma semanal em Excel, formulários do Google Forms de produção e prontuários clínicos em papel físico.

A plataforma unifica a grade de agendamento por salas e terapeutas, recepção e check-in, prontuário eletrônico multiprofissional (PEP) com assinatura eletrônica e geração automatizada do lote magnético de faturamento BPA-i (SIA/SUS).

---

## 2. Atores e Matriz de Permissões (RBAC)

| Perfil | Identificador | Atribuições e Acessos |
| :--- | :--- | :--- |
| **Recepção** | `recepcao` | Visualiza a grade diária, realiza check-in (presença/falta), cadastra dados demográficos de usuários e gerencia absenteísmo. Sem acesso a anotações clínicas. |
| **Terapeuta / Especialista** | `terapeuta` | Visualiza a agenda pessoal e salas compartilhadas, acessa a linha do tempo clínica, preenche e assina eletronicamente a evolução clínica com seleção do procedimento executado. |
| **Faturamento / Auditoria** | `faturamento` | Audita inconsistências cadastrais de pacientes e CBOs, parametriza de-para SIGTAP/CBO e exporta o arquivo magnético oficial `.txt` do BPA-i. |
| **Coordenação Geral** | `coordenacao` | Acesso irrestrito a configurações, parametrização de salas/espaços, gestão de usuários, relatórios de produtividade e trilha de auditoria. |

---

## 3. Requisitos Funcionais

### Módulo 1: Cadastros Base & Parâmetros SUS
* **RF1.1 - Cadastro de Pacientes:** CNS (15 dígitos com validação de algoritmo de Luhn/SUS), CPF, Nome completo, Data de Nascimento, Sexo, Nome da Mãe, Município de Residência (Código IBGE de 6 dígitos), Endereço, Bairro, Contato/WhatsApp do responsável, CID-10 principal e secundário, Modalidades de Reabilitação vinculadas (Física, Intelectual, Auditiva, Visual).
* **RF1.2 - Cadastro de Profissionais:** Vínculo com usuário do sistema (`users`), Nome, CPF, CNS, Registro do Conselho de Classe (CREFITO, CRP, CRFa, etc.), CBO principal de atuação e credencial de assinatura eletrônica (PIN hash).
* **RF1.3 - Cadastro de Salas e Espaços:** Identificação e tipo de espaço (Consultórios, Sala Sensorial, Sala AVD, Fisioterapia Pediátrica/Adulto, Anexos).
* **RF1.4 - Tabela Parametrizada SIGTAP/CBO:** Carga via Seeder com os procedimentos de reabilitação faturados pelo CER, vinculando código de 10 dígitos, CBOs autorizados, faixa etária permitida e compatibilidade de sexo.

### Módulo 2: Grade de Agendamento & Recepção
* **RF2.1 - Grade Semanal Recorrente:** Espelhamento visual da grade semanal (Segunda a Sexta) em blocos de 30 minutos (08h às 12h e 13h às 17h) por sala e terapeuta.
* **RF2.2 - Atendimentos Compartilhados:** Capacidade de associar 2 ou mais profissionais ao mesmo agendamento e sala sem disparar conflito de agenda.
* **RF2.3 - Horários de Trabalho Interno:** Suporte a slots de trabalho interno e discussão de caso ("EVOLUÇÃO").
* **RF2.4 - Painel da Recepção (Check-in):** Alternância de status de atendimento em 1 clique (`AGENDADO`, `PRESENTE`, `ATENDIDO`, `FALTA_JUSTIFICADA`, `FALTA_INJUSTIFICADA`, `CANCELADO`).
* **RF2.5 - Alerta de Absenteísmo:** Destaque visual caso o paciente atinja 3 faltas consecutivas ou 5 alternadas no mês vigente.

### Módulo 3: Prontuário Eletrônico do Paciente (PEP Multi)
* **RF3.1 - Linha do Tempo Multiprofissional:** Visualização cronológica integrada de todas as evoluções clínicas do usuário, permitindo leitura cruzada entre diferentes especialidades (Fonoaudiologia, Fisioterapia, Psicologia, Terapia Ocupacional).
* **RF3.2 - Evolução Clínica Estruturada:** Formulário contendo Queixa/Estado Atual, Conduta Terapêutica Realizada e Intercorrências.
* **RF3.3 - Atendimento Compartilhado no PEP:** Geração de um encontro conjunto onde cada profissional envolvido assina sua respectiva nota técnica individualmente.
* **RF3.4 - Assinatura Eletrônica e Imutabilidade:** Confirmação de PIN individual, gravação de hash SHA-256 e bloqueio irrestrito de edição após 24 horas.
* **RF3.5 - Termos de Adendo:** Registro de notas retificadoras vinculadas à evolução original para ajustes após o bloqueio de 24 horas.

### Módulo 4: Produção & Faturamento BPA-i (SIA/SUS)
* **RF4.1 - Geração Automática de Produção:** Ao assinar a evolução, o sistema cria o registro de produção associando: Profissional + CBO + Paciente + Procedimento SIGTAP + CID-10 + Data do Atendimento.
* **RF4.2 - Pré-Auditoria de Lote:** Relatório preventivo identificando previamente falhas no lote (CNS inválido ou ausente, idade incompatível, CBO desautorizado para o procedimento).
* **RF4.3 - Exportação Magnética BPA-i:** Geração de arquivo texto (.txt) posicional em conformidade rigorosa com o layout do DATASUS (Header `#BPA#` + Linhas de Produção tipo `03`).

---

## 4. Requisitos Não-Funcionais

* **RNF1 - Segurança & Sigilo (LGPD):** Criptografia de dados sensíveis; isolamento estrito de permissões por Policies; bloqueio de leitura de anotações clínicas por perfis administrativos/recepção.
* **RNF2 - Trilha de Auditoria:** Gravação imutável de logs para acessos ao PEP, assinaturas clínicas e exportações de dados.
* **RNF3 - Performance:** Renderização da grade diária em menos de 500ms utilizando Livewire 4.x com Eager Loading estrito (`with(['paciente', 'profissionais', 'sala'])`).
* **RNF4 - Design System Moderno:** Interface limpa e minimalista com Tailwind CSS v4.3, focada em alta legibilidade e densidade de informação clínica (estilo Linear/Filament).

---

## 5. Regras de Negócio Críticas

* **RN01 - Anti-Conflito de Grade:** Um paciente não pode ter dois agendamentos simultâneos, exceto se configurado como Atendimento Compartilhado no mesmo registro.
* **RN02 - Validação Cadastral para Atendimento:** O paciente só pode ser atendido se possuir CNS válido e data de nascimento preenchida.
* **RN03 - Trava de 24 Horas:** Passadas 24 horas da assinatura da evolução, a edição é bloqueada por Policy, admitindo exclusivamente termos de adendo retificadores.
* **RN04 - Desmembramento de Produção Compartilhada:** Atendimentos com mais de um profissional geram linhas de BPA-i independentes, associando o CBO e o procedimento específico de cada categoria.
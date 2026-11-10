# Declaração de Escopo do Produto — CER Digital

## 🎯 1. Objetivos da Plataforma
Substituir de forma definitiva o uso de processos manuais e ferramentas legadas fragmentadas (Planilhas Excel de Cronograma, Formulários do Google Forms de Produção e Fichas de Papel de Evolução) por um sistema integrado corporativo em **Laravel 13 + Livewire 4 + Tailwind CSS 4**, assegurando integridade e imutabilidade no prontuário multiprofissional, agilidade na recepção com painel audiovisual de TV, controle de absenteísmo, inteligência em relatórios gerenciais e automação completa da remessa do faturamento BPA-i para o DATASUS (SIA/SUS).

---

## ✅ 2. Funcionalidades Entregues e Homologadas (Core & Evoluções)

### Landing Page & Identidade Visual
* Landing page institucional pública moderna apresentando as 4 linhas de reabilitação e o fluxo de cuidado humanizado do SUS.
* Nova tela de login corporativo, elegante, com alternância de visualização de senha e proteção contra ataques.
* Arquitetura Mobile-First com menu de navegação inferior (Bottom Nav) e cartões verticais em substituição a tabelas largas.

### Agendamento e Grade Operacional Unificada
* Visualização flexível nos modos Dia, Grade Semanal (slots de 30 min por sala/terapeuta) e Mapa Mensal.
* Suporte nativo a grade recorrente fixa (horário cativo do usuário para múltiplas semanas).
* Suporte a agendamentos compartilhados (2 ou mais profissionais com o mesmo usuário no mesmo espaço sem choque de agenda).
* Bloqueio visual para horários de trabalho interno ("EVOLUÇÃO").
* Validação cadastral imediata: alertas visuais claros quando o paciente selecionado não possui Cartão SUS (CNS) ou quando o CNS é inválido, com atalho para atualização rápida.

### Recepção, Check-in & Chamada por TV
* Check-in de pacientes em 1 clique (`AGENDADO`, `PRESENTE`, `ATENDIDO`, `FALTA_JUSTIFICADA`, `FALTA_INJUSTIFICADA`, `CANCELADO`) com registro automático do horário de chegada (`chegada_em`).
* Sinalização visual preventiva de absenteísmo acumulado (3 faltas seguidas ou 5 alternadas no mês) para busca ativa pelo Serviço Social.
* Sistema de chamadas audiovisuais em Smart TV (`/painel-chamadas`) com campainha harmônica e sintetizador de voz (Web Speech API).
* Pareamento seguro de TVs via código numérico de 6 dígitos.

### Prontuário Eletrônico do Paciente (PEP Multi)
* Histórico clínico multidisciplinar unificado na ficha do usuário (Fisioterapia, TO, Fonoaudiologia, Psicologia, etc.).
* Registro de evolução clínica estruturada vinculando o procedimento SIGTAP executado.
* Assinatura eletrônica interna via confirmação de PIN pessoal com hash criptográfico SHA-256.
* Trava legal de imutabilidade após 24 horas do registro, com suporte a termos de adendo retificadores.
* Impressão formatada em folha A4 com carimbo de autenticidade.

### Faturamento e BPA-i (SIA/SUS)
* Carga via Seeder dos procedimentos de reabilitação e CBOs faturados no CER.
* Vínculo automático de produção clínica com o procedimento SUS ao assinar o atendimento.
* Desmembramento automático de sessões compartilhadas em linhas de BPA-i independentes.
* Painel de pré-auditoria de inconsistências cadastrais e de regras de CBO x Procedimento x Idade x Sexo x CID-10 x CNS.
* Exportação direta do arquivo magnético posicional `.txt` do BPA-i para importação no SIA/SUS (Header `#BPA#` + linhas `03`).

### Módulo de Relatórios e Indicadores Gerenciais (`/relatorios`)
* Módulo analítico exclusivamente consultivo (sem efeitos colaterais em agendamentos ou prontuários).
* Filtros globais dinâmicos por período (Hoje, 7 dias, Mês Atual, Mês Anterior, Ano), Profissional, Especialidade, Sala e Competência BPA-i.
* 6 áreas temáticas: Visão Geral (cards executivos e evolução diária), Atendimentos (status e horários de pico), Pacientes Críticos (3+ faltas consecutivas ou 5+ no mês), Profissionais (produtividade e ocupação), Salas (taxa de ocupação de espaços) e Produção SUS/BPA-i (estimativa financeira SIGTAP e inconsistências).
* Exportação em formato CSV universal (Excel/Google Planilhas) e impressão limpa institucional em PDF/A4.

### Integração e-SUS APS PEC & Governança
* Sincronização direta com a base de dados PostgreSQL do e-SUS APS PEC municipal com vinculação de UBS e ACS.
* Trilha de auditoria imutável (`logs_auditoria`) registrando acessos ao PEP, assinaturas clínicas e exportações de lotes com IP do usuário.
* Central de Ajuda & Sobre com linha do tempo de versões SemVer e download do Manual do Usuário em Markdown.

---

## 🚫 3. Fora de Escopo / Roadmap Futuro

* Integração via Webservice direto ou SOAP com a base federal do DATASUS (o faturamento utiliza o arquivo magnético oficial homologado).
* Assinatura digital via certificados ICP-Brasil físicos (tokens A1/A3).
* Gestão de estoque e dispensação de Órteses, Próteses e Materiais Especiais (OPM).
* Arquitetura SaaS Multi-tenant (cada município/unidade opera em instância dedicada).

---

## 📊 4. Critérios e Métricas de Sucesso

1. **Eliminação de 100% do Papel:** Todos os atendimentos, evoluções e prontuários são exclusivamente eletrônicos e auditáveis.
2. **Tempo de Produção Zero:** Nenhum terapeuta ou recepcionista redigita fichas de produção no final do mês.
3. **Consistência do Lote no DATASUS:** O arquivo `.txt` gerado é importado no programa oficial do BPA Magnético com 100% de consistência.
4. **Zero Conflito de Agendas:** Bloqueio matemático de duplicidade inadvertida de agendamento para o mesmo usuário ou espaço físico.
5. **Decisões Baseadas em Indicadores:** Coordenação acompanha em tempo real a frequência, o absenteísmo e a sustentabilidade financeira da unidade.

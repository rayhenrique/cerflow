# Declaração de Escopo do MVP - CER Flow

## 🎯 1. Objetivos do MVP
Substituir de forma imediata o uso das três ferramentas legadas (Planilha Excel de Cronograma, Google Forms de Produção e Fichas de Papel de Evolução) por um sistema único em **Laravel 13 + Livewire 4**, garantindo integridade no prontuário multiprofissional e automatizando a remessa do faturamento BPA-i para o DATASUS.

---

## ✅ 2. O Que Está Aprovado (Core Features)

### Agendamento e Grade
* Visualização da grade semanal por dia e turno com slots de 30 minutos por sala e terapeuta.
* Suporte nativo a grade recorrente fixa (horário cativo do usuário).
* Suporte a agendamentos compartilhados (2 ou mais profissionais com o mesmo usuário no mesmo espaço).
* Bloqueio visual para horários de trabalho interno ("EVOLUÇÃO").

### Recepção
* Check-in de pacientes em 1 clique (`PRESENTE`, `FALTA`, `ATENDIDO`).
* Sinalização visual de absenteísmo acumulado.

### Prontuário Eletrônico do Paciente (PEP)
* Histórico clínico multidisciplinar unificado na ficha do usuário.
* Registro de evolução clínica estruturada.
* Assinatura eletrônica interna via confirmação de PIN do profissional com hash SHA-256.
* Trava legal de imutabilidade após 24 horas do registro, com suporte a termos de adendo retificadores.

### Faturamento e BPA-i
* Carga via Seeder dos procedimentos de reabilitação e CBOs faturados no CER.
* Vínculo automático de produção clínica com o procedimento SUS ao assinar o atendimento.
* Painel de pré-auditoria de inconsistências cadastrais e de regras de CBO.
* Exportação direta do arquivo magnético posicional `.txt` do BPA-i para importação no SIA/SUS.

---

## 🚫 3. O Que Foi Postergado / Fora de Escopo

* Integração via Webservice direto ou SOAP com a base federal do DATASUS.
* Assinatura digital via certificados ICP-Brasil (tokens físicos A1/A3).
* Módulo de fila de espera dinâmica com disparos automáticos via WhatsApp API.
* Módulo de faturamento para BPA-C (Consolidado) e APAC.
* Gestão de estoque e dispensação de Órteses, Próteses e Materiais Especiais (OPM).
* Arquitetura SaaS Multi-tenant (instância única dedicada ao município).

---

## 📊 4. Critérios e Métricas de Sucesso do MVP

1. **Eliminação de 100% do Papel:** Nenhum atendimento do CER demandará anotação física após 15 dias de operação.
2. **Tempo de Produção Zero:** O profissional não gastará tempo redigitando dados em formulários externos após a evolução clínica.
3. **Validação do Lote no DATASUS:** O arquivo `.txt` gerado pelo sistema deve ser importado com sucesso no programa oficial do BPA Magnético do Ministério da Saúde sem rejeição estrutural de layout.
4. **Zero Conflito de Agendas:** Eliminação total de duplicidade inadvertida de agendamento para o mesmo usuário ou espaço físico.
# Diretrizes de Engenharia e Contexto de IA - CER Flow

## Stack Tecnológica
- PHP 8.3
- Framework: Laravel 13.x (última versão estável)
- Banco de Dados: MySQL 8.0+
- Frontend Reativo: Laravel Livewire 4.x + Alpine.js 3.x
- Estilização: Tailwind CSS v4.3
- Padrão de Código: PSR-12 estrito

## Regras de Arquitetura e Engenharia de Software
1. NUNCA coloque lógica de negócios ou validações dentro de rotas, closures ou Views Blade.
2. NUNCA execute queries Eloquent diretas dentro de Views Blade. Todas as relações necessárias devem ser carregadas via Eager Loading (`with()`) no Controller ou Componente Livewire para evitar N+1 queries.
3. Controllers devem ser enxutos. Para operações complexas (faturamento BPA-i, trava de prontuário, conflito de agenda), utilize Service Classes dedicadas (ex: `App\Services\BpaiExportService`).
4. Validações devem ser SEMPRE encapsuladas em classes dedicadas de `FormRequest` ou nas regras de validação nativas do Livewire 4 (`rules()`).
5. Todas as alterações em dados clínicos (PEP) devem respeitar estritamente a autorização via Laravel Policies (`$this->authorize()`).
6. Chaves primárias usam `bigIncrements` (ou `$table->id()`), datas usam objetos Carbon imutáveis, e foreign keys são declaradas explicitamente com regras de integridade (`restrictOnDelete` ou `cascadeOnDelete`).

## Regras de Domínio SUS / Saúde
1. O CNS (Cartão Nacional de Saúde) deve conter exatamente 15 dígitos numéricos e respeitar o algoritmo Módulo 11 do Ministério da Saúde.
2. O código do procedimento SIGTAP deve conter exatamente 10 dígitos numéricos.
3. A evolução clínica torna-se IMUTÁVEL após 24 horas da assinatura eletrônica. Nunca crie rotas de update direto que ignorem o campo `assinado_em`. Retificações posteriores devem utilizar exclusivamente o modelo `EvolucaoAdendo`.
4. Atendimentos compartilhados geram registros separados na tabela `producoes_bpai`, associando o CBO e o procedimento de cada profissional participante.
5. O módulo de Relatórios e Indicadores é ESTRITAMENTE CONSULTIVO: nenhuma rotina de relatório pode alterar o status de agendamentos, evoluções ou produções. Horários futuros e cancelados não devem ser computados como faltas.
6. A interface deve manter fidelidade ao design system Mobile-First do CER Digital, assegurando usabilidade tátil, cards responsivos e ausência de rolagem horizontal desnecessária.

## Gestão de Tarefas
- Sempre consulte o arquivo `TASKS.md` antes de iniciar uma nova implementação.
- Ao concluir com sucesso uma etapa ou funcionalidade descrita no `TASKS.md`, atualize o arquivo marcando a respectiva caixa como concluída `[x]`.

## Regra Permanente para Notas de Atualização e Tela de Novidades

Sempre que uma nova versão do CER Digital for preparada, com commit e push autorizados, atualizar as notas da versão utilizando **linguagem simples, clara e acessível ao usuário comum**.

### Regras obrigatórias:
1. **Foco no usuário:** Descrever o que mudou no sistema destacando os benefícios diretos para recepcionistas, profissionais de saúde, coordenação e faturamento.
2. **Termos técnicos proibidos:** NUNCA utilizar termos de desenvolvimento, arquitetura ou infraestrutura nas notas voltadas ao usuário final, tais como:
   - Refatoração
   - Migration
   - Query
   - Endpoint
   - Livewire
   - Middleware
   - Cache
   - Backend
   - Otimização de SQL
   - N+1, drivers, Redis, polling, Eloquent, keyset, etc.
3. **Substituição de expressões técnicas:** Substituir jargões por explicações claras do resultado prático e do benefício no dia a dia.
4. **Categorias simples:** Organizar os destaques em categorias claras e objetivas:
   - **Novidades**
   - **Melhorias**
   - **Correções**
5. **Estilo:** Utilizar frases curtas, diretas e objetivas, sem exageros publicitários.
6. **Veracidade:** Não mencionar funcionalidades que não tenham sido efetivamente implementadas e verificadas por testes automatizados.
7. **Separação de registros:** Manter o versionamento e registros técnicos internos necessários (como o `CHANGELOG.md` para desenvolvedores), mas apresentar ao usuário final (em `VersionService`, Modal de Novidades e Central de Ajuda) apenas textos acessíveis e compreensíveis.
8. **Atualização nos arquivos corretos:** Atualizar o arquivo `VERSION`, o serviço `VersionService` (`historicoVersoes()`) e as telas de "Novidades" e "Ajuda & Sobre" conforme a estrutura existente no projeto.
9. **Controle de publicação:** NÃO executar commit ou push sem autorização expressa. Esta regra deve ser aplicada quando a publicação for autorizada.

### Exemplos práticos de linguagem:
- **Evitar:** "Refatorado componente Livewire com sincronização de propriedades de estado e correção da query de agendamentos."  
  **Preferir:** "Corrigimos a navegação da agenda semanal. Agora você pode alternar entre os dias e visualizar os agendamentos na data correta."
- **Evitar:** "Implementado eager loading e eliminadas consultas N+1."  
  **Preferir:** "Melhoramos a velocidade de carregamento da agenda e da lista de pacientes."
- **Evitar:** "Implementado relacionamento entre agendamentos e evoluções clínicas por profissional."  
  **Preferir:** "Os profissionais agora podem visualizar os pacientes que chegaram à recepção e abrir seus prontuários com mais facilidade."

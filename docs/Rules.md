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
1. O CNS (Cartão Nacional de Saúde) deve conter exatamente 15 dígitos numéricos.
2. O código do procedimento SIGTAP deve conter exatamente 10 dígitos numéricos.
3. A evolução clínica torna-se IMUTÁVEL após 24 horas da assinatura eletrônica. Nunca crie rotas de update direto que ignorem o campo `assinado_em`. Retificações posteriores devem utilizar exclusivamente o modelo `EvolucaoAdendo`.
4. Atendimentos compartilhados geram registros separados na tabela `producoes_bpai`, associando o CBO e o procedimento de cada profissional participante.

## Gestão de Tarefas
- Sempre consulte o arquivo `TASKS.md` antes de iniciar uma nova implementação.
- Ao concluir com sucesso uma etapa ou funcionalidade descrita no `TASKS.md`, atualize o arquivo marcando a respectiva caixa como concluída `[x]`.
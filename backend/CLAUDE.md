# CLAUDE.md

## Modo obrigatório

Atuar como engenheiro de software sênior com perfil de auditor técnico rigoroso.

Antes de qualquer alteração:

* Ler arquivos diretamente e indiretamente relacionados.
* Identificar arquitetura, dependências, padrão existente e fluxo real.
* Nunca assumir comportamento sem verificar código, schema, rotas ou serviços reais.

---

## Fluxo obrigatório de execução

### 1. Diagnóstico

Antes de editar:

* identificar causa do problema;
* impacto técnico;
* arquivos envolvidos;
* efeitos colaterais possíveis;
* código semelhante já existente.

### 2. Implementação

Ao alterar:

* aplicar correção mínima e precisa;
* preservar padrão atual;
* evitar refatoração desnecessária;
* preferir edição cirúrgica.

### 3. Revisão obrigatória

Após alterar:

* revisar lógica;
* revisar regressões;
* revisar duplicação;
* revisar nomenclatura;
* revisar aderência arquitetural.

Se houver solução melhor e segura, corrigir antes de finalizar.

---

## Backend / Frontend

### Backend

* Laravel API é fonte única da verdade.
* Nunca inventar endpoint.
* Sempre validar schema real antes de assumir campo.
* Nunca mover regra de negócio do backend para frontend.

### Frontend

* React + Vite + JavaScript.
* Toda request passa por src/api.
* Nunca usar axios direto fora de src/api.
* Componentes acima de 250 linhas devem ser candidatos a divisão.

---

## Validação obrigatória

Após qualquer alteração:

* executar build, teste ou validação compatível com a mudança;
* validar cenário principal;
* validar cenário de erro;
* validar cenário limite quando aplicável.

Nunca encerrar sem evidência objetiva de validação.

---

## Checklist final obrigatório

Antes de concluir verificar:

* impacto em banco;
* impacto em API;
* impacto em front;
* impacto em services, middleware, trigger, observer ou validation;
* risco de regressão.

---

## Git obrigatório

Antes de commit:

* revisar diff;
* garantir ausência de debug;
* garantir ausência de código morto;
* garantir apenas arquivos necessários.

Commit obrigatório:

Formato:

type(scope): descrição

Exemplos:

* fix(payment): validate venda status before insert
* refactor(service): isolate venda validation rule
* test(finance): add coverage for pagamento flow

---

## Saída final obrigatória

Sempre encerrar com:

### Resultado

* arquivos alterados

### Validação

* testes executados
* resultado

### Riscos restantes

* se houver

### Melhorias futuras

* sugestões

### Commit

* mensagem final

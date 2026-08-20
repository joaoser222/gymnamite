# Próximas Etapas — MCP + Chat (pós-IMPLEMENTATION_STEPS)

## Visão Geral

O plano base (`IMPLEMENTATION_STEPS.md`, Etapas 0–5 + Prompts) está concluído e
commitado: **38 MCP Tools** (escrita), **22 MCP Resources** (leitura) e **5 MCP
Prompts** (guiados), todos com gates de permissão via `shouldRegister()`.

Este documento cobre as etapas seguintes, que **conectam o Chat (LLM) ao
servidor MCP** e agregam robustez de produção. Cada fase é independente e pode
ser priorizada separadamente.

---

## Fase A — Chat acionando Tools de escrita (MCP Tools)

**Objetivo:** hoje `ChatToolSchemaProvider::readOnlyResourcesForCurrentUser()`
só expõe os 22 Resources (leitura). Os 38 Tools de escrita já existem no
`GymnamiteServer` mas não chegam ao LLM. Esta fase fecha o ciclo
"LLM comanda ação".

### Domínios Envolvidos
- MCP (`gymnamite-project-patterns`, `laravel-best-practices`)

### Detalhamento
1. **Backend — `ChatToolSchemaProvider`**
   - Adicionar `writableToolsForCurrentUser()` (ou estender o método atual) que
     lê `$defaults['tools']` do `GymnamiteServer` via `ReflectionClass`, filtra
     por `eligibleForRegistration()` (já faz o gate de permissão do usuário) e
     monta definições OpenAI a partir de `tool->name()` + `tool->schema()`
     (`Illuminate\Contracts\JsonSchema\JsonSchema`).
   - Retornar no `map` também os Tools (ex.: chave `tool_map` com
     `class-string` + params) para o `ChatService` executar.
2. **Backend — `ChatService::ask()`**
   - Ao receber `tool_calls`, distinguir recurso vs. tool:
     - recurso → `executeResource()` (já existe).
     - tool → instanciar a Tool e chamar `handle(new Request($arguments))`.
       A Tool já valida o DTO e executa a Action; tratar `Response::error`.
   - Manter `max_tool_iterations` e, se necessário, exigir confirmação antes de
     escritas destrutivas (`#[IsDestructive]`).
3. **Frontend** — `Chat.vue`: atualizar o texto de "somente visualização" para
   refletir que o assistente agora executa ações permitidas.
4. **Tests** — `tests/Feature/Chat/ChatControllerTest.php`:
   - Usuário com `clients.create` dispara `create-client-tool` → cliente criado
     no banco e confirmado em `assertDatabaseHas`.
   - Usuário sem a permissão NÃO recebe o tool na lista (já coberto pelo padrão
     `eligibleForRegistration`).

### Observações
- Ferramenta de escrita altera dados de verdade: o gate de permissão por
  usuário é a única proteção. Recomenda-se logar cada execução.
- O loop de tool-calls pode disparar várias escritas; `max_tool_iterations`
  limita, mas avaliar confirmação para `cancel`/`delete`.

---

## Fase B — Persistência de histórico de conversa

**Objetivo:** o `ChatController` hoje recebe só a última mensagem; o histórico
não é salvo e cada requisição é stateless. Persistir dá continuidade real.

### Domínios Envolvidos
- Chat / Persistência (`laravel-best-practices`, `gymnamite-project-patterns`)

### Detalhamento
1. **Backend — Migrations**
   - `chat_conversations`: `id`, `user_id`, `title`, `created_at`, `updated_at`.
   - `chat_messages`: `id`, `conversation_id`, `role` (user/assistant),
     `content`, `created_at`.
2. **Backend — Models** `Conversation` + `ChatMessage` com relacionamentos e
   `$fillable`/`casts` (seguir padrão Eloquent do projeto).
3. **Backend — `ChatService`/`ChatController`**
   - `ChatController::message()` aceita `conversation_id` opcional; cria ou
     carrega a conversa, grava a mensagem do usuário e a resposta do assistente.
   - Retorna `conversation_id` no JSON.
4. **Frontend — `Chat.vue`**
   - Enviar/receber `conversation_id`; permitir retomar conversa (listar
     conversas no menu "Início", se desejado).
5. **Tests** — `tests/Feature/Chat/`:
   - Mensagens persistem em `chat_messages` após o POST.
   - `history` enviado pelo cliente é ignorado quando há `conversation_id`
     válido (fonte única de verdade = banco).

### Observações
- Privacidade: o histórico contém dados de negócio; restringir leitura por
  `user_id` (um usuário não lê conversa de outro).

---

## Fase C — Streaming de resposta

**Objetivo:** reduzir a percepção de latência exibindo a resposta conforme o
LLM a gera (o endpoint Zen/MiniMax suporta Server-Sent Events).

### Domínios Envolvidos
- HTTP Client / Frontend (`laravel-best-practices`, `vuetify-development`)

### Detalhamento
1. **Backend — `ChatService`**
   - `ask()` passa a aceitar um callback de stream ou retorna um
     `StreamedResponse`; usa `Http::withToken(...)->withOptions(['stream' => true])`
     e lê os chunks `data:` do SSE.
   - Tool-calls com stream exigem buffer do último chunk com `tool_calls` antes
     de executar o recurso/tool.
2. **Backend — `ChatController`**
   - Retorna `StreamedResponse` com `Content-Type: text/event-stream` quando o
     cliente solicita stream.
3. **Frontend — `Chat.vue`**
   - `send()` usa `fetch` + `response.body.getReader()` para atualizar a bolha
     do assistente em tempo real.
4. **Tests**
   - Difícil testar SSE ponta a ponta; cobrir apenas que o endpoint retorna
     `text/event-stream` e que o `Http::fake` com stream é tratado.

### Observações
- Manter um fallback não-stream (Fase A) para clientes que não suportam SSE.

---

## Fase D — Correção das falhas de web tests (CSRF 419)

**Objetivo:** ~73 testes de `tests/Feature` de formulários web falham com 419
(CSRF) ou erro de validação — pré-existentes e ambientais, não ligados a MCP/chat.

### Domínios Envolvidos
- Testes / Segurança (`laravel-best-practices`)

### Detalhamento
1. Diagnosticar a causa real:
   - `VerifyCsrfToken` em `bootstrap/app.php` vs. ambiente de teste.
   - Testes de formulário web usando `post()`/`submit()` sem token CSRF ou sem
     `Session` configurada.
2. Corrigir no lado dos testes (sem alterar regra de negócio):
   - Usar `withSession` / token CSRF, ou `WithoutMiddleware` apenas onde
     pertinente, ou ajustar `phpunit.xml` (`SESSION_DRIVER`, `APP_KEY`).
3. Garantir `APP_KEY` definido no `phpunit.xml` ou `.env.testing`.
4. **Tests** — rodar `php artisan test --compact` e zerar as falhas legítimas.

### Observações
- Se as falhas forem puramente de ambiente (Sail/container vs. host), documentar
  o setup correto em vez de alterar a aplicação.

---

## Fase E — Hardening de produção

**Objetivo:** deixar o Chat + MCP prontos para uso real.

### Domínios Envolvidos
- Config / Deploy (`saas-php-laravel`, `laravel-best-practices`)

### Detalhamento
1. **Config** — definir `MCP_CHAT_API_KEY` (OpenCode Zen) no `.env` de produção;
   confirmar `MCP_CHAT_BASE_URL`, `MCP_CHAT_MODEL`.
2. **Frontend** — `npm run build` (ou `npm run dev`) para refletir `Chat.vue`
   (texto de "somente visualização" e, se aplicável, Fases B/C).
3. **Smoke test** — registrar o `GymnamiteServer` (/mcp/gymnamite) num cliente
   MCP real e validar `tools/list`, `resources/list`, `prompts/list`.
4. **Tests** — `php artisan test --compact` com o filtro mais amplo antes do
   deploy.

### Observações
- Sem validação contra a API real da Zen (sem chave em dev): os testes usam
  `Http::fake`, como já feito.

---

## Ordem recomendada

| Ordem | Fase | Esforço | Valor |
|-------|------|---------|-------|
| 1 | **A** — Chat aciona Tools de escrita | Alto | Alto (uso real do MCP) |
| 2 | **B** — Histórico persistido | Médio | Médio |
| 3 | **E** — Hardening | Baixo | Alto (viabiliza prod) |
| 4 | **C** — Streaming | Médio | Médio (UX) |
| 5 | **D** — Web tests | Médio | Baixo (qualidade) |

Dependências: B e C podem usar o `map`/loop da Fase A; E é independente e pode
entrar a qualquer momento.

## Riscos gerais
- Fase A expõe escrita real: reforçar gates de permissão e logging.
- Fase C com tool-calls exige cuidado de buffering de SSE.
- Fase D pode ser apenas ambiental — validar antes de mudar código de app.

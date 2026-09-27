# OrderMind — Arquitetura e decisões técnicas

## Estado do documento

Fases 01 a 10 consolidadas em 26/09/2026. Filas, sessão, regras de negócio, matriz de estados e uso gratuito do Gemini foram aprovados pelo usuário. Fundação, pedidos, administração e backend do assistente foram validados com Docker; nenhuma cobrança foi habilitada. A interface do chat continua na fase 11.

## Arquitetura prevista

```mermaid
flowchart LR
    Usuario[Usuário] --> Frontend[React / TypeScript / Vite]
    Frontend -->|REST + token Sanctum| API[Laravel]
    API --> Banco[(PostgreSQL)]
    API -->|Mensagens e ferramentas autorizadas| Gemini[Gemini API]
    Gemini -->|Solicitação de ferramenta| API
```

Laravel concentra autenticação, Policies, validação e regras de negócio. O Gemini não acessa o banco: solicita ferramentas cadastradas que executam no contexto do usuário autenticado. O MVP usa dados fictícios e transportadoras simuladas.

### Organização do monorepo

| Caminho planejado | Responsabilidade |
| --- | --- |
| `frontend/src/` | Páginas, componentes, rotas, serviços HTTP e hooks |
| `frontend/public/` | Assets estáticos |
| `backend/app/Http/` | Controllers, Form Requests e API Resources |
| `backend/app/Policies/` | Autorização de pedidos, conversas e administração |
| `backend/app/Actions/` | Cancelamento e atualização de status |
| `backend/app/AI/` | Assistente, registro e ferramentas de consulta |
| `backend/app/Jobs/` | Geração de títulos de conversas |
| `backend/app/Services/Tracking/` | Contrato e provedores simulados |
| `backend/database/` | Migrations, factories e seeders |
| `backend/tests/` | Testes de integração e domínio |
| `docs/` | Arquitetura, acompanhamento e screenshots |

A base de `frontend/` e `backend/` foi criada na fase 02. Policies, Actions, ferramentas de IA e provedores de rastreamento serão adicionados com suas funcionalidades.

## Inventário do host na fase 01

| Ferramenta | Versão observada | Verificação |
| --- | --- | --- |
| Git | 2.34.1 | Repositório inicializado em `main`, ainda sem commits |
| Docker CLI | 29.8.1 | Executável disponível |
| Docker Engine | 29.4.0 | Daemon respondeu a `docker info` após autorização de acesso ao socket |
| Docker Compose | 5.1.2 | Plugin disponível |
| Node.js | 22.13.0 | Disponível via nvm |
| npm | 10.9.2 | Disponível |
| PHP no host | 8.2.33 | Não atende ao mínimo do Laravel 13 |
| Composer no host | 2.8.4 | Disponível; não usar o PHP do host para resolver dependências de PHP 8.4 |
| Arquitetura da máquina | x86_64 | Verificada com `uname -m` |

Identidade Git já configurada. Remoto `origin`: `https://github.com/italo-vinicius/order-mind.git`, informado pelo usuário e configurado com autorização. `git ls-remote origin` terminou com código 0 e sem referências: remoto acessível e vazio nesta consulta. Nenhum push executado.

## Compatibilidade e execução definidas

- **Backend:** Laravel 13 (`^13.0`) com PHP 8.4.26 e Composer 2.10.3 em contêineres. Laravel 13 requer PHP ≥ 8.3; a documentação atual do Pest requer PHP ≥ 8.4. PHP 8.4 atende aos dois requisitos e evita alterar o PHP instalado no host. [Laravel](https://laravel.com/docs/13.x/releases), [Pest](https://pestphp.com/docs/installation).
- **Frontend:** usar Node.js 22.23.3 com npm 10.9.9 na stack do projeto, conforme o [arquivo oficial da versão](https://nodejs.org/en/download/archive/v22.23.3). A instalação observada atende ao mínimo publicado do Vite, mas não foi validada com as dependências do projeto. [Vite](https://vite.dev/guide/), [ciclo do Node.js](https://nodejs.org/en/about/previous-releases).
- **Banco:** PostgreSQL 17.11 local, conforme a [política oficial de versões](https://www.postgresql.org/support/versioning/), e major 17 no Neon, que gerencia atualizações de patch. Confirmar a opção no provisionamento. [Neon](https://neon.com/blog/postgres-17).
- **Qualidade:** Pest, Vitest com React Testing Library e Playwright, conforme o plano; Laravel Pint, ESLint e Prettier para padronização. A resolução exata das versões será verificada antes de instalar, sem misturar instruções de versões de desenvolvimento com estáveis. [Vitest](https://vitest.dev/guide/migration.html), [Playwright](https://playwright.dev/docs/intro).
- **Reprodutibilidade:** os patches acima são a referência inicial, consultada em 26/09/2026. PHP confirmado na [página de downloads](https://www.php.net/downloads.php), Composer na [página oficial](https://getcomposer.org/download/). Na fase 02, validar disponibilidade das imagens e fixar tags/digests, além de gerar `composer.lock` e `package-lock.json` com versões exatas das bibliotecas. Não usar `latest` como configuração permanente. Compatibilidade documental não equivale a build/testes executados; qualquer incompatibilidade deverá ser resolvida e registrada antes do scaffold.

## Contratos para implementação

Sessão e regras de negócio foram aprovadas via Ask Question em 26/09/2026. Os contratos HTTP abaixo completam os fluxos já previstos no MVP.

### API

- Prefixo `/api`, JSON, chaves em `snake_case`, IDs internos numéricos e número do pedido separado para exibição/busca.
- Resources com envelope `data`; listas paginadas com `data`, `links` e `meta`.
- Paginação: 15 itens por padrão, máximo de 100; filtros e ordenação limitados a campos permitidos.
- Erros com `message`; validação com `errors` por campo. Usar 401 para ausência de autenticação, 403 para ação proibida, 404 para recurso não visível, 422 para entrada inválida e 429 para limite excedido.
- Adicionar `POST /api/conversations` com criação no contexto autenticado e resposta 201; listar em `GET /api/conversations`, consultar detalhes em `GET /api/conversations/{conversation}` e enviar mensagens em `POST /api/conversations/{conversation}/messages`.
- Nunca aceitar `user_id` enviado pelo frontend ou pela IA como autoridade para consultas de cliente.

### Sessão — aprovada em 26/09/2026

Escolha explícita do usuário: token Sanctum apenas em memória, expiração em duas horas, revogação no logout e novo login ao recarregar a página. Limpar caches no logout ou mudança de usuário. Não persistir token em `localStorage` ou `sessionStorage` sem nova decisão explícita.

Na fase 05, `POST /api/auth/login` emite um token Bearer Sanctum com `expires_at` de 120 minutos e devolve somente os dados mínimos do usuário. `POST /api/auth/logout` remove o token apresentado, e `GET /api/auth/me` requer `auth:sanctum`. Falhas de autenticação retornam o mesmo erro para e-mail e senha; cinco tentativas inválidas para o mesmo e-mail/IP bloqueiam novas tentativas por 60 segundos. CORS aceita apenas `FRONTEND_URL`, permite `Authorization` e não aceita cookies. A origem e o tempo de expiração são configuráveis por ambiente, com os valores seguros padrão documentados em `.env.example`.

`OrderPolicy` permite ao cliente somente pedidos próprios e ao administrador todos os pedidos. `ConversationPolicy` permite somente o titular, inclusive para administradores; `ToolLogPolicy` permite somente administradores. O Gate `access-admin` protege as rotas administrativas. Controllers das fases seguintes devem autorizar o recurso antes de carregar relações aninhadas ou devolver dados.

## Entrega da fase 06 — sessão e interface base

O frontend separa integração HTTP em `frontend/src/lib/api.ts`, estado de sessão em `frontend/src/auth/`, elementos reutilizáveis em `frontend/src/components/` e rotas em `App.tsx`. `AuthProvider` mantém token, usuário e instante de expiração apenas no estado React. Ele agenda o encerramento ao vencer, limpa o cache TanStack Query no logout ou em resposta 401 e não usa qualquer armazenamento persistente. `ProtectedRoute` e `AdminRoute` impedem a renderização de páginas privadas sem a sessão e perfil apropriados.

A tela `/login` valida e envia credenciais com React Hook Form e Zod. Ela mostra a conta pública do cliente demo, apresenta falhas da API e consulta a saúde do servidor com timeout de dez segundos e ação de nova tentativa. Após login, o layout mostra perfil e logout; pedidos ainda aguardam a API da fase 07. O CI aplica migrations e o `DemoDataSeeder` no ambiente efêmero antes dos testes Playwright para que o login de demonstração seja verificável de ponta a ponta.

A autenticação SPA por cookie do Sanctum exige domínio raiz compartilhado. Usar cookie/proxy com os domínios distintos previstos demanda uma decisão de arquitetura; não será adotado automaticamente. [Sanctum](https://laravel.com/docs/13.x/sanctum).

### Regras de negócio — aprovadas em 26/09/2026

- Moeda única BRL; valores no banco como `numeric(12,2)` e na API como strings decimais, evitando cálculos monetários com ponto flutuante.
- Timestamps armazenados em UTC e enviados em ISO 8601; exibição e intervalo mensal em `America/Sao_Paulo`.
- Gasto mensal pelo campo `placed_at`, excluindo pedidos cancelados, com intervalo entre início do mês inclusivo e início do mês seguinte exclusivo.
- Atraso: estimativa vencida e pedido não entregue/cancelado. Esse predicado rege indicadores e consultas de atrasados; o status explícito `delayed` continua representando o estado operacional registrado. A consulta não altera o status persistido.
- Cancelamento: somente `pending_payment` ou `processing`, com instante atual menor ou igual a `cancellable_until`. A matriz completa das demais transições será documentada na fase 04; não introduzir exceções sem consulta.

### Estados do pedido — aprovados na fase 04 em 26/09/2026

Os sete valores persistidos são `pending_payment`, `processing`, `shipped`, `out_for_delivery`, `delayed`, `delivered` e `cancelled`. A matriz aprovada é:

| Estado atual | Próximos estados permitidos |
| --- | --- |
| `pending_payment` | `processing`, `cancelled` |
| `processing` | `shipped`, `cancelled` |
| `shipped` | `out_for_delivery`, `delayed` |
| `out_for_delivery` | `delivered`, `delayed` |
| `delayed` | `out_for_delivery`, `delivered` |
| `delivered` | nenhum (terminal) |
| `cancelled` | nenhum (terminal) |

O cancelamento continua condicionado a `pending_payment` ou `processing` e a `cancellable_until` inclusivo. `delayed` representa o atraso operacional; o cálculo por previsão vencida continua sendo um predicado de consulta e não altera o estado automaticamente.

## Hospedagem e orçamento

Orçamento autorizado para novas despesas: **zero**. Não habilitar faturamento, upgrade automático ou recursos pagos. Nenhuma conta ou quota individual foi verificada nesta etapa.

| Serviço previsto | Evidência pública e consequência |
| --- | --- |
| Vercel Hobby | Destinado a projetos pessoais não comerciais; adequado ao objetivo de portfólio, sujeito às quotas. [Documentação](https://vercel.com/docs/plans/hobby) |
| Render Free Web Service | Suspende após 15 minutos sem tráfego; filesystem efêmero, sem worker separado gratuito ou shell/one-off jobs gratuitos. Há limites de horas e tráfego; revisar comportamento de cobrança antes de provisionar. Persistir dados no PostgreSQL externo e planejar migrations sem depender de shell. [Documentação](https://render.com/docs/free) |
| Neon Free | Fonte oficial no GitHub informa 100 CU-h por projeto/mês, 0,5 GB por projeto e 5 GB de transferência pública por projeto/mês. Página principal indisponível nesta consulta; reconfirmar no painel antes do deploy. [Fonte oficial](https://github.com/neondatabase/website/blob/main/content/faqs/free-plan-limits-and-quotas.md) |
| Gemini | Gratuidade e quotas dependem do modelo e da conta. Selecionar um modelo de texto com function calling elegível para Free Tier, sem fallback pago; fixar em `GEMINI_MODEL` antes da integração. A tabela pública informa uso de conteúdo para melhoria de produtos no Free Tier: usar somente dados fictícios e exibir aviso no chat. [Preços e condições por modelo](https://ai.google.dev/gemini-api/docs/pricing) |

### Filas — aprovadas em 26/09/2026

O plano prevê `GenerateConversationTitleJob`. Um worker separado no Render não tem modalidade gratuita. O usuário aprovou fila no desenvolvimento e execução síncrona do título em produção, com timeout e título de fallback; preserva a funcionalidade, mas pode aumentar a latência. Limitar a geração ao primeiro título da conversa e preservar a resposta do chat se a geração falhar. Não contratar worker ou ativar fallback pago.

## Registro de decisões

| ID | Questão | Estado |
| --- | --- | --- |
| D01 | Filas locais e título síncrono em produção com timeout/fallback | Aprovado via Ask Question em 26/09/2026; custo zero |
| D02 | Token em memória, duas horas, logout revoga; recarregar exige login | Aprovado via Ask Question em 26/09/2026 |
| D03 | Remoto GitHub informado pelo usuário | Configurado e acesso de leitura validado em 26/09/2026 |
| D04 | BRL, UTC/São Paulo, gasto por placed_at sem cancelados, atraso por previsão vencida, cancelamento em pending_payment/processing até o prazo inclusive | Aprovado via Ask Question em 26/09/2026 |
| D05 | Gemini `gemini-3.7-flash` somente em Free Tier; sem faturamento nem ferramentas gerenciadas | Aprovado via Ask Question em 26/09/2026; dados do MVP são fictícios |

As decisões críticas registradas foram resolvidas. O encerramento da fase 01 registra este documento e o plano no commit `docs: define environment and architecture decisions`. A fase 02 começa com o scaffold e a instalação isolada dos runtimes definidos; nenhuma mudança no PHP global é necessária.


## Entrega da fase 02 — ambiente local

### Versões resolvidas

| Componente | Versão instalada |
| --- | --- |
| PHP / Composer (contêiner) | 8.4.26 / 2.10.3 |
| Laravel / Sanctum | 13.33.0 / 4.3.3 |
| PostgreSQL | 17.11 |
| Node.js / npm (contêiner) | 22.23.3 / 10.9.9 |
| React / TypeScript / Vite | 19.3.0 / 6.0.3 / 8.3.1 |
| React Router / TanStack Query | 7.18.4 / 5.104.0 |
| Tailwind CSS / shadcn CLI | 4.3.3 / 4.21.0 |
| React Hook Form / Zod / Recharts | 7.89.0 / 3.25.76 / 3.10.1 |

Os lockfiles são a referência das versões exatas das bibliotecas. As imagens Docker usam tags de patch explícitas; nenhuma configuração permanente usa `latest`. O scaffold foi gerado por `create-vite` 9.2.1 e `laravel/laravel` 13.10.1. shadcn foi inicializado com tema `base-nova`, botão baseado em Base UI e fonte Geist servida localmente.

### Serviços e configuração

- `database`: PostgreSQL em volume nomeado, sem porta publicada no host.
- `backend`: `artisan serve` em `localhost:8000`, com check HTTP que consulta o PostgreSQL.
- `frontend`: Vite em `localhost:5173`, consumindo `VITE_API_URL` pelo navegador; React Router e TanStack Query inicializados.
- `queue`: worker Laravel com conexão `database`, migrations de fila e reinício local após saída do processo. Jobs do produto virão nas fases correspondentes.
- PHP/Composer e Node/npm executam como o UID/GID local, sem alterar runtimes globais. Dependências ficam em diretórios ignorados dentro do projeto.
- `bash scripts/setup.sh` prepara um clone limpo: exemplos de ambiente, dependências pelos lockfiles, chave local quando vazia, migrations e inicialização.
- `DATABASE_URL` é lida pela conexão PostgreSQL; `DB_*` atendem ao desenvolvimento. CORS aceita somente `FRONTEND_URL`. Sanctum instalado com expiração de 120 minutos; login e emissão de tokens ainda não implementados.
- `/api/health`: readiness, com HTTP 200/503 e payload mínimo sobre o banco. `/up`: liveness do Laravel. A página inicial mostra conexão, indisponibilidade e nova tentativa, sem apresentar recursos de pedidos ainda inexistentes.

### Verificações realizadas

Setup executado no projeto e em cópia limpa sem `vendor`/`node_modules`, com banco e portas isolados. Build TypeScript/Vite e validação estrita do Composer aprovados. PHPUnit inicial: três testes e 12 assertions para saúde, falha sanitizada e CORS; Pint aprovado. Chromium temporário confirmou React → API → PostgreSQL, nova tentativa, erro 503/recuperação, navegação 404, layout em 390 px sem overflow e rejeição de origem não autorizada pelo navegador.

O Dockerfile atual é de desenvolvimento; produção continua nas fases 13–14.

## Entrega da fase 03 — qualidade e integração contínua

### Ferramentas

- **Backend:** Pest 4.7.8 com plugin Laravel 4.1.0 e Laravel Pint. `scripts/test-backend.sh` sobe `database-test`, aplica `migrate:fresh` somente nele e executa Pest. A base principal `database` e seu volume não são alvo desse comando.
- **Frontend:** ESLint 10, Prettier 3, TypeScript, Vitest 5 em JSDOM, React Testing Library e user-event. A cobertura numérica fica desativada até existirem fluxos que a tornem útil; os testes focam comportamentos críticos.
- **Ponta a ponta:** Playwright 1.63, com Chromium e smoke tests da página inicial. O binário do navegador fica fora do repositório no desenvolvimento; o CI o instala no runner.

### Isolamento e pipeline

`database-test` usa PostgreSQL 17.11, banco, usuário e volume próprios. O `phpunit.xml` seleciona essa conexão; há um teste que confirma o driver e `current_database()`. Migrations de teste não podem tocar no banco de desenvolvimento por configuração ou pelo script de execução.

`.github/workflows/quality.yml` executa em pushes para `main` e pull requests. Antes do primeiro comando Docker, o job cria o `.env` raiz e define `LOCAL_UID`/`LOCAL_GID` com o usuário do runner, para que o bind mount de `backend/vendor` seja gravável pelo Composer. Depois restaura dependências pelos lockfiles, cria os demais arquivos de ambiente descartáveis, constrói o runtime PHP, verifica Pint/Prettier/ESLint/TypeScript/Composer, executa Vitest e Pest, inicia API e frontend, consulta `/api/health` e roda Playwright. Relatórios do navegador são anexados se houver falha. A primeira execução falhou antes da instalação do Composer por essa permissão; a correção foi enviada e a execução seguinte passou.

## Entrega da fase 04 — domínio e dados de demonstração

`User` tem perfil `admin` ou `customer`. `Order` pertence ao cliente e contém valores `numeric(12,2)`, endereço JSON, transportadora, código de rastreio, datas de pedido/estimativa/cancelamento/entrega e um `OrderStatus`. Itens e eventos pertencem ao pedido; conversas pertencem ao usuário; mensagens pertencem a conversas; logs de ferramenta mantêm o usuário e, quando aplicável, a conversa e mensagem de origem. Chaves estrangeiras, unicidade de `orders.number` e índices para proprietário, estado, data, transportadora e linha do tempo já estão definidos.

`DemoDataSeeder` é seguro para reexecução: localiza registros de demonstração por identificadores estáveis antes de criá-los e não recria o banco. Ele gera `admin@ordermind.test`, `ana@ordermind.test` e `bruno@ordermind.test`, todos com a senha pública `ordermind-demo`, além de 30 pedidos e conversas fictícias. Essas credenciais existem somente para a demonstração local; credenciais operacionais jamais entram em seeders ou no repositório.

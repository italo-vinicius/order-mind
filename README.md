# OrderMind

Aplicação de demonstração para acompanhar pedidos e consultar informações com um assistente de IA.

**Estado atual:** fases 01 a 10 concluídas. Login, pedidos, administração e o backend do assistente funcionam localmente; a interface de chat será criada na fase 11. Ainda não há deploy público.

## Stack e organização

- `frontend/`: React, TypeScript, Vite, React Router, TanStack Query, Tailwind CSS e shadcn/ui. React Hook Form, Zod e Recharts instalados para as próximas telas.
- `backend/`: Laravel 13, Sanctum e PostgreSQL; fila local usando o banco.
- `docker/php.Dockerfile`: runtime de desenvolvimento PHP 8.4 com Composer e extensões.
- `docker-compose.yml`: frontend, API, PostgreSQL e worker local.
- [Plano de implementação](docs/PLANO_IMPLEMENTACAO.md): fases, critérios e histórico.
- [Arquitetura](docs/architecture.md): contratos e decisões aprovadas.

## Iniciar do zero

Requisitos: Git, Docker Engine operacional, Docker Compose v2 ou superior e Bash. PHP, Composer e Node do host não são necessários para o fluxo Docker. As imagens usam PHP 8.4.26, Composer 2.10.3, Node 22.23.3 e PostgreSQL 17.11, com dependências fixadas nos lockfiles.

```bash
git clone https://github.com/italo-vinicius/order-mind.git
cd order-mind
bash scripts/setup.sh
```

O script copia os exemplos de ambiente somente se estiverem ausentes, instala dependências, gera `APP_KEY` apenas quando vazia, aplica migrations pendentes e inicia os serviços. Pode ser executado novamente sem apagar o banco ou trocar uma chave existente. A primeira execução baixa imagens e compila extensões PHP.

- Frontend: http://localhost:5173
- API: http://localhost:8000/api/health
- Liveness da API: http://localhost:8000/up

O health check responde `200` com `{"data":{"status":"ok","database":"ok"}}` quando o banco está acessível; responde `503` sem detalhes da conexão quando indisponível. Na página inicial, a confirmação aparece como **Conexão disponível**.

## Configuração local

| Arquivo | Configuração |
| --- | --- |
| `.env` na raiz | UID/GID usados pelos contêineres e portas externas; gerado pelo setup |
| `backend/.env` | Laravel, PostgreSQL, origem do frontend e futuras credenciais de IA |
| `frontend/.env` | `VITE_API_URL`, URL acessível pelo navegador, incluindo `/api` |

O banco usa valores públicos exclusivos do desenvolvimento definidos no Compose e em `backend/.env.example`, e não publica porta no host. Não reutilize essas credenciais em produção. O volume `postgres_data` preserva os dados entre reinícios.

Se alterar `API_PORT` ou `FRONTEND_PORT` na raiz, ajuste também `APP_URL`/`FRONTEND_URL` no backend e `VITE_API_URL` no frontend. O CORS permite somente a origem exata de `FRONTEND_URL`; abra a interface por `localhost`, conforme os exemplos. Reinicie os serviços após mudar variáveis.

Em Linux, o setup grava seu UID/GID na configuração local para evitar arquivos pertencentes a root. Se houver erro de permissão, confira `LOCAL_UID` e `LOCAL_GID`. Não é necessário usar PHP ou npm globais.

### Gemini local

O assistente usa `gemini-3.7-flash` exclusivamente no Free Tier, sem faturamento, Google Search ou ferramentas gerenciadas. Crie uma chave no Google AI Studio e adicione-a apenas ao arquivo ignorado `backend/.env`:

```dotenv
GEMINI_API_KEY=sua_chave_local
GEMINI_MODEL=gemini-3.7-flash
```

Reinicie o serviço `backend` após alterar essas variáveis. Não envie a chave por chat, não a versione e não use o prefixo `VITE_`. O Free Tier pode usar o conteúdo para melhoria do produto; a demonstração envia somente dados fictícios.

## Comandos de desenvolvimento

Execute na raiz:

| Comando | Uso |
| --- | --- |
| `docker compose up -d --wait` | Iniciar serviços já preparados |
| `docker compose down` | Parar e remover contêineres, preservando o volume do banco |
| `docker compose ps` | Ver estado e portas dos serviços |
| `docker compose logs -f backend queue frontend` | Acompanhar logs |
| `docker compose restart backend queue frontend` | Reiniciar após alterações de configuração |
| `docker compose exec backend php artisan migrate` | Aplicar migrations pendentes |
| `docker compose exec backend php artisan route:list --path=api` | Conferir rotas da API |
| `docker compose exec backend php artisan queue:restart` | Recarregar código do worker após alterações |
| `docker compose run --rm --no-deps backend composer install` | Restaurar dependências PHP pelo lockfile |
| `docker compose run --rm --no-deps frontend npm ci` | Restaurar dependências JS pelo lockfile |
| `docker compose run --rm --no-deps frontend npm run build` | Validar TypeScript e gerar `frontend/dist/` |
| `docker compose run --rm --no-deps frontend npm run typecheck` | Verificar TypeScript |
| `docker compose run --rm --no-deps backend php artisan test` | Executar testes iniciais de saúde e CORS |
| `docker compose run --rm --no-deps backend vendor/bin/pint --test` | Verificar estilo PHP |

## Qualidade e testes

| Comando | Uso |
| --- | --- |
| `docker compose run --rm --no-deps frontend npm run format:check` | Conferir Prettier |
| `docker compose run --rm --no-deps frontend npm run lint` | Executar ESLint |
| `docker compose run --rm --no-deps frontend npm run typecheck` | Conferir TypeScript |
| `docker compose run --rm --no-deps frontend npm run test` | Executar Vitest e React Testing Library |
| `bash scripts/test-backend.sh` | Iniciar PostgreSQL de testes, recriar somente esse banco e executar Pest |
| `PLAYWRIGHT_BROWSERS_PATH=/tmp/ordermind-playwright npx --prefix frontend playwright install chromium` | Baixar Chromium temporário para testes locais |
| `PLAYWRIGHT_BROWSERS_PATH=/tmp/ordermind-playwright npm --prefix frontend run test:e2e` | Executar smoke tests Playwright contra os serviços ativos |

O banco de testes é o serviço isolado `database-test`, com volume próprio e credenciais exclusivas de desenvolvimento. `scripts/test-backend.sh` executa `migrate:fresh` apenas nesse banco; nunca aponta para o volume de desenvolvimento. O primeiro teste ponta a ponta precisa do Chromium. No GitHub Actions, o workflow baixa esse navegador e executa a mesma sequência de lint, tipos, testes, build e verificação da API.

O worker precisa das migrations de `jobs` e `failed_jobs`, executadas pelo setup. Após alterações, use `queue:restart` ou reinicie o serviço `queue`; o Compose reinicia o processo encerrado graciosamente.

Para frontend no host, use a versão de `.nvmrc`: `cd frontend`, `npm ci` e `npm run dev`. Pare o serviço frontend do Compose para liberar a porta. A API continua em Docker.

## Validação e limites da entrega

Pest verifica a conexão PostgreSQL exclusiva de testes, saúde, indisponibilidade sanitizada e CORS restrito. Vitest cobre o cliente HTTP e os estados de conexão da interface. Playwright cobre a página carregada e seu estado recuperável de indisponibilidade. A verificação real do PostgreSQL também acontece pelo endpoint em execução.

Este Compose e o servidor `artisan serve` são exclusivos de desenvolvimento. A imagem de produção e o deploy serão preparados nas fases 13–14. Não execute comandos que removam volumes se quiser manter os dados locais.

Segredos ficam em arquivos `.env` ignorados. Somente `VITE_API_URL` é exposta ao frontend. Chaves Gemini nunca devem usar o prefixo `VITE_`. O projeto não habilita faturamento nem recursos de IA pagos.

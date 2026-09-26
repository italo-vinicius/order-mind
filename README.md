# OrderMind

Aplicação de demonstração para acompanhar pedidos e consultar informações com um assistente de IA.

**Estado atual:** fundação local da fase 02. A página inicial consulta a saúde da API, que verifica o PostgreSQL. Login, pedidos, administração e IA serão implementados nas próximas fases; ainda não há usuário de demonstração ou deploy público.

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

O worker precisa das migrations de `jobs` e `failed_jobs`, executadas pelo setup. Após alterações, use `queue:restart` ou reinicie o serviço `queue`; o Compose reinicia o processo encerrado graciosamente.

Para frontend no host, use a versão de `.nvmrc`: `cd frontend`, `npm ci` e `npm run dev`. Pare o serviço frontend do Compose para liberar a porta. A API continua em Docker.

## Validação e limites da entrega

Os testes iniciais verificam resposta saudável, indisponibilidade do banco sem vazamento de detalhes e CORS restrito. A verificação real do PostgreSQL acontece pelo endpoint em execução. A configuração completa de Pest, testes do frontend, Playwright, ESLint, Prettier e CI pertence à fase 03.

Este Compose e o servidor `artisan serve` são exclusivos de desenvolvimento. A imagem de produção e o deploy serão preparados nas fases 13–14. Não execute comandos que removam volumes se quiser manter os dados locais.

Segredos ficam em arquivos `.env` ignorados. Somente `VITE_API_URL` é exposta ao frontend. Chaves Gemini nunca devem usar o prefixo `VITE_`. Nesta fase, nenhuma chamada de IA ou serviço pago é necessária.

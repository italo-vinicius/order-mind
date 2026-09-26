# OrderMind — Plano de execução e acompanhamento

## Objetivo e situação atual

Implementar uma aplicação de demonstração para acompanhamento de pedidos, com dashboard, rastreamento simulado, administração e assistente de IA que consulta dados autorizados do usuário. Este documento define o escopo do MVP, controla a execução e estabelece os critérios de conclusão, da instalação da stack à publicação. As decisões técnicas estão em `docs/architecture.md`.

**Situação em 26/09/2026:** fase 01 concluída: ambiente verificado, Git inicializado, remoto acessível, arquitetura e contratos documentados. Decisões críticas aprovadas via Ask Question. Nenhuma dependência de aplicação instalada ou infraestrutura externa provisionada; fase 02 ainda não iniciada.

## Regras de execução e commits

1. Executar as fases na ordem abaixo. As subfases são checklists internos e não exigem commits separados.
2. Implementar testes junto com cada funcionalidade, sem adiar toda a validação para a fase de qualidade.
3. Ao terminar uma fase, executar suas verificações e registrar neste documento: tarefas concluídas, resultados, decisões, desvios e data.
4. Atualizar o status para `Concluída` somente quando todos os critérios de aceite forem atendidos. Se houver impedimento, registrar `Bloqueada`, causa e próximo passo; não declarar conclusão parcial.
5. Fazer um commit de encerramento por fase, incluindo a implementação e a atualização deste documento. As mensagens propostas abaixo identificam cada entrega. Selecionar somente arquivos relacionados à fase.
6. Usar o título único do commit como referência no documento; consultar o hash pelo histórico depois. Não incluir o hash do próprio commit no conteúdo que ele registra.
7. Antes de avançar, confirmar que o commit foi criado. Se Git estiver indisponível, registrar o impedimento e resolver o versionamento antes de prosseguir.
8. Aplicar a regra de decisões críticas abaixo em todas as fases, inclusive instalação, configuração e deploy. Uma tarefa prevista no checklist não autoriza assumir custos ou resolver escolhas críticas ainda não aprovadas.

Estados permitidos: `Pendente`, `Em andamento`, `Bloqueada`, `Concluída`. Ao iniciar uma fase, atualizar seu estado; ao encerrá-la, preencher o registro de execução no final deste documento.

## Decisões críticas — interromper e usar Ask Question

**Ao identificar uma decisão crítica sem autorização prévia explícita, interromper a implementação e consultar o usuário pelo recurso Ask Question. Não escolher nem executar a alternativa por conta própria.**

Exigir essa consulta quando a decisão envolver:

- **Custo financeiro:** contratação, plano pago, cobrança por uso, upgrade ou recurso que possa ultrapassar a franquia gratuita. O orçamento padrão para novas despesas é zero até aprovação explícita.
- **Custo técnico elevado:** mudança de stack, banco, provedor, arquitetura ou dependência central com impacto relevante em manutenção, migração ou retrabalho.
- **Dados e reversibilidade:** exclusão de dados, recriação de banco fora dos testes, migrations destrutivas, alteração irreversível de infraestrutura ou reescrita de histórico compartilhado.
- **Segurança e acesso:** mudanças relevantes em autenticação, armazenamento de tokens, permissões, exposição pública de dados ou tratamento de informações sensíveis.
- **Escopo e produto:** inclusão/remoção de funcionalidades do MVP, alteração das regras de negócio ou redução dos critérios de aceite para contornar uma limitação.
- **Impasses entre alternativas:** ausência de uma opção que atenda ao plano sem comprometer custo, segurança ou viabilidade. Na dúvida sobre um impacto potencialmente alto, consultar antes de agir.

### Procedimento obrigatório

1. Investigar o suficiente para apresentar uma decisão concreta, usando leituras e verificações seguras, sem contratar, provisionar recursos com custo ou aplicar a mudança em discussão.
2. Registrar a fase como `Bloqueada — aguardando decisão do usuário`, detalhando o problema e as tarefas afetadas.
3. Abrir **Ask Question** com contexto curto, motivo da interrupção e alternativas viáveis. Informar recomendação, custo estimado (ou desconhecido), riscos, esforço, reversibilidade e impacto no MVP. Incluir a opção de adiar ou manter o escopo quando viável.
4. Aguardar resposta explícita. Silêncio, tempo decorrido ou opção pré-selecionada não são aprovação. Se Ask Question estiver indisponível, perguntar diretamente na conversa e manter a implementação suspensa.
5. Registrar a escolha e os limites autorizados neste documento; atualizar as tarefas e retomar somente dentro desses limites. Uma nova consequência crítica fora da autorização exige nova consulta.

Decisões rotineiras, locais e reversíveis, compatíveis com o plano e sem custo relevante, podem ser tomadas autonomamente. Não repetir perguntas sobre decisões já aprovadas, salvo mudança material nas condições. Propostas técnicas deste documento não equivalem à aprovação de despesas ou desvios críticos.

**Exemplos:** se filas exigirem um worker pago, consultar antes de contratar ou mudar o comportamento; se a hospedagem gratuita não atender ao MVP, apresentar alternativas antes de trocar de provedor; se uma migration exigir apagar dados, interromper antes de executá-la.

## Visão geral

| Fase | Entrega | Dependência | Status |
| --- | --- | --- | --- |
| 01 | Pré-requisitos e decisões técnicas | — | Concluída |
| 02 | Monorepo, stack e ambiente local | 01 | Pendente |
| 03 | Qualidade automatizada e integração contínua | 02 | Pendente |
| 04 | Modelo de domínio e dados de demonstração | 03 | Pendente |
| 05 | Autenticação e autorização no backend | 04 | Pendente |
| 06 | Base visual e autenticação no frontend | 05 | Pendente |
| 07 | API de pedidos, dashboard e rastreamento | 06 | Pendente |
| 08 | Interface de pedidos e dashboard | 07 | Pendente |
| 09 | Administração no backend e frontend | 08 | Pendente |
| 10 | Ferramentas e orquestração de IA no backend | 09 | Pendente |
| 11 | Chat, histórico e integração no frontend | 10 | Pendente |
| 12 | Revisão integrada e observabilidade | 11 | Pendente |
| 13 | Preparação de produção | 12 | Pendente |
| 14 | Deploy, validação pública e documentação | 13 | Pendente |

## Fase 01 — Pré-requisitos e decisões técnicas

### 1.1 Ambiente e versionamento
- [x] Verificar Git, acesso ao repositório remoto e identidade de commits; inicializar o repositório somente se necessário, preservando arquivos existentes.
- [x] Verificar e instalar os pré-requisitos ausentes: Docker com Compose, Node.js com npm e Git. Definir se PHP e Composer serão executados exclusivamente em contêineres ou também no host.
- [x] Registrar versões compatíveis de Node.js, PHP, Laravel, PostgreSQL e ferramentas, consultando a documentação oficial na execução; não depender de tags flutuantes.

### 1.2 Decisões e contratos
- [x] Registrar em `docs/architecture.md` a arquitetura React → Laravel → PostgreSQL/Gemini e a estrutura do monorepo.
- [x] Adotar como proposta Pest para backend, Vitest com React Testing Library para frontend e Playwright para os fluxos ponta a ponta; confirmar compatibilidade na instalação.
- [x] Definir política de sessão/token, expiração, moeda, precisão monetária, fuso horário e cálculo de atraso.
- [x] Definir contratos de resposta, erros, paginação, identificação de pedidos e criação de conversas. Proposta: adicionar `POST /api/conversations`, pois o plano prevê mensagens em conversas existentes, mas não explicita sua criação.
- [x] Validar disponibilidade e limites dos provedores previstos: Vercel, Render, Neon e Gemini, incluindo viabilidade de filas sem custo adicional. Registrar alternativas se necessário.

**Aceite:** pré-requisitos disponíveis, Git operacional e decisões documentadas com versões e pendências resolvidas para iniciar a instalação.

**Commit:** `docs: define environment and architecture decisions`

## Fase 02 — Monorepo, stack e ambiente local

### 2.1 Backend e banco
- [ ] Criar `backend/` com Laravel e dependências compatíveis; instalar Sanctum e configurar PostgreSQL.
- [ ] Criar ambiente Docker Compose para API, banco e execução local de filas, com volumes e verificações de saúde.
- [ ] Implementar `GET /api/health` e configurar comunicação entre serviços.

### 2.2 Frontend
- [ ] Criar `frontend/` com React, TypeScript e Vite.
- [ ] Instalar React Router, TanStack Query, Tailwind CSS, shadcn/ui, React Hook Form, Zod e Recharts.
- [ ] Configurar acesso à API por `VITE_API_URL` e validar uma chamada ao endpoint de saúde.

### 2.3 Configuração reproduzível
- [ ] Criar `.gitignore`, arquivos de versões, lockfiles, `.env.example` dos dois projetos e README inicial.
- [ ] Documentar instalação, inicialização, parada, logs, migrations e execução de comandos dentro dos contêineres.

**Aceite:** um ambiente limpo consegue iniciar a stack pelas instruções; frontend acessa a API e Laravel conecta ao PostgreSQL; nenhum segredo está versionado.

**Commit:** `chore: bootstrap application stack and local environment`

## Fase 03 — Qualidade automatizada e integração contínua

### 3.1 Ferramentas
- [ ] Configurar ESLint, Prettier, checagem TypeScript e Laravel Pint, com comandos documentados.
- [ ] Configurar Pest, Vitest, React Testing Library e Playwright conforme a decisão da fase 01.
- [ ] Preparar banco PostgreSQL isolado para testes, factories e substituição das chamadas externas nos testes.

### 3.2 Pipeline
- [ ] Criar GitHub Actions para instalação por lockfiles, lint, tipos, testes e build do frontend.
- [ ] Adicionar verificações básicas de saúde da API e renderização inicial para validar o pipeline.
- [ ] Definir execução dos testes ponta a ponta no CI assim que os fluxos estiverem implementados.

**Aceite:** comandos locais documentados passam; pipeline configurado e, havendo remoto disponível, execução no GitHub validada. Não estabelecer percentual arbitrário de cobertura: priorizar comportamentos críticos.

**Commit:** `ci: configure formatting tests and build checks`

## Fase 04 — Modelo de domínio e dados de demonstração

### 4.1 Persistência e regras
- [ ] Criar migrations e models para usuários, pedidos, itens, rastreamento, conversas, mensagens e logs de ferramentas.
- [ ] Configurar relacionamentos, índices, chaves estrangeiras, unicidade do número do pedido e campos monetários com precisão definida.
- [ ] Criar `OrderStatus` com os sete estados do plano e documentar a matriz de transições e as regras de cancelamento.

### 4.2 Dados fictícios
- [ ] Criar factories e seeders com um administrador, dois clientes e aproximadamente 30 pedidos, com três a seis itens e eventos coerentes.
- [ ] Incluir diferentes transportadoras, estados, prazos, pedidos canceláveis e conversas de exemplo.
- [ ] Definir reexecução segura dos seeders e separar credenciais públicas de demonstração de quaisquer credenciais operacionais.

**Aceite:** migrations executam em banco vazio; seeders produzem dados consistentes; testes verificam relacionamentos, valores e isolamento das fixtures. Operações de recriação do banco ficam restritas ao ambiente de teste/desenvolvimento.

**Commit:** `feat: add order domain and demonstration data`

## Fase 05 — Autenticação e autorização no backend

### 5.1 Sessão
- [ ] Implementar `POST /api/auth/login`, `POST /api/auth/logout` e `GET /api/auth/me` com tokens Sanctum.
- [ ] Validar entrada, limitar tentativas de login, revogar tokens no logout e padronizar erros.
- [ ] Configurar CORS para origens explícitas e aplicar a política de expiração definida.

### 5.2 Permissões
- [ ] Implementar Policies e restrições por perfil para pedidos, conversas e administração.
- [ ] Garantir que consultas e resolução de identificadores respeitem o usuário autenticado, inclusive relações aninhadas.
- [ ] Testar login válido/inválido, token ausente/expirado/revogado, cliente versus administrador e acesso cruzado entre clientes.

**Aceite:** autenticação funciona pela API; logout invalida acesso; um cliente não acessa recursos de outro nem rotas administrativas.

**Commit:** `feat: implement authentication and access policies`

## Fase 06 — Base visual e autenticação no frontend

### 6.1 Estrutura da interface
- [ ] Organizar rotas, páginas, componentes, serviços de API, tipos e hooks por responsabilidade.
- [ ] Criar layout responsivo, navegação, componentes básicos, estados de carregamento e tratamento global de erros.
- [ ] Configurar TanStack Query e cliente HTTP com autenticação e tratamento de sessão expirada.

### 6.2 Login e navegação
- [ ] Implementar login com React Hook Form e Zod, credenciais de demonstração visíveis e botão de entrada como cliente demo.
- [ ] Implementar logout, proteção de rotas e navegação por perfil; limpar dados em cache ao encerrar ou trocar sessão.
- [ ] Implementar mensagem de inicialização do servidor com tempo limite e opção de tentar novamente.
- [ ] Testar login, logout, falhas, sessão expirada e navegação protegida.

**Aceite:** cliente demo entra e sai pela interface; sessão segue a política definida; dados de uma sessão não aparecem em outra.

**Commit:** `feat: add application layout and login flow`

## Fase 07 — API de pedidos, dashboard e rastreamento

### 7.1 Consultas
- [ ] Implementar `GET /api/dashboard`, `GET /api/orders` e `GET /api/orders/{order}` com Resources.
- [ ] Implementar busca por número/produto, filtros de status/transportadora/período, ordenação permitida e paginação no backend.
- [ ] Calcular totais e gasto mensal sem pedidos cancelados, aplicando período e fuso definidos.
- [ ] Entregar detalhes, itens, endereço mascarado e eventos em ordem cronológica.

### 7.2 Regras e rastreamento
- [ ] Implementar `CancelOrderAction` e `POST /api/orders/{order}/cancel`, respeitando status e prazo em transação.
- [ ] Criar contrato `TrackingProvider` e implementações simuladas de Correios, Jadlog e Loggi.
- [ ] Testar isolamento, filtros, paginação, agregações, limites de prazo, cancelamento repetido e alterações concorrentes relevantes.

**Aceite:** consultas retornam apenas dados autorizados; valores são coerentes com os seeders; cancelamentos inválidos não alteram o pedido.

**Commit:** `feat: implement order dashboard and tracking API`

## Fase 08 — Interface de pedidos e dashboard

### 8.1 Dashboard e listagem
- [ ] Criar indicadores, gráfico por status, pedidos recentes e acesso ao assistente.
- [ ] Criar listagem responsiva, busca, filtros, paginação e estados de vazio, erro e carregamento.
- [ ] Sincronizar filtros relevantes com a URL e invalidar consultas após mutações.

### 8.2 Detalhes e cancelamento
- [ ] Exibir itens, valores, endereço mascarado, transportadora, código e linha do tempo.
- [ ] Implementar confirmação de cancelamento, envio, feedback e atualização de dashboard/listagem/detalhes.
- [ ] Testar filtros, rastreamento, cancelamento e erros; adicionar fluxo ponta a ponta de consulta e cancelamento.

**Aceite:** fluxo tradicional de pedidos funciona sem IA, em desktop e celular, com dados reais da API local.

**Commit:** `feat: add customer dashboard and order screens`

## Fase 09 — Administração no backend e frontend

### 9.1 API administrativa
- [ ] Implementar listagem, criação e atualização de pedidos, além de inclusão de eventos de rastreamento, nas rotas `/api/admin/orders` do plano.
- [ ] Implementar `UpdateOrderStatusAction`, validação das transições e consistência entre status, datas e eventos.
- [ ] Testar permissões administrativas, payloads inválidos e transições proibidas.

### 9.2 Painel
- [ ] Criar listagem e formulários de criação/edição de pedidos, alteração de status e inclusão de eventos.
- [ ] Exibir validações da API, confirmações apropriadas e feedback de sucesso/erro.
- [ ] Testar o fluxo administrador altera pedido → cliente visualiza atualização; reservar a visualização de logs de IA para a fase 11.

**Aceite:** administrador gerencia pedidos e rastreamento; cliente recebe as alterações e continua sem acesso administrativo.

**Commit:** `feat: implement order administration`

## Fase 10 — Ferramentas e orquestração de IA no backend

### 10.1 Ferramentas autorizadas
- [ ] Implementar `ToolRegistry` e as cinco ferramentas: `get_latest_order`, `get_order_details`, `list_delayed_orders`, `calculate_monthly_spending` e `check_cancellation_eligibility`.
- [ ] Validar argumentos, aplicar contexto do usuário no servidor e devolver somente campos necessários.
- [ ] Manter as ferramentas do MVP somente para consulta; cancelamento permanece no fluxo explícito da interface.

### 10.2 Gemini e conversas
- [ ] Configurar cliente Gemini com modelo por variável de ambiente, limites, timeout e tratamento de indisponibilidade/cota.
- [ ] Implementar `OrderAssistant`: mensagem → solicitação de ferramenta → validação → execução → resultado → resposta, limitando iterações e tamanho do histórico.
- [ ] Persistir conversas, mensagens e logs sanitizados; implementar criação, listagem, detalhes e envio de mensagens com Policies.
- [ ] Implementar `GenerateConversationTitleJob`, processamento local e comportamento de contingência definido para produção.
- [ ] Implementar `GET /api/admin/ai-tool-logs` com paginação e acesso restrito.

### 10.3 Testes
- [ ] Simular respostas do provedor, argumentos inválidos, ferramenta desconhecida, ausência de dados, timeout e excesso de chamadas.
- [ ] Testar acesso cruzado a conversas/pedidos, tentativas de contornar regras e persistência consistente em falhas.
- [ ] Instruir o assistente a responder com dados das ferramentas e admitir informação indisponível; não depender do prompt para autorização.

**Aceite:** testes determinísticos validam as cinco ferramentas e os limites; uma integração controlada com Gemini confirma o contrato sem expor dados de outros usuários.

**Commit:** `feat: implement authorized AI assistant backend`

## Fase 11 — Chat, histórico e integração no frontend

### 11.1 Assistente
- [ ] Criar chat, perguntas sugeridas, nova conversa, histórico, carregamento e mensagens de falha/cota.
- [ ] Exibir links para pedidos retornados pela API, conteúdo renderizado com segurança e aviso de dados fictícios.
- [ ] Evitar envio duplicado; definir nova tentativa sem duplicar mensagens já persistidas e manter pedidos acessíveis quando a IA falhar.

### 11.2 Administração e testes
- [ ] Adicionar consulta paginada de logs de ferramentas no painel administrativo, sem segredos ou argumentos sensíveis.
- [ ] Testar envio, resposta, troca de conversa, histórico vazio, falha e repetição controlada.
- [ ] Adicionar testes ponta a ponta do chat com provedor simulado e realizar verificação manual com integração real.

**Aceite:** usuário consulta seus pedidos em linguagem natural, recupera conversas e entende falhas; administrador consulta logs autorizados.

**Commit:** `feat: add assistant chat history and tool log screens`

## Fase 12 — Revisão integrada e observabilidade

### 12.1 Robustez e segurança
- [ ] Revisar Policies, validações, CORS, limites de requisição, limpeza de sessão e ausência de segredos no código/build/logs.
- [ ] Adicionar logs estruturados com `request_id`, duração de endpoints e de ferramentas, evitando tokens, senhas e dados sensíveis.
- [ ] Revisar consultas para evitar carregamentos repetidos desnecessários e garantir paginação/limites.

### 12.2 Experiência e regressão
- [ ] Revisar teclado, foco, rótulos, contraste, layouts móveis, skeletons e estados vazios/erro.
- [ ] Validar API iniciando, banco indisponível, Gemini indisponível e sessão expirada.
- [ ] Completar regressão de autenticação, isolamento, pedidos, administração e chat; revisar coerência dos dados demo.
- [ ] Executar lint, tipos, testes backend/frontend/ponta a ponta e build; corrigir falhas antes de encerrar.

**Aceite:** fluxos críticos passam no CI e na revisão manual em desktop/celular; falhas externas apresentam respostas controladas e rastreáveis.

**Commit:** `fix: harden application flows and observability`

## Fase 13 — Preparação de produção

### 13.1 Artefatos e operação
- [ ] Criar Dockerfile de produção do backend, validar imagem localmente e configurar processo HTTP, porta, logs e health check.
- [ ] Configurar build do frontend e fallback de rotas da SPA para Vercel.
- [ ] Documentar variáveis: `APP_ENV`, `APP_KEY`, `APP_URL`, `FRONTEND_URL`, `DATABASE_URL`, `GEMINI_API_KEY`, `GEMINI_MODEL`, `LOG_LEVEL` e `VITE_API_URL`, além das necessárias para filas/CORS.
- [ ] Configurar produção sem debug e com credenciais apenas nos provedores apropriados.

### 13.2 Restrições de hospedagem
- [ ] Revalidar condições dos planos e definir execução de migrations, seeders e jobs no provedor escolhido.
- [ ] Não pressupor worker permanente gratuito: documentar e testar a estratégia viável; se o título usar execução síncrona no MVP, registrar o desvio e seu impacto.
- [ ] Preparar seeders de produção explícitos e seguros, política para restaurar dados da demonstração e instruções de recuperação/rollback compatíveis com migrations.
- [ ] Documentar conexão PostgreSQL/TLS, persistência necessária e limitações do ambiente de demonstração.

**Aceite:** imagem e build de produção validados; procedimento de publicação, migrations, filas e recuperação documentado; contas/configurações necessárias identificadas.

**Commit:** `chore: prepare production builds and deployment procedures`

## Fase 14 — Deploy, validação pública e documentação

### 14.1 Publicação
- [ ] Configurar repositório GitHub e integração de deploy com credenciais disponíveis na execução.
- [ ] Criar banco Neon e configurar conexão segura do backend.
- [ ] Publicar API no Render a partir do Dockerfile; configurar variáveis, `APP_KEY`, health check e estratégia de filas.
- [ ] Executar migrations e carga inicial de demonstração explicitamente, sem recriar o banco a cada deploy.
- [ ] Publicar `frontend/` na Vercel com URL da API; configurar CORS com a origem publicada e verificar HTTPS.

### 14.2 Validação pública
- [ ] Verificar saúde, login/logout demo, dashboard, filtros, detalhes, rastreamento, cancelamento e atualização administrativa.
- [ ] Validar isolamento com dois clientes, funcionamento do chat, histórico e visualização administrativa de logs.
- [ ] Testar acesso direto a rotas da SPA, primeiro carregamento após inatividade e tratamento de falhas.
- [ ] Confirmar que segredos não estão no bundle e que logs são suficientes para diagnosticar falhas.

### 14.3 Entrega
- [ ] Finalizar README com URLs, credenciais exclusivas de demo, arquitetura, instalação, comandos, testes, decisões, limitações e melhorias futuras.
- [ ] Adicionar screenshots/GIFs em `docs/screenshots/` e roteiro curto de demonstração.
- [ ] Registrar URLs, data, verificações e limitações conhecidas neste documento; encerrar com o commit previsto e confirmar o deploy resultante desse commit.

**Aceite:** um visitante acessa a aplicação publicada, entra como demo e utiliza os fluxos principais; documentação permite reproduzir o ambiente. Se faltar acesso a uma conta ou configuração externa, registrar a fase como bloqueada, sem declarar o deploy concluído.

**Commit:** `docs: finalize deployed MVP and portfolio guide`

## Registro de execução por fase

Preencher uma entrada ao encerrar cada fase, no mesmo commit da entrega:

```markdown
### Fase NN — Nome
- Status: Concluída | Bloqueada
- Início: AAAA-MM-DD
- Encerramento: AAAA-MM-DD ou pendente
- Entregas: arquivos e comportamentos implementados
- Verificações: comandos executados, resultados e evidências manuais/CI
- Decisões e desvios: alterações em relação ao plano e justificativas
- Consultas críticas: pergunta apresentada, resposta explícita do usuário, data e limites autorizados; ou nenhuma
- Pendências: nenhuma para conclusão, ou impedimentos concretos
- Commit de encerramento: título único previsto/ajustado, ou pendente
- Próxima fase: NN
```

### Fase 01 — Pré-requisitos e decisões técnicas

- Status: Concluída.
- Início e encerramento: 26/09/2026.
- Entregas: `docs/architecture.md` com inventário, compatibilidade, arquitetura, contratos HTTP, regras de negócio e limites públicos dos provedores; atualização deste plano; Git inicializado em `main`, identidade existente validada e `origin` configurado para `https://github.com/italo-vinicius/order-mind.git`.
- Verificações: comandos de versão de Git/Docker/Compose/Node/npm/PHP/Composer; `docker info` confirmou daemon 29.4.0; `git ls-remote origin` retornou código 0, sem referências (remoto vazio); documentação oficial consultada e vinculada em `docs/architecture.md`.
- Decisões: Laravel 13, PHP 8.4.26 e Composer 2.10.3 em contêineres; Node 22.23.3/npm 10.9.9 como alvo da stack; PostgreSQL 17.11 local e major 17 no Neon. PHP global permanece intacto. Pest/Vitest/React Testing Library/Playwright conforme plano. Nenhum pré-requisito de host ausente; instalação dos runtimes isolados, resolução das bibliotecas e build serão executados na fase 02, com testes configurados na fase 03.
- Consultas críticas: D01 aprovada — filas locais, título síncrono em produção com timeout/fallback, sem worker pago; D02 aprovada — token em memória, duas horas, revogação no logout e novo login ao recarregar; D03 respondida — URL do GitHub fornecida; D04 aprovada — BRL, UTC/São Paulo, gasto mensal por `placed_at` sem cancelados, atraso por previsão vencida e cancelamento apenas em `pending_payment`/`processing` até o prazo inclusive. Respostas explícitas recebidas em 26/09/2026.
- Desvios: execução síncrona do título em produção aprovada para manter orçamento zero. Complemento do contrato com `POST /api/conversations`, necessário ao fluxo já previsto.
- Pendências desta fase: nenhuma. Contas, quotas individuais e modelo Gemini serão validados nas fases de integração/deploy; limites públicos consultados não garantem disponibilidade na conta. Disponibilidade de imagens e versões resolvidas será verificada ao instalar a stack.
- Validação de código: não aplicável; entrega documental, sem aplicação instalada. Revisão de integridade do Markdown e conteúdo do commit antes do encerramento.
- Commit de encerramento: `docs: define environment and architecture decisions`.
- Próxima fase: 02 — Monorepo, stack e ambiente local (não iniciada).

## Limites de escopo e conclusão do MVP

Continuam fora do MVP: transportadoras reais, cadastro público, recuperação de senha, pagamentos, WebSockets, aplicativo móvel, múltiplos idiomas, upload de documentos, microsserviços, Kafka e banco vetorial.

O MVP estará concluído quando todas as fases estiverem encerradas com seus commits e os seguintes critérios forem atendidos:

- Aplicação publicada e acessível, com usuário de demonstração funcional e sem exigir cadastro ou configuração do visitante.
- Interface consistente em desktop e celular, com consulta de pedidos, filtros, detalhes e rastreamento fictício.
- Administração capaz de atualizar pedidos e eventos, respeitando as permissões e regras de negócio.
- Assistente respondendo com dados do banco fictício por ferramentas autorizadas, sem expor informações de outros clientes.
- Indisponibilidade da IA e do backend tratada com mensagens claras e recuperação apropriada.
- Testes automatizados aprovados nos fluxos críticos, incluindo isolamento entre usuários.
- Código e documentação públicos no GitHub, com arquitetura, instruções reproduzíveis, URLs, screenshots e limitações.
- Demonstração do projeto e das decisões principais possível em menos de três minutos.

Mudanças de escopo devem ser registradas antes da implementação, com impacto nas fases e nos critérios de aceite.

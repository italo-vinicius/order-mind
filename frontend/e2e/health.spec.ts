import { expect, test } from '@playwright/test'

test('allows the customer demo to log in and log out', async ({ page }) => {
  await page.goto('/login')
  await expect(page.getByText('Conexão disponível', { exact: true })).toBeVisible()

  await page.getByRole('button', { name: 'Entrar como cliente demo' }).click()
  await expect(page.getByRole('heading', { name: 'Seus pedidos' })).toBeVisible()
  await expect(page.getByText('Pedidos por status')).toBeVisible()
  await page.getByText('OM-2026-0002', { exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Linha do tempo' })).toBeVisible()
  await page.getByRole('link', { name: '← Voltar aos pedidos' }).click()

  await page.getByRole('button', { name: 'Sair' }).click()
  await expect(page.getByRole('heading', { name: 'Entrar na demonstração' })).toBeVisible()

  await page.getByRole('button', { name: 'Entrar como cliente demo' }).click()
  await expect(page.getByRole('heading', { name: 'Seus pedidos' })).toBeVisible()
  await page.reload()
  await expect(page.getByRole('heading', { name: 'Entrar na demonstração' })).toBeVisible()
})

test('renders a recoverable unavailable state on the login screen', async ({ page }) => {
  await page.route('**/api/health', async (route) => {
    await route.fulfill({
      status: 503,
      contentType: 'application/json',
      body: JSON.stringify({ data: { status: 'unavailable', database: 'unavailable' } }),
    })
  })

  await page.goto('/login')
  await expect(page.getByRole('button', { name: 'Tentar novamente' })).toBeVisible()
})

test('allows the demo administrator to view and select an order', async ({ page }) => {
  await page.route('**/api/admin/ai-tool-logs**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: [
          {
            id: 1,
            tool_name: 'get_latest_order',
            input: {},
            output: {},
            succeeded: true,
            created_at: '2026-09-26T12:00:00Z',
            user: { id: 1, name: 'Ana Cliente' },
          },
        ],
        links: { next: null, prev: null },
        meta: { current_page: 1, last_page: 1 },
      }),
    })
  })
  await page.goto('/login')
  await page.getByRole('button', { name: 'Entrar como administrador demo' }).click()
  await expect(page.getByRole('heading', { name: 'Gerenciar pedidos' })).toBeVisible()
  await page.getByRole('button', { name: /OM-2026-0002/ }).click()
  await expect(page.getByRole('heading', { name: /^Alterar status:/ })).toBeVisible()
  await page.getByRole('link', { name: 'IA' }).click()
  await expect(page.getByRole('heading', { name: 'Registros de ferramentas de IA' })).toBeVisible()
  await expect(page.getByText('get_latest_order')).toBeVisible()
})

test('renders a simulated assistant response and a link to its order', async ({ page }) => {
  let created = false
  let messages: object[] = []
  await page.route('**/api/conversations**', async (route) => {
    const request = route.request()
    const url = new URL(request.url())
    if (request.method() === 'POST' && url.pathname.endsWith('/conversations')) {
      created = true
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: 44,
            title: 'Nova conversa',
            created_at: '2026-09-26T12:00:00Z',
            updated_at: '2026-09-26T12:00:00Z',
          },
        }),
      })
      return
    }
    if (request.method() === 'POST' && url.pathname.endsWith('/messages')) {
      messages = [
        { id: 1, role: 'user', content: 'Qual é meu pedido?', created_at: '2026-09-26T12:00:00Z' },
        {
          id: 2,
          role: 'assistant',
          content: 'O pedido OM-2026-0002 está em preparação.',
          created_at: '2026-09-26T12:00:01Z',
        },
      ]
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: messages[1] }),
      })
      return
    }
    if (url.pathname.endsWith('/conversations/44')) {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: 44,
            title: 'Nova conversa',
            created_at: '2026-09-26T12:00:00Z',
            updated_at: '2026-09-26T12:00:01Z',
            messages,
          },
        }),
      })
      return
    }
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: created
          ? [
              {
                id: 44,
                title: 'Nova conversa',
                created_at: '2026-09-26T12:00:00Z',
                updated_at: '2026-09-26T12:00:01Z',
              },
            ]
          : [],
        links: { next: null, prev: null },
        meta: { current_page: 1, last_page: 1 },
      }),
    })
  })
  await page.goto('/login')
  await page.getByRole('button', { name: 'Entrar como cliente demo' }).click()
  await page.getByRole('link', { name: 'Assistente' }).click()
  await page.getByRole('button', { name: 'Nova conversa' }).click()
  await page.getByLabel('Sua pergunta').fill('Qual é meu pedido?')
  await page.getByRole('button', { name: 'Enviar' }).click()
  await expect(page.getByText('O pedido')).toBeVisible()
  await expect(page.getByRole('link', { name: 'OM-2026-0002' })).toBeVisible()
})

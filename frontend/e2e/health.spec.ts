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
  await page.goto('/login')
  await page.getByLabel('E-mail').fill('admin@ordermind.test')
  await page.getByLabel('Senha').fill('ordermind-demo')
  await page.getByRole('button', { name: 'Entrar', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Gerenciar pedidos' })).toBeVisible()
  await page.getByRole('button', { name: /OM-2026-0002/ }).click()
  await expect(page.getByRole('heading', { name: 'Alterar status: processing' })).toBeVisible()
})

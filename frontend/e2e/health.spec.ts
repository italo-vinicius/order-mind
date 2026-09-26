import { expect, test } from '@playwright/test'

test('allows the customer demo to log in and log out', async ({ page }) => {
  await page.goto('/login')
  await expect(page.getByText('Conexão disponível', { exact: true })).toBeVisible()

  await page.getByRole('button', { name: 'Entrar como cliente demo' }).click()
  await expect(page.getByRole('heading', { name: 'Seus pedidos' })).toBeVisible()
  await expect(page.getByText('Pedidos por status')).toBeVisible()

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

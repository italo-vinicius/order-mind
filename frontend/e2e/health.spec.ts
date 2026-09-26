import { expect, test } from '@playwright/test'

test('confirms the API and database connection', async ({ page }) => {
  await page.goto('/')

  await expect(page.getByText('Conexão disponível', { exact: true })).toBeVisible()
  await expect(page.getByRole('button', { name: 'Verificar conexão' })).toBeVisible()
})

test('renders a recoverable unavailable state', async ({ page }) => {
  await page.route('**/api/health', async (route) => {
    await route.fulfill({
      status: 503,
      contentType: 'application/json',
      body: JSON.stringify({ data: { status: 'unavailable', database: 'unavailable' } }),
    })
  })

  await page.goto('/')

  await expect(page.getByRole('button', { name: 'Tentar novamente' })).toBeVisible()
})

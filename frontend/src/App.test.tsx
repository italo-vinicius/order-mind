import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { cleanup, render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import { afterEach, describe, expect, it, vi } from 'vitest'
import App from './App'
import * as api from './lib/api'

vi.mock('./lib/api', async (importOriginal) => ({
  ...(await importOriginal<typeof import('./lib/api')>()),
  getHealth: vi.fn(),
  login: vi.fn(),
  logout: vi.fn(),
  authenticatedRequest: vi.fn(),
}))

function renderApp(route = '/') {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter initialEntries={[route]}>
        <App />
      </MemoryRouter>
    </QueryClientProvider>,
  )
}

describe('App', () => {
  afterEach(() => {
    cleanup()
    vi.clearAllMocks()
  })

  it('redirects unauthenticated visitors to login and shows the server connection', async () => {
    vi.mocked(api.getHealth).mockResolvedValue({ status: 'ok', database: 'ok' })
    renderApp()
    expect(
      await screen.findByRole('heading', { name: 'Entrar na demonstração' }),
    ).toBeInTheDocument()
    expect(await screen.findByText('Conexão disponível')).toBeInTheDocument()
  })

  it('logs in through the customer demonstration action', async () => {
    const user = userEvent.setup()
    vi.mocked(api.getHealth).mockResolvedValue({ status: 'ok', database: 'ok' })
    vi.mocked(api.login).mockResolvedValue({
      token: 'token',
      token_expires_at: new Date(Date.now() + 7_200_000).toISOString(),
      user: { id: 1, name: 'Ana Cliente', email: 'ana@ordermind.test', role: 'customer' },
    })
    vi.mocked(api.authenticatedRequest).mockImplementation(async (path, _token, parse) => {
      if (path === '/dashboard') {
        return parse({
          data: {
            total_orders: 1,
            active_orders: 1,
            delayed_orders: 0,
            monthly_spending: '119.90',
            orders_by_status: { processing: 1 },
            recent_orders: [],
          },
        })
      }
      return parse({
        data: [
          {
            id: 1,
            number: 'OM-2026-0002',
            status: 'processing',
            total_amount: '119.90',
            carrier: 'Correios',
            placed_at: '2026-09-25T12:00:00.000000Z',
            estimated_delivery_at: '2026-09-30T12:00:00.000000Z',
            is_delayed: false,
          },
        ],
        links: { next: null, prev: null },
        meta: { current_page: 1, last_page: 1 },
      })
    })
    renderApp('/login')
    await user.click(await screen.findByRole('button', { name: 'Entrar como cliente demo' }))
    expect(await screen.findByRole('heading', { name: 'Seus pedidos' })).toBeInTheDocument()
    expect(await screen.findByText('OM-2026-0002')).toBeInTheDocument()
    expect(api.login).toHaveBeenCalledWith({
      email: 'ana@ordermind.test',
      password: 'ordermind-demo',
    })
  })

  it('renders login errors and allows a health retry', async () => {
    const user = userEvent.setup()
    vi.mocked(api.getHealth)
      .mockRejectedValueOnce(new Error('offline'))
      .mockResolvedValueOnce({ status: 'ok', database: 'ok' })
    vi.mocked(api.login).mockRejectedValue(new api.ApiError(422, 'Credenciais inválidas.'))
    renderApp('/login')
    await user.click(await screen.findByRole('button', { name: 'Tentar novamente' }))
    expect(await screen.findByText('Conexão disponível')).toBeInTheDocument()
    await user.type(screen.getByLabelText('E-mail'), 'ana@ordermind.test')
    await user.type(screen.getByLabelText('Senha'), 'errada')
    await user.click(screen.getByRole('button', { name: 'Entrar' }))
    expect(await screen.findByRole('alert')).toHaveTextContent('Credenciais inválidas.')
  })

  it('filters orders and confirms a cancellation from the tracking screen', async () => {
    const user = userEvent.setup()
    const loginResult = {
      token: 'token',
      token_expires_at: new Date(Date.now() + 7_200_000).toISOString(),
      user: { id: 1, name: 'Ana Cliente', email: 'ana@ordermind.test', role: 'customer' as const },
    }
    const detail = {
      data: {
        id: 1,
        number: 'OM-2026-0002',
        status: 'processing',
        subtotal: '105.00',
        shipping_amount: '14.90',
        discount_amount: '0.00',
        total_amount: '119.90',
        carrier: 'Correios',
        tracking_code: 'OM0000000002BR',
        placed_at: '2026-09-25T12:00:00.000000Z',
        estimated_delivery_at: '2026-09-30T12:00:00.000000Z',
        cancellable_until: '2099-09-30T12:00:00.000000Z',
        delivered_at: null,
        cancelled_at: null,
        is_delayed: false,
        shipping_address: {
          street: 'Rua da Demonstração, 102',
          neighborhood: 'Centro',
          city: 'São Paulo',
          state: 'SP',
          zip_code: '01000***',
        },
        tracking: {
          carrier: 'Correios',
          tracking_code: 'OM0000000002BR',
          latest_event_at: '2026-09-25T13:00:00.000000Z',
        },
        items: {
          data: [
            {
              sku: 'OM-CABO-01',
              product_name: 'Cabo USB-C',
              quantity: 1,
              unit_price: '105.00',
              total_amount: '105.00',
            },
          ],
        },
        tracking_events: {
          data: [
            {
              status: 'processing',
              description: 'Pagamento confirmado.',
              location: 'Centro de distribuição',
              occurred_at: '2026-09-25T13:00:00.000000Z',
            },
          ],
        },
      },
    }
    vi.mocked(api.getHealth).mockResolvedValue({ status: 'ok', database: 'ok' })
    vi.mocked(api.login).mockResolvedValue(loginResult)
    vi.mocked(api.authenticatedRequest).mockImplementation(async (path, _token, parse) => {
      if (path === '/dashboard')
        return parse({
          data: {
            total_orders: 1,
            active_orders: 1,
            delayed_orders: 0,
            monthly_spending: '119.90',
            orders_by_status: { processing: 1 },
            recent_orders: [],
          },
        })
      if (path === '/orders/1' || path === '/orders/1/cancel') return parse(detail)
      return parse({
        data: [
          {
            id: 1,
            number: 'OM-2026-0002',
            status: 'processing',
            total_amount: '119.90',
            carrier: 'Correios',
            placed_at: '2026-09-25T12:00:00.000000Z',
            estimated_delivery_at: '2026-09-30T12:00:00.000000Z',
            is_delayed: false,
          },
        ],
        links: { next: null, prev: null },
        meta: { current_page: 1, last_page: 1 },
      })
    })
    renderApp('/login')
    await user.click(await screen.findByRole('button', { name: 'Entrar como cliente demo' }))
    await user.type(await screen.findByLabelText('Buscar pedido ou produto'), 'cabo')
    await user.click(screen.getByRole('button', { name: 'Buscar' }))
    expect(api.authenticatedRequest).toHaveBeenCalledWith(
      '/orders?q=cabo',
      'token',
      expect.any(Function),
      undefined,
    )
    await user.click(await screen.findByText('OM-2026-0002'))
    expect(await screen.findByRole('heading', { name: 'Linha do tempo' })).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Cancelar pedido' }))
    await user.click(screen.getByRole('button', { name: 'Confirmar cancelamento' }))
    expect(api.authenticatedRequest).toHaveBeenCalledWith(
      '/orders/1/cancel',
      'token',
      expect.any(Function),
      { method: 'POST' },
    )
  })
})

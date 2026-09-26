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
    renderApp('/login')
    await user.click(await screen.findByRole('button', { name: 'Entrar como cliente demo' }))
    expect(await screen.findByText('Sessão iniciada')).toBeInTheDocument()
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
})

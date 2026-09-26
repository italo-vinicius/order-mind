import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { cleanup, render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import { afterEach, describe, expect, it, vi } from 'vitest'
import App from './App'
import * as api from './lib/api'

vi.mock('./lib/api', () => ({ getHealth: vi.fn() }))

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

  it('shows a healthy API connection', async () => {
    vi.mocked(api.getHealth).mockResolvedValue({ status: 'ok', database: 'ok' })

    renderApp()

    expect(await screen.findByText('Conexão disponível')).toBeInTheDocument()
  })

  it('retries a failed health check', async () => {
    const user = userEvent.setup()
    vi.mocked(api.getHealth)
      .mockRejectedValueOnce(new Error('Serviço temporariamente indisponível.'))
      .mockResolvedValueOnce({ status: 'ok', database: 'ok' })

    renderApp()

    await user.click(await screen.findByRole('button', { name: 'Tentar novamente' }))

    expect(await screen.findByText('Conexão disponível')).toBeInTheDocument()
    expect(api.getHealth).toHaveBeenCalledTimes(2)
  })

  it('renders the not-found route', () => {
    vi.mocked(api.getHealth).mockResolvedValue({ status: 'ok', database: 'ok' })

    renderApp('/missing')

    expect(screen.getByRole('heading', { name: 'Página não encontrada' })).toBeInTheDocument()
  })
})

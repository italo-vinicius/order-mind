import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { cleanup, render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { AuthContext } from '@/auth/context'
import { ApiError } from '@/lib/api'
import { AssistantPage } from './AssistantPage'

function renderPage(
  request: <T>(path: string, parse: (payload: unknown) => T, init?: RequestInit) => Promise<T>,
) {
  return render(
    <QueryClientProvider
      client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}
    >
      <MemoryRouter>
        <AuthContext.Provider value={{ session: null, login: vi.fn(), logout: vi.fn(), request }}>
          <AssistantPage />
        </AuthContext.Provider>
      </MemoryRouter>
    </QueryClientProvider>,
  )
}

describe('AssistantPage', () => {
  afterEach(cleanup)

  it('loads the selected conversation from history', async () => {
    const user = userEvent.setup()
    const request = vi.fn<
      (path: string, parse: (payload: unknown) => unknown, init?: RequestInit) => Promise<unknown>
    >(async (path, parse) => {
      if (path === '/conversations') {
        return parse({
          data: [
            {
              id: 3,
              title: 'Pedido mais recente',
              created_at: '2026-09-26T12:00:00Z',
              updated_at: '2026-09-26T12:00:00Z',
            },
            {
              id: 4,
              title: 'Pedidos atrasados',
              created_at: '2026-09-26T11:00:00Z',
              updated_at: '2026-09-26T11:00:00Z',
            },
          ],
          links: { next: null, prev: null },
          meta: { current_page: 1, last_page: 1 },
        })
      }
      return parse({
        data: {
          id: 4,
          title: 'Pedidos atrasados',
          created_at: '2026-09-26T11:00:00Z',
          updated_at: '2026-09-26T11:00:00Z',
          messages: [
            {
              id: 8,
              role: 'assistant',
              content: 'Não há pedidos atrasados.',
              created_at: '2026-09-26T11:01:00Z',
            },
          ],
        },
      })
    })
    const typedRequest = <T,>(path: string, parse: (payload: unknown) => T, init?: RequestInit) =>
      request(path, parse, init) as Promise<T>
    renderPage(typedRequest)

    await user.click(await screen.findByRole('button', { name: 'Pedidos atrasados' }))
    expect(await screen.findByText('Não há pedidos atrasados.')).toBeInTheDocument()
  })

  it('creates a conversation, sends a question and reconciles a provider failure without retrying it', async () => {
    const user = userEvent.setup()
    const requestMock = vi.fn<
      (path: string, parse: (payload: unknown) => unknown, init?: RequestInit) => Promise<unknown>
    >(async (path, parse) => {
      if (path === '/conversations')
        return parse({
          data: [],
          links: { next: null, prev: null },
          meta: { current_page: 1, last_page: 1 },
        })
      if (path === '/conversations/9')
        return parse({
          data: {
            id: 9,
            title: 'Nova conversa',
            created_at: '2026-09-26T12:00:00Z',
            updated_at: '2026-09-26T12:00:00Z',
            messages: [],
          },
        })
      throw new Error(`Unexpected request: ${path}`)
    })
    const request = <T,>(path: string, parse: (payload: unknown) => T, init?: RequestInit) =>
      requestMock(path, parse, init) as Promise<T>
    requestMock.mockImplementationOnce(async (_path, parse: (payload: unknown) => unknown) =>
      parse({
        data: [],
        links: { next: null, prev: null },
        meta: { current_page: 1, last_page: 1 },
      }),
    )
    requestMock.mockImplementationOnce(async (_path, parse: (payload: unknown) => unknown) =>
      parse({
        data: {
          id: 9,
          title: 'Nova conversa',
          created_at: '2026-09-26T12:00:00Z',
          updated_at: '2026-09-26T12:00:00Z',
        },
      }),
    )
    renderPage(request)

    await user.click(await screen.findByRole('button', { name: 'Nova conversa' }))
    expect(
      await screen.findByRole('heading', { name: 'Pergunte sobre seus pedidos' }),
    ).toBeInTheDocument()
    await user.type(screen.getByLabelText('Sua pergunta'), 'Tenho pedidos atrasados?')
    requestMock.mockRejectedValueOnce(
      new ApiError(503, 'O assistente está indisponível no momento.'),
    )
    await user.click(screen.getByRole('button', { name: 'Enviar' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'Sua pergunta pode ter sido registrada',
    )
    expect(screen.getByDisplayValue('Tenho pedidos atrasados?')).toBeInTheDocument()
    expect(requestMock).toHaveBeenCalledWith(
      '/conversations/9/messages',
      expect.any(Function),
      expect.objectContaining({ method: 'POST' }),
    )
  })
})

import { z } from 'zod'

const healthSchema = z.object({
  data: z.object({ status: z.literal('ok'), database: z.literal('ok') }),
})
const userSchema = z.object({
  id: z.number(),
  name: z.string(),
  email: z.string().email(),
  role: z.enum(['admin', 'customer']),
})
const loginSchema = z.object({
  data: z.object({
    token: z.string().min(1),
    token_expires_at: z.string().datetime(),
    user: userSchema,
  }),
})
const currentUserSchema = z.object({ data: z.object({ user: userSchema }) })

export type AuthUser = z.infer<typeof userSchema>
export type LoginCredentials = { email: string; password: string }

export class ApiError extends Error {
  public readonly status: number

  constructor(status: number, message: string) {
    super(message)
    this.name = 'ApiError'
    this.status = status
  }
}

function apiUrl() {
  const value = import.meta.env.VITE_API_URL?.replace(/\/$/, '')
  if (!value) throw new Error('VITE_API_URL não configurada.')
  return value
}

async function request(path: string, init: RequestInit = {}) {
  const response = await fetch(`${apiUrl()}${path}`, {
    ...init,
    headers: { Accept: 'application/json', ...init.headers },
    signal: init.signal
      ? AbortSignal.any([init.signal, AbortSignal.timeout(10_000)])
      : AbortSignal.timeout(10_000),
  })
  if (!response.ok) {
    const payload: unknown = await response.json().catch(() => null)
    const message =
      typeof payload === 'object' && payload !== null && 'message' in payload
        ? String(payload.message)
        : 'Serviço temporariamente indisponível.'
    throw new ApiError(response.status, message)
  }
  return response
}

export async function getHealth(signal?: AbortSignal) {
  return healthSchema.parse(await (await request('/health', { signal })).json()).data
}

export async function login(credentials: LoginCredentials) {
  const response = await request('/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(credentials),
  })
  return loginSchema.parse(await response.json()).data
}

export async function getCurrentUser(token: string) {
  const response = await request('/auth/me', { headers: { Authorization: `Bearer ${token}` } })
  return currentUserSchema.parse(await response.json()).data.user
}

export async function logout(token: string) {
  await request('/auth/logout', { method: 'POST', headers: { Authorization: `Bearer ${token}` } })
}

export async function authenticatedRequest<T>(
  path: string,
  token: string,
  parse: (payload: unknown) => T,
  init: RequestInit = {},
) {
  const response = await request(path, {
    ...init,
    headers: { Authorization: `Bearer ${token}`, ...init.headers },
  })
  return parse(await response.json())
}

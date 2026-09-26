import { z } from 'zod'

const healthSchema = z.object({
  data: z.object({
    status: z.literal('ok'),
    database: z.literal('ok'),
  }),
})

export async function getHealth(signal?: AbortSignal) {
  const apiUrl = import.meta.env.VITE_API_URL?.replace(/\/$/, '')

  if (!apiUrl) {
    throw new Error('VITE_API_URL não configurada.')
  }

  const response = await fetch(`${apiUrl}/health`, {
    headers: { Accept: 'application/json' },
    signal: signal
      ? AbortSignal.any([signal, AbortSignal.timeout(10_000)])
      : AbortSignal.timeout(10_000),
  })

  if (!response.ok) {
    throw new Error('Serviço temporariamente indisponível.')
  }

  return healthSchema.parse(await response.json()).data
}

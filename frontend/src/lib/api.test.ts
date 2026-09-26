import { afterEach, describe, expect, it, vi } from 'vitest'
import { getHealth } from './api'

describe('getHealth', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('returns the validated API health payload', async () => {
    vi.stubGlobal(
      'fetch',
      vi
        .fn()
        .mockResolvedValue(
          new Response(JSON.stringify({ data: { status: 'ok', database: 'ok' } }), { status: 200 }),
        ),
    )

    await expect(getHealth()).resolves.toEqual({ status: 'ok', database: 'ok' })
  })

  it('rejects an unavailable service response', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 503 })))

    await expect(getHealth()).rejects.toThrow('Serviço temporariamente indisponível.')
  })
})

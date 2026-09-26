import { useQueryClient } from '@tanstack/react-query'
import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react'
import {
  ApiError,
  authenticatedRequest,
  login as requestLogin,
  logout as requestLogout,
  type LoginCredentials,
} from '@/lib/api'
import { AuthContext, type Session } from './context'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [session, setSession] = useState<Session | null>(null)
  const queryClient = useQueryClient()
  const clearSession = useCallback(() => {
    setSession(null)
    queryClient.clear()
  }, [queryClient])

  useEffect(() => {
    if (!session) {
      return undefined
    }

    const timeout = window.setTimeout(
      clearSession,
      Math.max(0, new Date(session.expiresAt).getTime() - Date.now()),
    )

    return () => window.clearTimeout(timeout)
  }, [clearSession, session])
  const login = useCallback(async (credentials: LoginCredentials) => {
    const result = await requestLogin(credentials)
    setSession({ token: result.token, user: result.user, expiresAt: result.token_expires_at })
    return result.user
  }, [])
  const logout = useCallback(async () => {
    try {
      if (session) await requestLogout(session.token)
    } finally {
      clearSession()
    }
  }, [clearSession, session])
  const request = useCallback(
    async <T,>(path: string, parse: (payload: unknown) => T, init?: RequestInit) => {
      if (!session) throw new ApiError(401, 'Sua sessão expirou. Entre novamente.')
      try {
        return await authenticatedRequest(path, session.token, parse, init)
      } catch (error) {
        if (error instanceof ApiError && error.status === 401) clearSession()
        throw error
      }
    },
    [clearSession, session],
  )
  const value = useMemo(
    () => ({ session, login, logout, request }),
    [login, logout, request, session],
  )
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

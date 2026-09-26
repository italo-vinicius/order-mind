import { createContext } from 'react'
import type { AuthUser, LoginCredentials } from '@/lib/api'

export type Session = { token: string; user: AuthUser; expiresAt: string }
export type AuthContextValue = {
  session: Session | null
  login: (credentials: LoginCredentials) => Promise<AuthUser>
  logout: () => Promise<void>
  request: <T>(path: string, parse: (payload: unknown) => T, init?: RequestInit) => Promise<T>
}
export const AuthContext = createContext<AuthContextValue | null>(null)

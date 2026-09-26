import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from './useAuth'

export function ProtectedRoute() {
  const { session } = useAuth()
  return session ? <Outlet /> : <Navigate to="/login" replace />
}
export function AdminRoute() {
  const { session } = useAuth()
  return session?.user.role === 'admin' ? <Outlet /> : <Navigate to="/" replace />
}

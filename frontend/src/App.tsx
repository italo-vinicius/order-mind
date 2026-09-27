import { zodResolver } from '@hookform/resolvers/zod'
import { LogOut, Package } from 'lucide-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, Navigate, Outlet, Route, Routes, useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { AdminRoute, ProtectedRoute } from '@/auth/ProtectedRoute'
import { AuthProvider } from '@/auth/AuthProvider'
import { useAuth } from '@/auth/useAuth'
import { ServerStatus } from '@/components/ServerStatus'
import { Button } from '@/components/ui/button'
import { ApiError } from '@/lib/api'
import { DashboardPage } from '@/pages/DashboardPage'
import { AdminOrdersPage } from '@/pages/AdminOrdersPage'
import { OrderDetailPage } from '@/pages/OrderDetailPage'

const credentialsSchema = z.object({
  email: z.string().email('Informe um e-mail válido.'),
  password: z.string().min(1, 'Informe sua senha.'),
})
type Credentials = z.infer<typeof credentialsSchema>

function Brand() {
  return (
    <Link
      to="/"
      className="flex items-center gap-3 text-xl font-semibold tracking-tight"
      aria-label="OrderMind, início"
    >
      <span className="grid size-10 place-items-center rounded-xl bg-[#173c34] text-[#cdf3be]">
        <Package className="size-5" aria-hidden="true" />
      </span>
      OrderMind
    </Link>
  )
}

function LoginPage() {
  const navigate = useNavigate()
  const { login } = useAuth()
  const [error, setError] = useState<string | null>(null)
  const form = useForm<Credentials>({
    resolver: zodResolver(credentialsSchema),
    defaultValues: { email: '', password: '' },
  })
  const submit = async (credentials: Credentials) => {
    setError(null)
    try {
      const user = await login(credentials)
      navigate(user.role === 'admin' ? '/admin' : '/', { replace: true })
    } catch (caught) {
      setError(
        caught instanceof ApiError ? caught.message : 'Não foi possível entrar. Tente novamente.',
      )
    }
  }
  const enterDemo = (email: string) => {
    form.setValue('email', email)
    form.setValue('password', 'ordermind-demo')
    void form.handleSubmit(submit)()
  }
  return (
    <main className="min-h-svh bg-[#f5f5ef] px-6 py-8 text-[#173c34] sm:px-10">
      <header className="mx-auto max-w-5xl">
        <Brand />
      </header>
      <section className="mx-auto grid max-w-5xl gap-10 py-12 lg:grid-cols-[1fr_0.9fr] lg:py-24">
        <div className="self-center">
          <p className="mb-5 text-xs font-semibold uppercase tracking-[0.2em]">
            Acompanhamento de pedidos
          </p>
          <h1 className="max-w-lg text-4xl font-medium leading-tight tracking-tight sm:text-5xl">
            Saiba onde cada pedido está.
          </h1>
          <p className="mt-5 max-w-md text-lg leading-relaxed text-[#52675f]">
            Entre com uma conta de demonstração para acompanhar entregas fictícias em um só lugar.
          </p>
          <div className="mt-8 rounded-2xl border border-[#173c34]/10 bg-white/60 p-5">
            <ServerStatus />
          </div>
        </div>
        <div className="rounded-3xl bg-white p-7 shadow-[0_12px_50px_-30px_#173c3460] sm:p-9">
          <h2 className="text-2xl font-medium">Entrar na demonstração</h2>
          <p className="mt-2 text-sm text-[#52675f]">
            A sessão dura até duas horas e termina ao recarregar a página.
          </p>
          <form className="mt-7 space-y-5" onSubmit={form.handleSubmit(submit)} noValidate>
            <label className="block text-sm font-medium">
              E-mail
              <input
                className="mt-2 w-full rounded-lg border border-[#173c34]/20 px-3 py-2.5 outline-none focus:border-[#173c34]"
                type="email"
                autoComplete="email"
                {...form.register('email')}
              />
            </label>
            {form.formState.errors.email && (
              <p className="-mt-3 text-sm text-red-700">{form.formState.errors.email.message}</p>
            )}
            <label className="block text-sm font-medium">
              Senha
              <input
                className="mt-2 w-full rounded-lg border border-[#173c34]/20 px-3 py-2.5 outline-none focus:border-[#173c34]"
                type="password"
                autoComplete="current-password"
                {...form.register('password')}
              />
            </label>
            {form.formState.errors.password && (
              <p className="-mt-3 text-sm text-red-700">{form.formState.errors.password.message}</p>
            )}
            {error && (
              <p className="rounded-lg bg-red-50 p-3 text-sm text-red-800" role="alert">
                {error}
              </p>
            )}
            <Button className="h-10 w-full" disabled={form.formState.isSubmitting} type="submit">
              {form.formState.isSubmitting ? 'Entrando…' : 'Entrar'}
            </Button>
          </form>
          <div className="mt-7 border-t border-[#173c34]/10 pt-5">
            <p className="text-sm font-medium">Conta de demonstração</p>
            <p className="mt-1 text-sm text-[#52675f]">
              ana@ordermind.test · senha: ordermind-demo
            </p>
            <Button
              className="mt-4"
              variant="outline"
              onClick={() => enterDemo('ana@ordermind.test')}
            >
              Entrar como cliente demo
            </Button>
            <Button
              className="mt-2"
              variant="outline"
              onClick={() => enterDemo('admin@ordermind.test')}
            >
              Entrar como administrador demo
            </Button>
          </div>
        </div>
      </section>
    </main>
  )
}

function AppLayout() {
  const navigate = useNavigate()
  const { logout, session } = useAuth()
  const leave = async () => {
    await logout()
    navigate('/login', { replace: true })
  }
  return (
    <div className="min-h-svh bg-[#f5f5ef] text-[#173c34]">
      <header className="border-b border-[#173c34]/10 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4 sm:px-10">
          <Brand />
          <div className="flex items-center gap-3">
            <span className="hidden text-right text-sm sm:block">
              <strong className="block font-medium">{session?.user.name}</strong>
              <span className="text-xs text-[#52675f]">
                {session?.user.role === 'admin' ? 'Administrador' : 'Cliente'}
              </span>
            </span>
            <Button variant="outline" size="sm" onClick={() => void leave()}>
              <LogOut aria-hidden="true" />
              Sair
            </Button>
          </div>
        </div>
      </header>
      <main className="mx-auto max-w-6xl px-6 py-10 sm:px-10">
        <Outlet />
      </main>
    </div>
  )
}
function ApplicationRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route element={<ProtectedRoute />}>
        <Route element={<AppLayout />}>
          <Route index element={<DashboardPage />} />
          <Route path="orders/:id" element={<OrderDetailPage />} />
          <Route element={<AdminRoute />}>
            <Route path="admin" element={<AdminOrdersPage />} />
          </Route>
        </Route>
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
export default function App() {
  return (
    <AuthProvider>
      <ApplicationRoutes />
    </AuthProvider>
  )
}

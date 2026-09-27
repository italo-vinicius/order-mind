import { useQuery } from '@tanstack/react-query'
import { Bar, BarChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Link, useSearchParams } from 'react-router-dom'
import { useState } from 'react'
import { useAuth } from '@/auth/useAuth'
import { Button } from '@/components/ui/button'
import { LoadingState } from '@/components/LoadingState'
import { parseDashboard, parseOrders } from '@/lib/orders'

const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })
const date = new Intl.DateTimeFormat('pt-BR', {
  dateStyle: 'medium',
  timeZone: 'America/Sao_Paulo',
})
const statuses = [
  ['pending_payment', 'Aguardando pagamento'],
  ['processing', 'Em preparação'],
  ['shipped', 'Enviado'],
  ['out_for_delivery', 'Saiu para entrega'],
  ['delayed', 'Atrasado'],
  ['delivered', 'Entregue'],
  ['cancelled', 'Cancelado'],
]
const carriers = ['Correios', 'Jadlog', 'Loggi']

function labelStatus(status: string) {
  return statuses.find(([value]) => value === status)?.[1] ?? status
}

export function DashboardPage() {
  const { request } = useAuth()
  const [params, setParams] = useSearchParams()
  const [search, setSearch] = useState(params.get('q') ?? '')
  const query = params.toString()
  const dashboard = useQuery({
    queryKey: ['dashboard'],
    queryFn: () => request('/dashboard', parseDashboard),
  })
  const orders = useQuery({
    queryKey: ['orders', query],
    queryFn: () => request(`/orders?${query}`, parseOrders),
  })

  const updateFilter = (name: string, value: string) => {
    const next = new URLSearchParams(params)
    if (value) next.set(name, value)
    else next.delete(name)
    next.delete('page')
    setParams(next)
  }

  if (dashboard.isLoading || orders.isLoading)
    return <LoadingState label="Carregando seus pedidos…" />
  if (dashboard.isError || orders.isError) {
    return <p role="alert">Não foi possível carregar seus pedidos. Tente novamente mais tarde.</p>
  }
  if (!dashboard.data || !orders.data) return null

  const data = dashboard.data
  const list = orders.data
  const chartData = Object.entries(data.orders_by_status).map(([status, total]) => ({
    status: labelStatus(status),
    total,
  }))

  return (
    <section className="space-y-8">
      <div>
        <p className="text-xs font-semibold uppercase tracking-[.2em]">Visão geral</p>
        <h1 className="mt-2 text-4xl font-medium">Seus pedidos</h1>
      </div>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[
          ['Pedidos', data.total_orders],
          ['Em andamento', data.active_orders],
          ['Atrasados', data.delayed_orders],
          ['Neste mês', money.format(Number(data.monthly_spending))],
        ].map(([label, value]) => (
          <article key={String(label)} className="rounded-2xl bg-white p-5 shadow-sm">
            <p className="text-sm text-[#52675f]">{label}</p>
            <strong className="mt-2 block text-2xl">{value}</strong>
          </article>
        ))}
      </div>
      <article className="rounded-2xl bg-white p-5 shadow-sm">
        <h2 className="text-lg font-medium">Pedidos por status</h2>
        <div className="mt-4 h-56" aria-label="Gráfico de pedidos por status">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={chartData} margin={{ top: 5, right: 8, bottom: 5, left: -24 }}>
              <XAxis
                dataKey="status"
                tick={{ fontSize: 11 }}
                interval={0}
                angle={-18}
                textAnchor="end"
                height={58}
              />
              <YAxis allowDecimals={false} />
              <Tooltip formatter={(value) => [value, 'Pedidos']} />
              <Bar dataKey="total" fill="#3b7039" radius={[6, 6, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </article>
      <form
        className="grid gap-3 rounded-2xl bg-white p-4 shadow-sm md:grid-cols-[1fr_auto_auto_auto]"
        onSubmit={(event) => {
          event.preventDefault()
          updateFilter('q', search)
        }}
      >
        <label className="sr-only" htmlFor="order-search">
          Buscar pedido ou produto
        </label>
        <input
          id="order-search"
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          placeholder="Buscar pedido ou produto"
          className="rounded-lg border border-[#173c34]/20 px-3 py-2 outline-none focus:border-[#173c34]"
        />
        <select
          aria-label="Filtrar por status"
          className="rounded-lg border border-[#173c34]/20 px-3 py-2"
          value={params.get('status') ?? ''}
          onChange={(event) => updateFilter('status', event.target.value)}
        >
          <option value="">Todos os status</option>
          {statuses.map(([value, label]) => (
            <option key={value} value={value}>
              {label}
            </option>
          ))}
        </select>
        <select
          aria-label="Filtrar por transportadora"
          className="rounded-lg border border-[#173c34]/20 px-3 py-2"
          value={params.get('carrier') ?? ''}
          onChange={(event) => updateFilter('carrier', event.target.value)}
        >
          <option value="">Todas as transportadoras</option>
          {carriers.map((carrier) => (
            <option key={carrier} value={carrier}>
              {carrier}
            </option>
          ))}
        </select>
        <Button type="submit">Buscar</Button>
      </form>
      <div className="overflow-hidden rounded-2xl bg-white shadow-sm">
        <div className="grid grid-cols-[1fr_auto] gap-4 border-b border-[#173c34]/10 p-4 text-sm font-medium">
          <span>Pedido</span>
          <span>Total</span>
        </div>
        {list.data.length === 0 ? (
          <p className="p-6 text-sm text-[#52675f]">Nenhum pedido encontrado para estes filtros.</p>
        ) : (
          list.data.map((order) => (
            <Link
              key={order.id}
              to={`/orders/${order.id}`}
              className="grid grid-cols-[1fr_auto] gap-4 border-b border-[#173c34]/10 p-4 hover:bg-[#f5f5ef]"
            >
              <span>
                <strong className="block">{order.number}</strong>
                <small className="text-[#52675f]">
                  {labelStatus(order.status)} · {order.carrier ?? 'Transportadora pendente'} ·{' '}
                  {date.format(new Date(order.placed_at))}
                </small>
              </span>
              <strong>{money.format(Number(order.total_amount))}</strong>
            </Link>
          ))
        )}
      </div>
      <div className="flex items-center justify-between">
        <p className="text-sm text-[#52675f]">
          Página {list.meta.current_page} de {list.meta.last_page}
        </p>
        <div className="flex gap-2">
          <Button
            variant="outline"
            disabled={!list.links.prev}
            onClick={() => updateFilter('page', String(list.meta.current_page - 1))}
          >
            Anterior
          </Button>
          <Button
            variant="outline"
            disabled={!list.links.next}
            onClick={() => updateFilter('page', String(list.meta.current_page + 1))}
          >
            Próxima
          </Button>
        </div>
      </div>
    </section>
  )
}

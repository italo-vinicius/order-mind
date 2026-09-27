import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState, type FormEvent } from 'react'
import { useAuth } from '@/auth/useAuth'
import { Button } from '@/components/ui/button'
import { LoadingState } from '@/components/LoadingState'
import { ApiError } from '@/lib/api'
import {
  parseAdminCustomers,
  parseAdminOrder,
  parseAdminOrders,
  type AdminOrder,
} from '@/lib/admin'

const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })
const nextStatuses: Record<string, [string, string][]> = {
  pending_payment: [
    ['processing', 'Em preparação'],
    ['cancelled', 'Cancelado'],
  ],
  processing: [
    ['shipped', 'Enviado'],
    ['cancelled', 'Cancelado'],
  ],
  shipped: [
    ['out_for_delivery', 'Saiu para entrega'],
    ['delayed', 'Atrasado'],
  ],
  out_for_delivery: [
    ['delivered', 'Entregue'],
    ['delayed', 'Atrasado'],
  ],
  delayed: [
    ['out_for_delivery', 'Saiu para entrega'],
    ['delivered', 'Entregue'],
  ],
  delivered: [],
  cancelled: [],
}

function errorMessage(error: unknown) {
  return error instanceof ApiError ? error.message : 'Não foi possível concluir a operação.'
}

function NewOrderForm({ onCreated }: { onCreated: () => void }) {
  const { request } = useAuth()
  const customers = useQuery({
    queryKey: ['admin-customers'],
    queryFn: () => request('/admin/customers', parseAdminCustomers),
  })
  const create = useMutation({
    mutationFn: (body: Record<string, unknown>) =>
      request('/admin/orders', parseAdminOrder, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      }),
    onSuccess: onCreated,
  })
  const [form, setForm] = useState({
    customer: '',
    number: '',
    product: '',
    sku: '',
    quantity: '1',
    unitPrice: '',
    shipping: '0.00',
    discount: '0.00',
    carrier: '',
    trackingCode: '',
    street: '',
    neighborhood: '',
    city: '',
    state: 'SP',
    zipCode: '',
  })
  const set = (key: keyof typeof form, value: string) =>
    setForm((current) => ({ ...current, [key]: value }))
  const submit = (event: FormEvent) => {
    event.preventDefault()
    create.mutate({
      user_id: Number(form.customer),
      number: form.number,
      shipping_amount: form.shipping,
      discount_amount: form.discount,
      carrier: form.carrier || null,
      tracking_code: form.trackingCode || null,
      shipping_address: {
        street: form.street,
        neighborhood: form.neighborhood,
        city: form.city,
        state: form.state,
        zip_code: form.zipCode,
      },
      items: [
        {
          sku: form.sku,
          product_name: form.product,
          quantity: Number(form.quantity),
          unit_price: form.unitPrice,
        },
      ],
    })
  }
  return (
    <article className="rounded-2xl bg-white p-5 shadow-sm">
      <h2 className="text-lg font-medium">Novo pedido</h2>
      <form className="mt-4 grid gap-3 md:grid-cols-2" onSubmit={submit}>
        <select
          required
          aria-label="Cliente"
          value={form.customer}
          onChange={(e) => set('customer', e.target.value)}
          className="rounded-lg border p-2"
        >
          <option value="">Selecione o cliente</option>
          {customers.data?.map((customer) => (
            <option key={customer.id} value={customer.id}>
              {customer.name} — {customer.email}
            </option>
          ))}
        </select>
        <input
          required
          placeholder="Número do pedido"
          value={form.number}
          onChange={(e) => set('number', e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          required
          placeholder="Produto"
          value={form.product}
          onChange={(e) => set('product', e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          required
          placeholder="SKU"
          value={form.sku}
          onChange={(e) => set('sku', e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          required
          min="1"
          type="number"
          aria-label="Quantidade"
          value={form.quantity}
          onChange={(e) => set('quantity', e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          required
          min="0"
          step="0.01"
          type="number"
          aria-label="Valor unitário"
          placeholder="Valor unitário"
          value={form.unitPrice}
          onChange={(e) => set('unitPrice', e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          min="0"
          step="0.01"
          type="number"
          aria-label="Frete"
          placeholder="Frete"
          value={form.shipping}
          onChange={(e) => set('shipping', e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          min="0"
          step="0.01"
          type="number"
          aria-label="Desconto"
          placeholder="Desconto"
          value={form.discount}
          onChange={(e) => set('discount', e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          placeholder="Transportadora"
          value={form.carrier}
          onChange={(e) => set('carrier', e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          placeholder="Código de rastreio"
          value={form.trackingCode}
          onChange={(e) => set('trackingCode', e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          required
          placeholder="Rua e número"
          value={form.street}
          onChange={(e) => set('street', e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          required
          placeholder="Bairro"
          value={form.neighborhood}
          onChange={(e) => set('neighborhood', e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          required
          placeholder="Cidade"
          value={form.city}
          onChange={(e) => set('city', e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          required
          maxLength={2}
          placeholder="UF"
          value={form.state}
          onChange={(e) => set('state', e.target.value.toUpperCase())}
          className="rounded-lg border p-2"
        />
        <input
          required
          placeholder="CEP"
          value={form.zipCode}
          onChange={(e) => set('zipCode', e.target.value)}
          className="rounded-lg border p-2"
        />
        <div className="flex items-center gap-3">
          <Button disabled={create.isPending} type="submit">
            {create.isPending ? 'Criando…' : 'Criar pedido'}
          </Button>
          {create.isError && (
            <p role="alert" className="text-sm text-red-700">
              {errorMessage(create.error)}
            </p>
          )}
        </div>
      </form>
    </article>
  )
}

function OrderEditor({ order, onChanged }: { order: AdminOrder; onChanged: () => void }) {
  const { request } = useAuth()
  const [carrier, setCarrier] = useState(order.carrier ?? '')
  const [trackingCode, setTrackingCode] = useState(order.tracking_code ?? '')
  const [shipping, setShipping] = useState(order.shipping_amount)
  const [discount, setDiscount] = useState(order.discount_amount)
  const [description, setDescription] = useState('')
  const [location, setLocation] = useState('')
  const update = useMutation({
    mutationFn: () =>
      request(`/admin/orders/${order.id}`, parseAdminOrder, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          carrier: carrier || null,
          tracking_code: trackingCode || null,
          shipping_amount: shipping,
          discount_amount: discount,
        }),
      }),
    onSuccess: onChanged,
  })
  const status = useMutation({
    mutationFn: (next: string) =>
      request(`/admin/orders/${order.id}/status`, parseAdminOrder, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: next }),
      }),
    onSuccess: onChanged,
  })
  const event = useMutation({
    mutationFn: () =>
      request(`/admin/orders/${order.id}/tracking-events`, parseAdminOrder, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ description, location: location || null }),
      }),
    onSuccess: () => {
      setDescription('')
      setLocation('')
      onChanged()
    },
  })
  return (
    <article className="space-y-5 rounded-2xl bg-white p-5 shadow-sm">
      <div className="flex items-start justify-between gap-3">
        <div>
          <h2 className="text-lg font-medium">{order.number}</h2>
          <p className="text-sm text-[#52675f]">
            {order.customer.name} · {order.customer.email}
          </p>
        </div>
        <strong>{money.format(Number(order.total_amount))}</strong>
      </div>
      <div className="grid gap-2 sm:grid-cols-2">
        <input
          aria-label="Transportadora"
          value={carrier}
          onChange={(e) => setCarrier(e.target.value)}
          placeholder="Transportadora"
          className="rounded-lg border p-2"
        />
        <input
          aria-label="Código de rastreio"
          value={trackingCode}
          onChange={(e) => setTrackingCode(e.target.value)}
          placeholder="Código"
          className="rounded-lg border p-2"
        />
        <input
          aria-label="Frete"
          type="number"
          min="0"
          step="0.01"
          value={shipping}
          onChange={(e) => setShipping(e.target.value)}
          className="rounded-lg border p-2"
        />
        <input
          aria-label="Desconto"
          type="number"
          min="0"
          step="0.01"
          value={discount}
          onChange={(e) => setDiscount(e.target.value)}
          className="rounded-lg border p-2"
        />
      </div>
      <Button variant="outline" disabled={update.isPending} onClick={() => update.mutate()}>
        Salvar alterações
      </Button>
      {update.isError && (
        <p role="alert" className="text-sm text-red-700">
          {errorMessage(update.error)}
        </p>
      )}
      <div className="border-t pt-4">
        <h3 className="font-medium">Alterar status: {order.status}</h3>
        <div className="mt-3 flex flex-wrap gap-2">
          {nextStatuses[order.status].map(([value, label]) => (
            <Button
              key={value}
              variant="outline"
              disabled={status.isPending}
              onClick={() => status.mutate(value)}
            >
              {label}
            </Button>
          )) || <p className="text-sm text-[#52675f]">Estado terminal.</p>}
        </div>
        {status.isError && (
          <p role="alert" className="mt-2 text-sm text-red-700">
            {errorMessage(status.error)}
          </p>
        )}
      </div>
      <form
        className="border-t pt-4"
        onSubmit={(e) => {
          e.preventDefault()
          event.mutate()
        }}
      >
        <h3 className="font-medium">Adicionar evento</h3>
        <div className="mt-3 grid gap-2 sm:grid-cols-2">
          <input
            required
            aria-label="Descrição do evento"
            placeholder="Descrição"
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            className="rounded-lg border p-2"
          />
          <input
            aria-label="Local do evento"
            placeholder="Local"
            value={location}
            onChange={(e) => setLocation(e.target.value)}
            className="rounded-lg border p-2"
          />
        </div>
        <Button className="mt-3" variant="outline" disabled={event.isPending} type="submit">
          Adicionar evento
        </Button>
        {event.isError && (
          <p role="alert" className="mt-2 text-sm text-red-700">
            {errorMessage(event.error)}
          </p>
        )}
      </form>
      <div className="border-t pt-4">
        <h3 className="font-medium">Eventos</h3>
        <ol className="mt-3 space-y-2 text-sm">
          {order.tracking_events.map((tracking) => (
            <li key={`${tracking.occurred_at}-${tracking.description}`}>
              {tracking.description}{' '}
              <span className="text-[#52675f]">{tracking.location ?? ''}</span>
            </li>
          ))}
        </ol>
      </div>
    </article>
  )
}

export function AdminOrdersPage() {
  const { request } = useAuth()
  const queryClient = useQueryClient()
  const [selected, setSelected] = useState<number | null>(null)
  const orders = useQuery({
    queryKey: ['admin-orders'],
    queryFn: () => request('/admin/orders', parseAdminOrders),
  })
  const detail = useQuery({
    queryKey: ['admin-order', selected],
    queryFn: () => request(`/admin/orders/${selected}`, parseAdminOrder),
    enabled: selected !== null,
  })
  const refresh = () => {
    void queryClient.invalidateQueries({ queryKey: ['admin-orders'] })
    void queryClient.invalidateQueries({ queryKey: ['admin-order', selected] })
  }
  return (
    <section className="space-y-8">
      <div>
        <p className="text-xs font-semibold uppercase tracking-[.2em]">Administração</p>
        <h1 className="mt-2 text-4xl font-medium">Gerenciar pedidos</h1>
      </div>
      <NewOrderForm onCreated={refresh} />
      <div className="grid gap-6 lg:grid-cols-[.8fr_1.2fr]">
        <article className="overflow-hidden rounded-2xl bg-white shadow-sm">
          <h2 className="border-b p-5 text-lg font-medium">Pedidos</h2>
          {orders.isLoading ? (
            <LoadingState label="Carregando pedidos…" />
          ) : orders.isError ? (
            <p role="alert" className="p-5">
              Não foi possível carregar pedidos.
            </p>
          ) : (
            orders.data?.data.map((order) => (
              <button
                key={order.id}
                onClick={() => setSelected(order.id)}
                className="block w-full border-b p-4 text-left hover:bg-[#f5f5ef]"
              >
                <strong className="block">{order.number}</strong>
                <span className="text-sm text-[#52675f]">
                  {order.customer.name} · {order.status}
                </span>
              </button>
            ))
          )}
        </article>
        <div>
          {detail.isLoading && <LoadingState label="Carregando pedido…" />}
          {detail.isError && <p role="alert">Não foi possível carregar o pedido.</p>}
          {detail.data && (
            <OrderEditor key={detail.data.id} order={detail.data} onChanged={refresh} />
          )}
          {!selected && (
            <p className="rounded-2xl bg-white p-5 text-sm text-[#52675f]">
              Selecione um pedido para editar, alterar o status ou adicionar um evento.
            </p>
          )}
        </div>
      </div>
    </section>
  )
}

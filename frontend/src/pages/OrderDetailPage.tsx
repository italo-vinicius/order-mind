import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'
import { useState } from 'react'
import { useAuth } from '@/auth/useAuth'
import { Button } from '@/components/ui/button'
import { ApiError } from '@/lib/api'
import { parseOrderDetail } from '@/lib/orders'

const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })
const dateTime = new Intl.DateTimeFormat('pt-BR', {
  dateStyle: 'medium',
  timeStyle: 'short',
  timeZone: 'America/Sao_Paulo',
})

export function OrderDetailPage() {
  const { id } = useParams()
  const { request } = useAuth()
  const queryClient = useQueryClient()
  const [confirming, setConfirming] = useState(false)
  const order = useQuery({
    queryKey: ['order', id],
    queryFn: () => request(`/orders/${id}`, parseOrderDetail),
    enabled: Boolean(id),
  })
  const cancel = useMutation({
    mutationFn: () => request(`/orders/${id}/cancel`, parseOrderDetail, { method: 'POST' }),
    onSuccess: (updated) => {
      queryClient.setQueryData(['order', id], updated)
      void queryClient.invalidateQueries({ queryKey: ['dashboard'] })
      void queryClient.invalidateQueries({ queryKey: ['orders'] })
      setConfirming(false)
    },
  })

  if (order.isLoading) return <p>Carregando pedido…</p>
  if (order.isError || !order.data) {
    return <p role="alert">Não foi possível carregar este pedido.</p>
  }

  const data = order.data
  const cancellableUntil = data.cancellable_until
  const cancellable =
    ['pending_payment', 'processing'].includes(data.status) &&
    cancellableUntil &&
    new Date(cancellableUntil) >= new Date()

  return (
    <section className="space-y-8">
      <Link to="/" className="text-sm font-medium underline underline-offset-4">
        ← Voltar aos pedidos
      </Link>
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[.2em]">Pedido {data.number}</p>
          <h1 className="mt-2 text-4xl font-medium">
            {data.is_delayed ? 'Entrega em atraso' : data.status}
          </h1>
          <p className="mt-2 text-[#52675f]">
            Feito em {dateTime.format(new Date(data.placed_at))}
          </p>
        </div>
        <strong className="text-2xl">{money.format(Number(data.total_amount))}</strong>
      </div>
      <div className="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
        <div className="space-y-6">
          <article className="rounded-2xl bg-white p-6 shadow-sm">
            <h2 className="text-lg font-medium">Itens</h2>
            <div className="mt-4 space-y-4">
              {data.items.map((item) => (
                <div
                  key={item.sku}
                  className="flex justify-between gap-4 border-b border-[#173c34]/10 pb-4 last:border-0 last:pb-0"
                >
                  <span>
                    <strong className="block">{item.product_name}</strong>
                    <small className="text-[#52675f]">
                      {item.sku} · {item.quantity} unidade(s)
                    </small>
                  </span>
                  <strong>{money.format(Number(item.total_amount))}</strong>
                </div>
              ))}
            </div>
            <dl className="mt-6 space-y-2 border-t border-[#173c34]/10 pt-4 text-sm">
              <div className="flex justify-between">
                <dt>Produtos</dt>
                <dd>{money.format(Number(data.subtotal))}</dd>
              </div>
              <div className="flex justify-between">
                <dt>Frete</dt>
                <dd>{money.format(Number(data.shipping_amount))}</dd>
              </div>
              <div className="flex justify-between">
                <dt>Desconto</dt>
                <dd>-{money.format(Number(data.discount_amount))}</dd>
              </div>
            </dl>
          </article>
          <article className="rounded-2xl bg-white p-6 shadow-sm">
            <h2 className="text-lg font-medium">Linha do tempo</h2>
            <ol className="mt-5 space-y-5 border-l border-[#3b7039] pl-5">
              {data.tracking_events.map((event, index) => (
                <li key={`${event.occurred_at}-${index}`}>
                  <strong className="block">{event.description}</strong>
                  <span className="text-sm text-[#52675f]">
                    {event.location ?? 'Local não informado'} ·{' '}
                    {event.occurred_at
                      ? dateTime.format(new Date(event.occurred_at))
                      : 'Data não informada'}
                  </span>
                </li>
              ))}
            </ol>
          </article>
        </div>
        <aside className="space-y-6">
          <article className="rounded-2xl bg-white p-6 shadow-sm">
            <h2 className="text-lg font-medium">Rastreamento</h2>
            <p className="mt-3 font-medium">{data.carrier ?? 'Aguardando transportadora'}</p>
            <p className="text-sm text-[#52675f]">
              {data.tracking_code ?? 'Código ainda não disponível'}
            </p>
            {data.tracking?.latest_event_at && (
              <p className="mt-4 rounded-lg bg-[#f5f5ef] p-3 text-sm">
                Última atualização em {dateTime.format(new Date(data.tracking.latest_event_at))}
              </p>
            )}
          </article>
          <article className="rounded-2xl bg-white p-6 shadow-sm">
            <h2 className="text-lg font-medium">Entrega</h2>
            <p className="mt-3 text-sm">
              {[data.shipping_address.street, data.shipping_address.neighborhood]
                .filter(Boolean)
                .join(', ')}
            </p>
            <p className="text-sm text-[#52675f]">
              {[data.shipping_address.city, data.shipping_address.state]
                .filter(Boolean)
                .join(' — ')}{' '}
              · {data.shipping_address.zip_code}
            </p>
          </article>
          {cancellable && cancellableUntil && (
            <article className="rounded-2xl border border-red-200 bg-white p-6 shadow-sm">
              <h2 className="text-lg font-medium">Cancelar pedido</h2>
              <p className="mt-2 text-sm text-[#52675f]">
                Disponível até {dateTime.format(new Date(cancellableUntil))}.
              </p>
              {confirming ? (
                <div className="mt-4 space-y-3">
                  <p className="text-sm">Confirma o cancelamento deste pedido?</p>
                  {cancel.isError && (
                    <p role="alert" className="text-sm text-red-700">
                      {cancel.error instanceof ApiError
                        ? cancel.error.message
                        : 'Não foi possível cancelar o pedido.'}
                    </p>
                  )}
                  <div className="flex gap-2">
                    <Button
                      variant="destructive"
                      disabled={cancel.isPending}
                      onClick={() => cancel.mutate()}
                    >
                      {cancel.isPending ? 'Cancelando…' : 'Confirmar cancelamento'}
                    </Button>
                    <Button
                      variant="outline"
                      disabled={cancel.isPending}
                      onClick={() => setConfirming(false)}
                    >
                      Voltar
                    </Button>
                  </div>
                </div>
              ) : (
                <Button className="mt-4" variant="destructive" onClick={() => setConfirming(true)}>
                  Cancelar pedido
                </Button>
              )}
            </article>
          )}
        </aside>
      </div>
    </section>
  )
}

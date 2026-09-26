import { useQuery } from '@tanstack/react-query'
import { ArrowUpRight, Check, LoaderCircle, Package, RefreshCw } from 'lucide-react'
import { Link, Route, Routes } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import { getHealth } from '@/lib/api'

function Home() {
  const health = useQuery({
    queryKey: ['health'],
    queryFn: ({ signal }) => getHealth(signal),
    retry: false,
    staleTime: 30_000,
    refetchOnWindowFocus: false,
  })
  const available = health.isSuccess && !health.isFetching

  return (
    <div className="min-h-svh bg-[#f5f5ef] text-[#173c34]">
      <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-8 sm:px-10">
        <Link to="/" className="flex items-center gap-3 text-xl font-semibold tracking-tight" aria-label="OrderMind, início">
          <span className="grid size-10 place-items-center rounded-xl bg-[#173c34] text-[#cdf3be]">
            <Package className="size-5" aria-hidden="true" />
          </span>
          OrderMind
        </Link>
        <span className="rounded-full border border-[#173c34]/15 px-3 py-1 text-xs font-medium">Em construção</span>
      </header>

      <main className="mx-auto grid max-w-6xl gap-12 px-6 py-16 sm:px-10 sm:py-24 lg:grid-cols-[1.3fr_1fr] lg:items-center">
        <section>
          <p className="mb-6 text-xs font-semibold uppercase tracking-[0.2em]">Menos dúvidas. Mais clareza.</p>
          <h1 className="max-w-xl text-5xl font-medium leading-[1.08] tracking-tight sm:text-6xl">
            Cada pedido.<br />Um lugar para acompanhar.
          </h1>
          <p className="mt-7 max-w-md text-lg leading-relaxed text-[#52675f]">
            Estamos preparando uma forma simples de acompanhar suas entregas e encontrar as respostas que você precisa.
          </p>
          <div className="mt-10 flex items-center gap-2 text-sm font-medium">
            <span className="size-2 rounded-full bg-[#618157]" />
            Uma demonstração com dados fictícios
          </div>
        </section>

        <section aria-labelledby="connection-heading" className="rounded-3xl bg-white p-8 shadow-[0_12px_50px_-30px_#173c3460] sm:p-10">
          <div className="mb-10 flex items-center justify-between">
            <span className="grid size-12 place-items-center rounded-2xl bg-[#ecf3e8]">
              <Package className="size-6" aria-hidden="true" />
            </span>
            <ArrowUpRight className="size-5 text-[#52675f]" aria-hidden="true" />
          </div>
          <h2 id="connection-heading" className="text-2xl font-medium tracking-tight">O primeiro passo está aqui.</h2>
          <p className="mt-3 text-sm leading-relaxed text-[#52675f]">Em breve, seus pedidos, rastreamento e assistente estarão reunidos neste espaço.</p>
          <div role="status" aria-live="polite" className="mt-8 flex items-center gap-3 border-t border-[#173c34]/10 pt-6 text-sm">
            {health.isFetching ? (
              <><LoaderCircle className="size-4 motion-safe:animate-spin" aria-hidden="true" />Conectando ao serviço…</>
            ) : available ? (
              <><Check className="size-4 text-[#3b7039]" aria-hidden="true" />Conexão disponível</>
            ) : (
              <span className="text-amber-800">Não foi possível conectar. Tente novamente em instantes.</span>
            )}
          </div>
          <Button variant="outline" className="mt-5 w-full" disabled={health.isFetching} onClick={() => void health.refetch()}>
            <RefreshCw aria-hidden="true" />
            {health.isError ? 'Tentar novamente' : 'Verificar conexão'}
          </Button>
        </section>
      </main>
      <footer className="mx-auto max-w-6xl px-6 pb-8 text-xs text-[#52675f] sm:px-10">OrderMind · Acompanhamento de pedidos com inteligência.</footer>
    </div>
  )
}

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Home />} />
      <Route path="*" element={<main className="p-10"><h1 className="text-2xl">Página não encontrada</h1><Link className="mt-4 inline-block underline" to="/">Voltar ao início</Link></main>} />
    </Routes>
  )
}

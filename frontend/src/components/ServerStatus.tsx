import { useQuery } from '@tanstack/react-query'
import { Check, LoaderCircle, RefreshCw } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { getHealth } from '@/lib/api'

export function ServerStatus() {
  const health = useQuery({
    queryKey: ['health'],
    queryFn: ({ signal }) => getHealth(signal),
    retry: false,
    staleTime: 30_000,
    refetchOnWindowFocus: false,
  })
  if (health.isFetching)
    return (
      <p
        className="flex items-center gap-2 text-sm text-[#52675f]"
        role="status"
        aria-live="polite"
      >
        <LoaderCircle className="size-4 motion-safe:animate-spin" aria-hidden="true" />
        Iniciando conexão com o servidor…
      </p>
    )
  if (health.isSuccess)
    return (
      <p className="flex items-center gap-2 text-sm text-[#3b7039]" role="status">
        <Check className="size-4" aria-hidden="true" />
        Conexão disponível
      </p>
    )
  return (
    <div className="space-y-3" role="status" aria-live="polite">
      <p className="text-sm text-amber-800">
        Não foi possível iniciar o servidor. Tente novamente.
      </p>
      <Button variant="outline" size="sm" onClick={() => void health.refetch()}>
        <RefreshCw aria-hidden="true" />
        Tentar novamente
      </Button>
    </div>
  )
}

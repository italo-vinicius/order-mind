import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useAuth } from '@/auth/useAuth'
import { Button } from '@/components/ui/button'
import { parseToolLogs } from '@/lib/assistant'

const date = new Intl.DateTimeFormat('pt-BR', {
  dateStyle: 'short',
  timeStyle: 'short',
  timeZone: 'America/Sao_Paulo',
})

export function AdminToolLogsPage() {
  const { request } = useAuth()
  const [page, setPage] = useState(1)
  const logs = useQuery({
    queryKey: ['admin-ai-tool-logs', page],
    queryFn: () => request(`/admin/ai-tool-logs?page=${page}&per_page=15`, parseToolLogs),
  })
  return (
    <section>
      <p className="text-xs font-semibold uppercase tracking-[.2em]">Administração</p>
      <h1 className="mt-2 text-3xl font-medium">Registros de ferramentas de IA</h1>
      <p className="mt-2 text-sm text-[#52675f]">
        Consulta de auditoria sem exibir segredos ou conteúdo de argumentos.
      </p>
      {logs.isLoading ? <p className="mt-8">Carregando registros…</p> : null}
      {logs.isError ? (
        <p className="mt-8" role="alert">
          Não foi possível carregar os registros.
        </p>
      ) : null}
      {logs.data ? (
        <>
          <div className="mt-6 overflow-x-auto rounded-2xl bg-white shadow-sm">
            <table className="w-full min-w-[42rem] text-left text-sm">
              <thead className="border-b border-[#173c34]/10 text-[#52675f]">
                <tr>
                  <th className="p-4">Quando</th>
                  <th className="p-4">Cliente</th>
                  <th className="p-4">Ferramenta</th>
                  <th className="p-4">Resultado</th>
                </tr>
              </thead>
              <tbody>
                {logs.data.data.map((log) => (
                  <tr key={log.id} className="border-b border-[#173c34]/10 last:border-0">
                    <td className="p-4">{date.format(new Date(log.created_at))}</td>
                    <td className="p-4">{log.user?.name ?? 'Conta removida'}</td>
                    <td className="p-4 font-mono text-xs">{log.tool_name}</td>
                    <td className="p-4">
                      <span className={log.succeeded ? 'text-emerald-800' : 'text-red-800'}>
                        {log.succeeded ? 'Concluída' : 'Falhou'}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {logs.data.data.length === 0 ? (
            <p className="mt-5 text-sm text-[#52675f]">Ainda não há registros.</p>
          ) : null}
          <nav className="mt-5 flex gap-3" aria-label="Paginação dos registros">
            <Button
              variant="outline"
              disabled={!logs.data.links.prev}
              onClick={() => setPage((current) => Math.max(1, current - 1))}
            >
              Anterior
            </Button>
            <Button
              variant="outline"
              disabled={!logs.data.links.next}
              onClick={() => setPage((current) => current + 1)}
            >
              Próxima
            </Button>
          </nav>
        </>
      ) : null}
    </section>
  )
}

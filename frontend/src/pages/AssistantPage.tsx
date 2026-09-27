import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Plus, Send } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '@/auth/useAuth'
import { Button } from '@/components/ui/button'
import { ApiError } from '@/lib/api'
import {
  parseAssistantMessage,
  parseConversation,
  parseConversations,
  type AssistantMessage,
  type Conversation,
} from '@/lib/assistant'

const suggestions = [
  'Qual é o status do meu pedido mais recente?',
  'Tenho algum pedido atrasado?',
  'Quanto gastei neste mês?',
  'O pedido OM-2026-0002 pode ser cancelado?',
]

function MessageContent({ content }: { content: string }) {
  const parts = content.split(/(OM-\d{4}-\d{4,})/g)
  return (
    <p className="whitespace-pre-wrap break-words leading-relaxed">
      {parts.map((part, index) =>
        /^OM-\d{4}-\d{4,}$/.test(part) ? (
          <Link
            key={`${part}-${index}`}
            to={`/?q=${encodeURIComponent(part)}`}
            className="font-medium underline"
          >
            {part}
          </Link>
        ) : (
          part
        ),
      )}
    </p>
  )
}

function conversationError(error: unknown) {
  if (error instanceof ApiError && error.status === 503) return error.message
  if (error instanceof ApiError) return error.message
  return 'Não foi possível enviar sua pergunta. Tente atualizar a conversa.'
}

export function AssistantPage() {
  const { request } = useAuth()
  const queryClient = useQueryClient()
  const [selectedId, setSelectedId] = useState<number | null>(null)
  const [content, setContent] = useState('')
  const [sendError, setSendError] = useState<string | null>(null)
  const conversations = useQuery({
    queryKey: ['conversations'],
    queryFn: () => request('/conversations', parseConversations),
  })
  const activeId = selectedId ?? conversations.data?.data[0]?.id ?? null
  const conversation = useQuery({
    queryKey: ['conversation', activeId],
    queryFn: () => request(`/conversations/${activeId}`, parseConversation),
    enabled: activeId !== null,
  })

  const create = useMutation<Conversation, Error, string>({
    mutationFn: () => request('/conversations', parseConversation, { method: 'POST' }),
    onSuccess: (created, initialMessage) => {
      setSelectedId(created.id)
      setContent(initialMessage)
      setSendError(null)
      void queryClient.invalidateQueries({ queryKey: ['conversations'] })
    },
  })
  const send = useMutation({
    mutationFn: (message: string) =>
      request(`/conversations/${activeId}/messages`, parseAssistantMessage, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: message }),
      }),
    onSuccess: () => {
      setContent('')
      setSendError(null)
      void queryClient.invalidateQueries({ queryKey: ['conversations'] })
      void queryClient.invalidateQueries({ queryKey: ['conversation', activeId] })
    },
    onError: (error) => {
      setSendError(conversationError(error))
      // The API may have recorded the user message before the provider failed. Refreshing
      // reconciles that state and prevents an automatic duplicate submission.
      void queryClient.invalidateQueries({ queryKey: ['conversations'] })
      void queryClient.invalidateQueries({ queryKey: ['conversation', activeId] })
    },
  })
  const messages: AssistantMessage[] = conversation.data?.messages ?? []
  const submit = (event: FormEvent) => {
    event.preventDefault()
    const question = content.trim()
    if (!question || activeId === null || send.isPending) return
    send.mutate(question)
  }

  return (
    <section className="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">
      <aside className="rounded-2xl bg-white p-4 shadow-sm">
        <Button className="w-full" onClick={() => create.mutate('')} disabled={create.isPending}>
          <Plus aria-hidden="true" /> {create.isPending ? 'Criando…' : 'Nova conversa'}
        </Button>
        <h2 className="mt-6 text-sm font-medium">Histórico</h2>
        {conversations.isLoading ? (
          <p className="mt-3 text-sm text-[#52675f]">Carregando…</p>
        ) : null}
        {conversations.isError ? (
          <p className="mt-3 text-sm text-red-800" role="alert">
            Não foi possível carregar seu histórico.
          </p>
        ) : null}
        {!conversations.isLoading && conversations.data?.data.length === 0 ? (
          <p className="mt-3 text-sm text-[#52675f]">
            Comece uma conversa para consultar seus pedidos.
          </p>
        ) : null}
        <div className="mt-3 space-y-1">
          {conversations.data?.data.map((item) => (
            <button
              key={item.id}
              type="button"
              onClick={() => {
                setSelectedId(item.id)
                setSendError(null)
              }}
              className={`w-full rounded-lg px-3 py-2 text-left text-sm ${activeId === item.id ? 'bg-[#e5f3dd] font-medium' : 'hover:bg-[#f5f5ef]'}`}
            >
              {item.title}
            </button>
          ))}
        </div>
      </aside>
      <div className="min-w-0 rounded-2xl bg-white p-5 shadow-sm sm:p-7">
        <p className="text-xs font-semibold uppercase tracking-[.2em]">Assistente</p>
        <h1 className="mt-2 text-3xl font-medium">Pergunte sobre seus pedidos</h1>
        <p className="mt-2 text-sm text-[#52675f]">
          Use apenas dados fictícios desta demonstração. As respostas podem falhar quando a cota
          gratuita estiver indisponível.
        </p>
        {activeId === null ? (
          <div className="mt-8 rounded-xl bg-[#f5f5ef] p-5">
            <p className="font-medium">Como o assistente pode ajudar?</p>
            <div className="mt-4 flex flex-wrap gap-2">
              {suggestions.map((suggestion) => (
                <Button
                  key={suggestion}
                  variant="outline"
                  onClick={() => create.mutate(suggestion)}
                >
                  {suggestion}
                </Button>
              ))}
            </div>
          </div>
        ) : (
          <>
            <div className="mt-7 min-h-72 space-y-4" aria-live="polite">
              {conversation.isLoading ? (
                <p className="text-sm text-[#52675f]">Carregando mensagens…</p>
              ) : null}
              {conversation.isError ? (
                <p role="alert">Não foi possível carregar esta conversa.</p>
              ) : null}
              {!conversation.isLoading && messages.length === 0 ? (
                <div className="rounded-xl bg-[#f5f5ef] p-5">
                  <p className="font-medium">O que você quer saber?</p>
                  <div className="mt-4 flex flex-wrap gap-2">
                    {suggestions.map((suggestion) => (
                      <Button
                        key={suggestion}
                        variant="outline"
                        onClick={() => setContent(suggestion)}
                      >
                        {suggestion}
                      </Button>
                    ))}
                  </div>
                </div>
              ) : null}
              {messages.map((message) => (
                <article
                  key={message.id}
                  className={`max-w-2xl rounded-2xl p-4 text-sm ${message.role === 'user' ? 'ml-auto bg-[#173c34] text-white' : 'bg-[#f5f5ef]'}`}
                >
                  <p className="mb-2 text-xs font-semibold uppercase tracking-wide opacity-70">
                    {message.role === 'user' ? 'Você' : 'OrderMind'}
                  </p>
                  <MessageContent content={message.content} />
                </article>
              ))}
              {send.isPending ? (
                <p className="text-sm text-[#52675f]">Consultando seus pedidos…</p>
              ) : null}
            </div>
            {sendError ? (
              <div className="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-950" role="alert">
                <p>{sendError}</p>
                <p className="mt-1">
                  Sua pergunta pode ter sido registrada. Atualize a conversa antes de enviar outra
                  mensagem para não duplicá-la.
                </p>
                <Button
                  className="mt-3"
                  variant="outline"
                  onClick={() => void conversation.refetch()}
                >
                  Atualizar conversa
                </Button>
              </div>
            ) : null}
            <form className="mt-6 flex gap-3" onSubmit={submit}>
              <label className="sr-only" htmlFor="assistant-message">
                Sua pergunta
              </label>
              <textarea
                id="assistant-message"
                value={content}
                onChange={(event) => setContent(event.target.value)}
                maxLength={1200}
                rows={2}
                placeholder="Ex.: Qual é o status do meu pedido mais recente?"
                className="min-w-0 flex-1 rounded-xl border border-[#173c34]/20 p-3 outline-none focus:border-[#173c34]"
              />
              <Button type="submit" disabled={!content.trim() || send.isPending}>
                <Send aria-hidden="true" /> Enviar
              </Button>
            </form>
          </>
        )}
      </div>
    </section>
  )
}

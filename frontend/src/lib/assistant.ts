import { z } from 'zod'

const messageSchema = z.object({
  id: z.number(),
  role: z.enum(['user', 'assistant']),
  content: z.string(),
  created_at: z.string(),
})

const conversationSchema = z.object({
  id: z.number(),
  title: z.string(),
  created_at: z.string(),
  updated_at: z.string(),
  messages: z.array(messageSchema).optional(),
})

const paginationSchema = z.object({
  current_page: z.number(),
  last_page: z.number(),
})

const conversationsSchema = z.object({
  data: z.array(conversationSchema),
  links: z.object({ next: z.string().nullable(), prev: z.string().nullable() }),
  meta: paginationSchema,
})

const toolLogSchema = z.object({
  id: z.number(),
  tool_name: z.string(),
  input: z.unknown().nullable(),
  output: z.unknown().nullable(),
  succeeded: z.boolean(),
  created_at: z.string(),
  user: z.object({ id: z.number(), name: z.string() }).nullable().optional(),
})

const toolLogsSchema = z.object({
  data: z.array(toolLogSchema),
  links: z.object({ next: z.string().nullable(), prev: z.string().nullable() }),
  meta: paginationSchema,
})

export type AssistantMessage = z.infer<typeof messageSchema>
export type Conversation = z.infer<typeof conversationSchema>
export type ToolLog = z.infer<typeof toolLogSchema>

export const parseConversations = (payload: unknown) => conversationsSchema.parse(payload)
export const parseConversation = (payload: unknown) =>
  z.object({ data: conversationSchema }).parse(payload).data
export const parseAssistantMessage = (payload: unknown) =>
  z.object({ data: messageSchema }).parse(payload).data
export const parseToolLogs = (payload: unknown) => toolLogsSchema.parse(payload)

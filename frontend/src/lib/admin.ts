import { z } from 'zod'

const customerSchema = z.object({ id: z.number(), name: z.string(), email: z.string().email() })
const itemSchema = z.object({
  sku: z.string(),
  product_name: z.string(),
  quantity: z.number(),
  unit_price: z.string(),
  total_amount: z.string(),
})
const eventSchema = z.object({
  status: z.string().nullable(),
  description: z.string(),
  location: z.string().nullable(),
  occurred_at: z.string().nullable(),
})
const adminOrderSchema = z.object({
  id: z.number(),
  number: z.string(),
  status: z.string(),
  customer: customerSchema,
  subtotal: z.string(),
  shipping_amount: z.string(),
  discount_amount: z.string(),
  total_amount: z.string(),
  carrier: z.string().nullable(),
  tracking_code: z.string().nullable(),
  shipping_address: z.object({
    street: z.string(),
    neighborhood: z.string(),
    city: z.string(),
    state: z.string(),
    zip_code: z.string(),
  }),
  placed_at: z.string(),
  estimated_delivery_at: z.string().nullable(),
  cancellable_until: z.string().nullable(),
  delivered_at: z.string().nullable(),
  cancelled_at: z.string().nullable(),
  items: z.array(itemSchema),
  tracking_events: z.array(eventSchema),
})
const customersSchema = z.object({ data: z.array(customerSchema) })
const ordersSchema = z.object({
  data: z.array(adminOrderSchema),
  links: z.object({ next: z.string().nullable(), prev: z.string().nullable() }),
  meta: z.object({ current_page: z.number(), last_page: z.number() }),
})

export type AdminCustomer = z.infer<typeof customerSchema>
export type AdminOrder = z.infer<typeof adminOrderSchema>

export const parseAdminCustomers = (payload: unknown) => customersSchema.parse(payload).data
export const parseAdminOrders = (payload: unknown) => ordersSchema.parse(payload)
export const parseAdminOrder = (payload: unknown) =>
  z.object({ data: adminOrderSchema }).parse(payload).data

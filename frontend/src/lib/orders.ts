import { z } from 'zod'

const orderSchema = z.object({
  id: z.number(),
  number: z.string(),
  status: z.string(),
  total_amount: z.string(),
  carrier: z.string().nullable(),
  placed_at: z.string(),
  estimated_delivery_at: z.string().nullable(),
  is_delayed: z.boolean(),
})

const orderDetailSchema = orderSchema.extend({
  subtotal: z.string(),
  shipping_amount: z.string(),
  discount_amount: z.string(),
  tracking_code: z.string().nullable(),
  cancellable_until: z.string().nullable().optional(),
  delivered_at: z.string().nullable(),
  cancelled_at: z.string().nullable(),
  shipping_address: z.object({
    street: z.string().nullable().optional(),
    neighborhood: z.string().nullable().optional(),
    city: z.string().nullable().optional(),
    state: z.string().nullable().optional(),
    zip_code: z.string().nullable().optional(),
  }),
  tracking: z
    .object({
      carrier: z.string(),
      tracking_code: z.string().nullable(),
      latest_event_at: z.string().nullable(),
    })
    .nullable(),
  items: z.object({
    data: z.array(
      z.object({
        sku: z.string(),
        product_name: z.string(),
        quantity: z.number(),
        unit_price: z.string(),
        total_amount: z.string(),
      }),
    ),
  }),
  tracking_events: z.object({
    data: z.array(
      z.object({
        status: z.string().nullable(),
        description: z.string(),
        location: z.string().nullable(),
        occurred_at: z.string().nullable(),
      }),
    ),
  }),
})

const dashboardSchema = z.object({
  data: z.object({
    total_orders: z.number(),
    active_orders: z.number(),
    delayed_orders: z.number(),
    monthly_spending: z.string(),
    orders_by_status: z.record(z.string(), z.number()),
    recent_orders: z.array(orderSchema),
  }),
})

const ordersSchema = z.object({
  data: z.array(orderSchema),
  links: z.object({ next: z.string().nullable(), prev: z.string().nullable() }),
  meta: z.object({ current_page: z.number(), last_page: z.number() }),
})

export type Order = z.infer<typeof orderSchema>
export type OrderDetail = z.infer<typeof orderDetailSchema>

export const parseDashboard = (payload: unknown) => dashboardSchema.parse(payload).data
export const parseOrders = (payload: unknown) => ordersSchema.parse(payload)
export const parseOrderDetail = (payload: unknown) =>
  z.object({ data: orderDetailSchema }).parse(payload).data

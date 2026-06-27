import client from './client'

export interface Notification {
  id: number
  user_id: number
  type: string
  title: string
  body: string | null
  data: Record<string, unknown> | null
  read_at: string | null
  created_at: string
}

export interface NotificationsResponse {
  notifications: Notification[]
  unread: number
}

export async function getNotifications(): Promise<NotificationsResponse> {
  const { data } = await client.get<NotificationsResponse>('/notifications')
  return data
}

export async function markAllRead(): Promise<void> {
  await client.post('/notifications/read-all')
}

export async function markOneRead(id: number): Promise<void> {
  await client.patch(`/notifications/${id}`, { read_at: new Date().toISOString() })
}

import client from './client'

export interface ContactRequest {
  id: number
  requester_id: number
  requestee_id: number
  service_id: number | null
  need_id: number | null
  message: string
  response_message: string | null
  status: 'pending' | 'accepted' | 'rejected' | 'cancelled' | 'expired' | 'closed'
  expires_at: string | null
  created_at: string
  requester?: { id: number; name: string; last_name: string | null; email?: string; phone?: string; whatsapp?: string }
  requestee?: { id: number; name: string; last_name: string | null; email?: string; phone?: string; whatsapp?: string }
}

export interface PaginatedContactRequests {
  data: ContactRequest[]
  current_page: number
  last_page: number
  total: number
}

export async function getContactRequests(params?: { direction?: 'sent' | 'received'; status?: string }): Promise<PaginatedContactRequests> {
  const { data } = await client.get<PaginatedContactRequests>('/contact-requests', { params })
  return data
}

export async function createContactRequest(payload: { requestee_id: number; service_id?: number; need_id?: number; message: string }): Promise<ContactRequest> {
  const { data } = await client.post<ContactRequest>('/contact-requests', payload)
  return data
}

export async function acceptContactRequest(id: number, response_message?: string): Promise<ContactRequest> {
  const { data } = await client.post<ContactRequest>(`/contact-requests/${id}/accept`, { response_message })
  return data
}

export async function rejectContactRequest(id: number, response_message?: string): Promise<ContactRequest> {
  const { data } = await client.post<ContactRequest>(`/contact-requests/${id}/reject`, { response_message })
  return data
}

export async function cancelContactRequest(id: number): Promise<ContactRequest> {
  const { data } = await client.post<ContactRequest>(`/contact-requests/${id}/cancel`)
  return data
}

export async function closeContactRequest(id: number): Promise<ContactRequest> {
  const { data } = await client.post<ContactRequest>(`/contact-requests/${id}/close`)
  return data
}

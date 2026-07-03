import client from './client'

export interface ContactRequest {
  id: number
  requester_id: number
  requestee_id: number | null
  service_id: number | null
  need_id: number | null
  message: string
  shared_fields: SharedContactField[]
  reason_type: ContactReasonType
  source_context: Record<string, unknown> | null
  response_message: string | null
  status: 'pending' | 'info_requested' | 'accepted' | 'rejected' | 'cancelled' | 'expired' | 'closed'
  expires_at: string | null
  created_at: string
  requester?: ContactPerson
  requestee?: ContactPerson
}

export type SharedContactField = 'identity' | 'email' | 'phone' | 'whatsapp' | 'workshop' | 'profession'
export type ContactReasonType = 'offer_publication' | 'need_publication' | 'profession_search' | 'workshop_location_search' | 'other'

export interface ContactPerson {
  id: number | null
  name: string
  last_name: string | null
  email?: string
  phone?: string
  whatsapp?: string
  profession?: string
  anonymous?: boolean
  principal_workshop?: { id: number; name: string; number: number } | null
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

export async function createContactRequest(payload: { requestee_id?: number; service_id?: number; need_id?: number; message: string; shared_fields?: SharedContactField[]; reason_type: ContactReasonType; source_context?: Record<string, unknown>; source?: 'search' | 'publications' }): Promise<ContactRequest> {
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

export async function requestContactInfo(id: number, response_message: string): Promise<ContactRequest> {
  const { data } = await client.post<ContactRequest>(`/contact-requests/${id}/request-info`, { response_message })
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

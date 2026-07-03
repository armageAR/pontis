import client from './client'

export interface ChangeRequest {
  id: number
  user_id: number
  field: 'name' | 'last_name' | 'dni' | 'masonic_id'
  current_value: string | null
  new_value: string
  reason: string | null
  status: 'pending' | 'approved' | 'rejected' | 'requires_info' | 'cancelled_by_user'
  reviewer_notes: string | null
  reviewed_at: string | null
  created_at: string
  user?: { id: number; name: string; last_name: string | null; email: string }
}

export interface PaginatedChangeRequests {
  data: ChangeRequest[]
  current_page: number
  last_page: number
  total: number
}

export async function getChangeRequests(params?: { status?: string; mine?: boolean }): Promise<PaginatedChangeRequests> {
  const { data } = await client.get<PaginatedChangeRequests>('/change-requests', { params })
  return data
}

export async function createChangeRequest(payload: { field: string; new_value: string; reason?: string }): Promise<ChangeRequest> {
  const { data } = await client.post<ChangeRequest>('/change-requests', payload)
  return data
}

export async function approveChangeRequest(id: number, reviewer_notes?: string): Promise<ChangeRequest> {
  const { data } = await client.post<ChangeRequest>(`/change-requests/${id}/approve`, { reviewer_notes })
  return data
}

export async function rejectChangeRequest(id: number, reviewer_notes?: string): Promise<ChangeRequest> {
  const { data } = await client.post<ChangeRequest>(`/change-requests/${id}/reject`, { reviewer_notes })
  return data
}

export async function requireInfoChangeRequest(id: number, reviewer_notes: string): Promise<ChangeRequest> {
  const { data } = await client.post<ChangeRequest>(`/change-requests/${id}/require-info`, { reviewer_notes })
  return data
}

export async function cancelChangeRequest(id: number): Promise<ChangeRequest> {
  const { data } = await client.post<ChangeRequest>(`/change-requests/${id}/cancel`)
  return data
}

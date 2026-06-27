import client from './client'
import type { ServiceCategory } from './services'

export interface Need {
  id: number
  user_id: number
  service_category_id: number | null
  title: string
  description: string | null
  location: string | null
  urgency: 'low' | 'medium' | 'high' | null
  visibility: 'private' | 'workshop' | 'my_workshops' | 'registered' | 'anonymous'
  status: 'draft' | 'open' | 'searching' | 'with_matches' | 'contact_requested' | 'linked' | 'closed' | 'cancelled'
  category?: ServiceCategory
  created_at: string
}

export interface NeedFilters {
  status?: string
  page?: number
}

export interface PaginatedNeeds {
  data: Need[]
  current_page: number
  last_page: number
  total: number
}

export async function getNeeds(filters: NeedFilters = {}): Promise<PaginatedNeeds> {
  const { data } = await client.get<PaginatedNeeds>('/needs', { params: filters })
  return data
}

export async function createNeed(payload: Partial<Need>): Promise<Need> {
  const { data } = await client.post<Need>('/needs', payload)
  return data
}

export async function updateNeed(id: number, payload: Partial<Need>): Promise<Need> {
  const { data } = await client.patch<Need>(`/needs/${id}`, payload)
  return data
}

export async function deleteNeed(id: number): Promise<void> {
  await client.delete(`/needs/${id}`)
}

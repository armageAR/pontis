import client from './client'

export interface Workshop {
  id: number
  zone_number: number | null
  zone_name: string | null
  name: string
  number: number
  work_day: string | null
  work_frequency: string | null
  address: string | null
  city: string | null
  province: string | null
  country: string
  language: string | null
  status: 'active' | 'disabled'
  notes: string | null
  created_at: string
  updated_at: string
  is_member: boolean
  is_pending: boolean
  my_role: 'admin' | 'member' | null
}

export interface WorkshopFormData {
  zone_number?: number | null
  zone_name?: string | null
  name: string
  number: number
  work_day?: string | null
  work_frequency?: string | null
  address?: string | null
  city?: string | null
  province?: string | null
  country?: string | null
  language?: string | null
  status?: string | null
  notes?: string | null
}

export interface WorkshopFilters {
  search?: string
  zone_number?: number
  status?: string
  work_day?: string
  city?: string
  province?: string
  per_page?: number
  page?: number
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
  my_workshops_only?: boolean
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface WorkshopSearchResult {
  id: number
  name: string
  number: number
  zone_name: string | null
  city: string | null
}

export async function searchWorkshopsPublic(q: string): Promise<WorkshopSearchResult[]> {
  const { data } = await client.get<WorkshopSearchResult[]>('/workshops/search', { params: { q } })
  return data
}

export async function listWorkshops(filters: WorkshopFilters = {}): Promise<PaginatedResponse<Workshop>> {
  const params = Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== undefined && v !== '' && v !== false),
  )
  const { data } = await client.get<PaginatedResponse<Workshop>>('/admin/workshops', { params })
  return data
}

export async function getWorkshop(id: number): Promise<Workshop> {
  const { data } = await client.get<{ data: Workshop }>(`/admin/workshops/${id}`)
  return data.data
}

export async function createWorkshop(payload: WorkshopFormData): Promise<Workshop> {
  const { data } = await client.post<{ data: Workshop }>('/admin/workshops', payload)
  return data.data
}

export async function updateWorkshop(id: number, payload: Partial<WorkshopFormData>): Promise<Workshop> {
  const { data } = await client.patch<{ data: Workshop }>(`/admin/workshops/${id}`, payload)
  return data.data
}

export async function deleteWorkshop(id: number): Promise<void> {
  await client.delete(`/admin/workshops/${id}`)
}

export async function disableWorkshop(id: number): Promise<Workshop> {
  const { data } = await client.post<{ data: Workshop }>(`/admin/workshops/${id}/disable`)
  return data.data
}

export async function enableWorkshop(id: number): Promise<Workshop> {
  const { data } = await client.post<{ data: Workshop }>(`/admin/workshops/${id}/enable`)
  return data.data
}

export async function joinWorkshop(id: number): Promise<Workshop> {
  const { data } = await client.post<{ data: Workshop }>(`/admin/workshops/${id}/join`)
  return data.data
}

export async function leaveWorkshop(id: number): Promise<Workshop> {
  const { data } = await client.delete<{ data: Workshop }>(`/admin/workshops/${id}/leave`)
  return data.data
}

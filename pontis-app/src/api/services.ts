import client from './client'

export interface ServiceCategory {
  id: number
  name: string
  description: string | null
}

export interface Service {
  id: number
  user_id: number
  service_category_id: number | null
  title: string
  description: string | null
  modality: 'presencial' | 'remoto' | 'both'
  location: string | null
  availability: string | null
  conditions: string | null
  visibility: 'private' | 'workshop' | 'my_workshops' | 'registered' | 'anonymous'
  status: 'draft' | 'active' | 'paused' | 'hidden' | 'disabled'
  category?: ServiceCategory
  created_at: string
}

export interface ServiceFilters {
  status?: string
  page?: number
}

export interface PaginatedServices {
  data: Service[]
  current_page: number
  last_page: number
  total: number
}

export async function getCategories(): Promise<ServiceCategory[]> {
  const { data } = await client.get<ServiceCategory[]>('/service-categories')
  return data
}

export async function getServices(filters: ServiceFilters = {}): Promise<PaginatedServices> {
  const { data } = await client.get<PaginatedServices>('/services', { params: filters })
  return data
}

export async function createService(payload: Partial<Service>): Promise<Service> {
  const { data } = await client.post<Service>('/services', payload)
  return data
}

export async function updateService(id: number, payload: Partial<Service>): Promise<Service> {
  const { data } = await client.patch<Service>(`/services/${id}`, payload)
  return data
}

export async function deleteService(id: number): Promise<void> {
  await client.delete(`/services/${id}`)
}

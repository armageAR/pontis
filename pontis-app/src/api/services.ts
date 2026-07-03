import client from './client'

export interface ServiceCategory {
  id: number
  name: string
  description: string | null
}

export type PublicationStatus = 'draft' | 'active' | 'suspended' | 'expired'

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
  status: PublicationStatus
  effective_status: PublicationStatus
  published_at: string | null
  expires_at: string | null
  category?: ServiceCategory
  created_at: string
}

export interface ServiceInput {
  title: string
  description: string
  service_category_id: number | null
  modality: string
  location: string
  availability: string
  conditions: string
  visibility: string
  publish: boolean
  validity_days?: number | null
  preview_confirmed?: boolean
}

export interface ServiceFilters {
  status?: string
  page?: number
  per_page?: number
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

export async function createService(payload: ServiceInput): Promise<Service> {
  const { data } = await client.post<Service>('/services', payload)
  return data
}

export async function updateService(id: number, payload: ServiceInput): Promise<Service> {
  const { data } = await client.patch<Service>(`/services/${id}`, payload)
  return data
}

export async function deleteService(id: number): Promise<void> {
  await client.delete(`/services/${id}`)
}

export async function suspendService(id: number): Promise<Service> {
  const { data } = await client.post<Service>(`/services/${id}/suspend`, {})
  return data
}

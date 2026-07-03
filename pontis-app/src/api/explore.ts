import client from './client'
import type { ServiceCategory } from './services'

export interface ExploreServiceUser {
  id: number | null
  name: string
  last_name: string | null
  profession: string | null
  locality: string | null
  province: string | null
  anonymous?: boolean
}

export interface ExploreService {
  id: number
  user_id: number | null
  service_category_id: number | null
  title: string
  description: string | null
  modality: string
  location: string | null
  availability: string | null
  visibility: string
  user?: ExploreServiceUser
  category?: ServiceCategory
  created_at: string
}

export interface ExploreNeedUser {
  id: number | null
  name: string
  last_name: string | null
  locality: string | null
  province: string | null
  anonymous?: boolean
}

export interface ExploreNeed {
  id: number
  user_id: number | null
  service_category_id: number | null
  title: string
  description: string | null
  location: string | null
  urgency: string | null
  visibility: string
  status: string
  user?: ExploreNeedUser
  category?: ServiceCategory
  created_at: string
}

export interface ExploreFilters {
  q?: string
  category_id?: number
  scope?: 'my_workshop' | 'my_workshops' | 'all'
  province?: string
  locality?: string
  page?: number
}

export interface PaginatedExploreServices {
  data: ExploreService[]
  current_page: number
  last_page: number
  total: number
}

export interface PaginatedExploreNeeds {
  data: ExploreNeed[]
  current_page: number
  last_page: number
  total: number
}

export async function exploreServices(filters: ExploreFilters = {}): Promise<PaginatedExploreServices> {
  const { data } = await client.get<PaginatedExploreServices>('/explore/services', { params: filters })
  return data
}

export async function exploreNeeds(filters: ExploreFilters = {}): Promise<PaginatedExploreNeeds> {
  const { data } = await client.get<PaginatedExploreNeeds>('/explore/needs', { params: filters })
  return data
}

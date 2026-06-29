import client from './client'

export interface Person {
  id: number
  name: string
  last_name: string | null
  masonic_id: string | null
  masonic_status: string | null
  province: string | null
  locality: string | null
  country: string | null
  profession: string | null
  role: string
  status: string
  workshops?: { id: number; name: string; number: number }[]
}

export interface PeopleFilters {
  q?: string
  workshop_id?: number
  province?: string
  locality?: string
  country?: string
  masonic_status?: string
  page?: number
  per_page?: number
}

export interface PaginatedPeople {
  data: Person[]
  current_page: number
  last_page: number
  total: number
}

export async function searchPeople(filters: PeopleFilters = {}): Promise<PaginatedPeople> {
  const { data } = await client.get<PaginatedPeople>('/people', { params: filters })
  return data
}

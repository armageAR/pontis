import client from './client'
import type { PaginatedResponse } from './workshops'

export interface UserWorkshop {
  id: number
  name: string
  number: number
}

export interface UserListItem {
  id: number
  name: string
  email: string
  role: string | null
  status: 'pending' | 'active' | 'rejected' | 'suspended' | 'inactive'
  email_verified_at: string | null
  created_at: string
  workshops: UserWorkshop[]
}

export interface UserFilters {
  search?: string
  role?: string
  status?: string
  workshop_id?: number | string
  per_page?: number
  page?: number
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
}

export async function listUsers(filters: UserFilters = {}): Promise<PaginatedResponse<UserListItem>> {
  const params = Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== undefined && v !== ''),
  )
  const { data } = await client.get<PaginatedResponse<UserListItem>>('/users', { params })
  return data
}

export async function updateUserStatus(userId: number, status: string): Promise<UserListItem> {
  const { data } = await client.patch<{ data: UserListItem }>(`/users/${userId}/status`, { status })
  return data.data
}

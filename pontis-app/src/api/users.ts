import client from './client'
import type { PaginatedResponse } from './workshops'

export interface UserWorkshop {
  id: number
  name: string
  number: number
  workshop_role: 'admin' | 'member'
}

export interface UserListItem {
  id: number
  name: string
  last_name: string | null
  email: string
  province: string | null
  role: 'superadmin' | 'user' | null
  status: 'pending' | 'active' | 'rejected' | 'suspended' | 'inactive' | 'o_eterno'
  email_verified_at: string | null
  created_at: string
  workshops: UserWorkshop[]
}

export interface UserFilters {
  search?: string
  role?: string
  status?: string
  workshop_id?: number | string
  workshop_role?: string
  province?: string
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

export interface UserUpdatePayload {
  name?: string
  email?: string
  role?: string
}

export async function updateUser(userId: number, payload: UserUpdatePayload): Promise<UserListItem> {
  const { data } = await client.patch<{ data: UserListItem }>(`/users/${userId}`, payload)
  return data.data
}

export async function updateUserPassword(userId: number, password: string, password_confirmation: string): Promise<void> {
  await client.patch(`/users/${userId}/password`, { password, password_confirmation })
}

export interface WorkshopOption {
  id: number
  name: string
  number: number
  my_role: 'admin' | 'member' | null
}

export async function listMyWorkshops(): Promise<WorkshopOption[]> {
  const { data } = await client.get<WorkshopOption[]>('/my-workshops')
  return data
}

export async function addUserWorkshop(userId: number, workshopId: number): Promise<UserListItem> {
  const { data } = await client.post<{ data: UserListItem }>(`/users/${userId}/workshops/${workshopId}`)
  return data.data
}

export async function updateUserWorkshopRole(userId: number, workshopId: number, role: 'admin' | 'member'): Promise<UserListItem> {
  const { data } = await client.patch<{ data: UserListItem }>(`/users/${userId}/workshops/${workshopId}`, { role })
  return data.data
}

export async function removeUserWorkshop(userId: number, workshopId: number): Promise<UserListItem> {
  const { data } = await client.delete<{ data: UserListItem }>(`/users/${userId}/workshops/${workshopId}`)
  return data.data
}

export async function markUserOEterno(userId: number): Promise<UserListItem> {
  const { data } = await client.post<{ data: UserListItem }>(`/users/${userId}/o-eterno`)
  return data.data
}

export async function revertUserOEterno(userId: number): Promise<UserListItem> {
  const { data } = await client.post<{ data: UserListItem }>(`/users/${userId}/o-eterno/revert`)
  return data.data
}

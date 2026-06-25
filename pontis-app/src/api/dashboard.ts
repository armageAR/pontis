import client from './client'

export interface PendingRequest {
  user_id: number
  user_name: string
  user_email: string
  user_status: string
  workshop_id: number
  workshop_name: string
  workshop_number: number
  requested_at: string
}

export interface MembershipNotification {
  workshop_id: number
  workshop_name: string
  workshop_number: number
  status: 'active' | 'rejected'
  resolved_at: string
}

export interface DashboardData {
  pending_requests: PendingRequest[]
  membership_notifications: MembershipNotification[]
}

export async function getDashboard(): Promise<DashboardData> {
  const { data } = await client.get<DashboardData>('/dashboard')
  return data
}

export async function approveJoinRequest(workshopId: number, userId: number): Promise<void> {
  await client.post(`/admin/workshops/${workshopId}/join-requests/${userId}/approve`)
}

export async function rejectJoinRequest(workshopId: number, userId: number): Promise<void> {
  await client.post(`/admin/workshops/${workshopId}/join-requests/${userId}/reject`)
}

export async function dismissMembershipNotification(workshopId: number): Promise<void> {
  await client.post(`/workshops/${workshopId}/dismiss-notification`)
}

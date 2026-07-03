import client from './client'

export interface WorkshopSummary {
  id: number
  number: number
  name: string
}

export interface User {
  id: number
  name: string
  last_name: string | null
  email: string
  role: string
  status: 'verifying' | 'pending' | 'active' | 'rejected' | 'suspended' | 'inactive' | 'o_eterno'
  masonic_id: string | null
  email_verified_at: string | null
  principal_workshop: WorkshopSummary | null
  admin_workshops: WorkshopSummary[]
}

interface AuthResponse {
  token: string
  user: User
}

export interface MembershipStatus {
  workshop_id: number
  workshop_name: string
  workshop_number: number
  status: 'pending' | 'active' | 'rejected' | 'correction_requested'
  correction_notes: string | null
}

export interface AccountStatus {
  status: 'verifying' | 'pending' | 'active' | 'rejected' | 'suspended' | 'inactive' | 'o_eterno'
  email_verified: boolean
  email_verified_at: string | null
  verification_sent_at: string
  memberships: MembershipStatus[]
}

export async function login(email: string, password: string): Promise<AuthResponse> {
  const { data } = await client.post<AuthResponse>('/login', { email, password })
  return data
}

export async function forgotPassword(email: string): Promise<{ message: string }> {
  const { data } = await client.post<{ message: string }>('/forgot-password', { email })
  return data
}

export async function resetPassword(payload: { email: string; token: string; password: string; password_confirmation: string }): Promise<{ message: string }> {
  const { data } = await client.post<{ message: string }>('/reset-password', payload)
  return data
}

export async function register(
  name: string,
  email: string,
  password: string,
  password_confirmation: string,
  workshop_id: number,
  extra?: { last_name?: string; dni?: string; masonic_id?: string },
): Promise<AuthResponse> {
  const { data } = await client.post<AuthResponse>('/register', {
    name,
    email,
    password,
    password_confirmation,
    workshop_id,
    ...extra,
  })
  return data
}

export async function logout(): Promise<void> {
  await client.post('/logout')
}

export async function getMe(): Promise<User> {
  const { data } = await client.get<User>('/me')
  return data
}

export async function getAccountStatus(): Promise<AccountStatus> {
  const { data } = await client.get<AccountStatus>('/account-status')
  return data
}

export async function resendVerification(): Promise<{ message: string }> {
  const { data } = await client.post<{ message: string }>('/email/resend-verification')
  return data
}

export async function verifyEmail(fullSignedUrl: string): Promise<{ message: string }> {
  const url = new URL(fullSignedUrl)
  const path = url.pathname.replace(/^\/api/, '') + url.search
  const { data } = await client.get<{ message: string }>(path)
  return data
}

export async function confirmEmailChange(fullSignedUrl: string): Promise<{ message: string }> {
  const url = new URL(fullSignedUrl)
  const path = url.pathname.replace(/^\/api/, '') + url.search
  const { data } = await client.get<{ message: string }>(path)
  return data
}

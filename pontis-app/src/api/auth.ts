import client from './client'

export interface User {
  id: number
  name: string
  email: string
  role: string
  status: 'verifying' | 'pending' | 'active' | 'rejected'
  email_verified_at: string | null
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
  status: 'verifying' | 'pending' | 'active' | 'rejected'
  email_verified: boolean
  email_verified_at: string | null
  verification_sent_at: string
  memberships: MembershipStatus[]
}

export async function login(email: string, password: string): Promise<AuthResponse> {
  const { data } = await client.post<AuthResponse>('/login', { email, password })
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

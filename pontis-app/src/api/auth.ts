import client from './client'

export interface User {
  id: number
  name: string
  email: string
  role: string
  status: 'pending' | 'active' | 'rejected'
  email_verified_at: string | null
}

interface AuthResponse {
  token: string
  user: User
}

export interface AccountStatus {
  status: 'pending' | 'active' | 'rejected'
  email_verified: boolean
  email_verified_at: string | null
  verification_sent_at: string
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
): Promise<AuthResponse> {
  const { data } = await client.post<AuthResponse>('/register', {
    name,
    email,
    password,
    password_confirmation,
    workshop_id,
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
  const path = url.pathname + url.search
  const { data } = await client.get<{ message: string }>(path, { baseURL: '' })
  return data
}

import client from './client'
import type { SharedContactField } from './contactRequests'

export interface Profile {
  id: number
  name: string
  last_name: string | null
  email: string
  pending_email: string | null
  role: string
  status: string
  dni: string | null
  masonic_id: string | null
  birth_date: string | null
  initiation_date: string | null
  masonic_status: string | null
  phone: string | null
  whatsapp: string | null
  alternative_email: string | null
  contact_preference: string | null
  country: string | null
  province: string | null
  locality: string | null
  neighborhood: string | null
  address: string | null
  profession: string | null
  occupation: string | null
  company: string | null
  profession_description: string | null
  bio: string | null
  photo_url: string | null
  linkedin: string | null
  website: string | null
  facebook: string | null
  instagram: string | null
  availability_notes: string | null
  phone_fixed: string | null
  secondary_activities: string | null
  knowledge_areas: string | null
  certifications: string | null
}

export type ContactChannel = 'email' | 'phone' | 'whatsapp' | 'in_flow'
export type ContactSource = 'search' | 'publications'

export interface ContactConsentSettings {
  default_shared_fields: SharedContactField[]
  preferred_channels: ContactChannel[]
  allowed_sources: ContactSource[]
}

export interface PublicationPreview {
  anonymous: boolean
  audience: string
  author: {
    name: string
    last_name: string | null
    profession?: string | null
    locality?: string | null
    province?: string | null
  }
}

export interface UserDegree {
  id: number
  user_id: number
  workshop_id: number | null
  degree: 'aprendiz' | 'companero' | 'maestro'
  start_date: string
  end_date: string | null
  notes: string | null
  validation_status: 'declared' | 'validated' | 'rejected'
  validated_at: string | null
  validation_notes: string | null
  created_at?: string | null
  workshop?: { id: number; name: string; number: number }
  user?: { id: number; name: string; last_name: string | null; email: string }
}

export interface UserPosition {
  id: number
  user_id: number
  position_id: number
  workshop_id: number
  start_date: string
  end_date: string | null
  notes: string | null
  validation_status: 'declared' | 'validated' | 'rejected'
  validated_at: string | null
  validation_notes: string | null
  created_at?: string | null
  position?: { id: number; name: string }
  workshop?: { id: number; name: string; number: number }
  user?: { id: number; name: string; last_name: string | null; email: string }
}

export async function getProfile(): Promise<Profile> {
  const { data } = await client.get<Profile>('/profile')
  return data
}

export async function updateProfile(payload: Partial<Profile>): Promise<Profile> {
  const { data } = await client.patch<Profile>('/profile', payload)
  return data
}

export async function requestEmailChange(email: string): Promise<{ message: string; pending_email: string }> {
  const { data } = await client.post<{ message: string; pending_email: string }>('/profile/email', { email })
  return data
}

export async function getContactConsent(): Promise<ContactConsentSettings> {
  const { data } = await client.get<ContactConsentSettings>('/profile/contact-consent')
  return data
}

export async function updateContactConsent(payload: ContactConsentSettings): Promise<ContactConsentSettings> {
  const { data } = await client.patch<ContactConsentSettings>('/profile/contact-consent', payload)
  return data
}

export async function getPublicationPreview(visibility: string): Promise<PublicationPreview> {
  const { data } = await client.get<PublicationPreview>('/profile/publication-preview', { params: { visibility } })
  return data
}

export async function getDegrees(): Promise<UserDegree[]> {
  const { data } = await client.get<UserDegree[]>('/profile/degrees')
  return data
}

// El grado no lleva fecha de fin manual: el período se deriva del siguiente grado.
export type DegreeInput = Omit<UserDegree, 'id' | 'user_id' | 'workshop' | 'end_date' | 'validation_status' | 'validated_at' | 'validation_notes' | 'user'>

export async function addDegree(payload: DegreeInput): Promise<UserDegree> {
  const { data } = await client.post<UserDegree>('/profile/degrees', payload)
  return data
}

export async function updateDegree(id: number, payload: Partial<DegreeInput>): Promise<UserDegree> {
  const { data } = await client.patch<UserDegree>(`/profile/degrees/${id}`, payload)
  return data
}

export async function deleteDegree(id: number): Promise<void> {
  await client.delete(`/profile/degrees/${id}`)
}

export async function getPendingDegreeValidations(): Promise<{ data: UserDegree[] }> {
  const { data } = await client.get<{ data: UserDegree[] }>('/admin/degree-validations')
  return data
}

export async function validateDegree(id: number): Promise<UserDegree> {
  const { data } = await client.post<UserDegree>(`/admin/degrees/${id}/validate`)
  return data
}

export async function rejectDegree(id: number, validation_notes?: string): Promise<UserDegree> {
  const { data } = await client.post<UserDegree>(`/admin/degrees/${id}/reject`, { validation_notes })
  return data
}

export async function getPositions(): Promise<UserPosition[]> {
  const { data } = await client.get<UserPosition[]>('/profile/positions')
  return data
}

export async function addPosition(payload: Omit<UserPosition, 'id' | 'user_id' | 'position' | 'workshop' | 'validation_status' | 'validated_at' | 'validation_notes' | 'user'>): Promise<UserPosition> {
  const { data } = await client.post<UserPosition>('/profile/positions', payload)
  return data
}

export async function updatePosition(id: number, payload: Partial<UserPosition>): Promise<UserPosition> {
  const { data } = await client.patch<UserPosition>(`/profile/positions/${id}`, payload)
  return data
}

export async function deletePosition(id: number): Promise<void> {
  await client.delete(`/profile/positions/${id}`)
}

export async function getPendingPositionValidations(): Promise<{ data: UserPosition[] }> {
  const { data } = await client.get<{ data: UserPosition[] }>('/admin/position-validations')
  return data
}

export async function validatePosition(id: number): Promise<UserPosition> {
  const { data } = await client.post<UserPosition>(`/admin/positions/${id}/validate`)
  return data
}

export async function rejectPosition(id: number, validation_notes?: string): Promise<UserPosition> {
  const { data } = await client.post<UserPosition>(`/admin/positions/${id}/reject`, { validation_notes })
  return data
}

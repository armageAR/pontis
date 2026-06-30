import client from './client'

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

export interface UserDegree {
  id: number
  user_id: number
  workshop_id: number | null
  degree: 'aprendiz' | 'companero' | 'maestro'
  start_date: string
  end_date: string | null
  notes: string | null
  workshop?: { id: number; name: string; number: number }
}

export interface UserPosition {
  id: number
  user_id: number
  position_id: number
  workshop_id: number
  start_date: string
  end_date: string | null
  notes: string | null
  position?: { id: number; name: string }
  workshop?: { id: number; name: string; number: number }
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

export async function getDegrees(): Promise<UserDegree[]> {
  const { data } = await client.get<UserDegree[]>('/profile/degrees')
  return data
}

export async function addDegree(payload: Omit<UserDegree, 'id' | 'user_id' | 'workshop'>): Promise<UserDegree> {
  const { data } = await client.post<UserDegree>('/profile/degrees', payload)
  return data
}

export async function updateDegree(id: number, payload: Partial<UserDegree>): Promise<UserDegree> {
  const { data } = await client.patch<UserDegree>(`/profile/degrees/${id}`, payload)
  return data
}

export async function deleteDegree(id: number): Promise<void> {
  await client.delete(`/profile/degrees/${id}`)
}

export async function getPositions(): Promise<UserPosition[]> {
  const { data } = await client.get<UserPosition[]>('/profile/positions')
  return data
}

export async function addPosition(payload: Omit<UserPosition, 'id' | 'user_id' | 'position' | 'workshop'>): Promise<UserPosition> {
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

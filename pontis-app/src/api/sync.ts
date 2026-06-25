import client from './client'

export interface GlaNewWorkshop {
  number: number
  name: string
  zone_number: number | null
  zone_name: string | null
  work_day: string | null
  work_frequency: string | null
  address: string | null
  city: string | null
  province: string | null
  language: string | null
}

export interface GlaModifiedWorkshop {
  id: number
  number: number
  name: string
  changes: Record<string, { from: string | null; to: string | null }>
}

export interface GlaDisabledWorkshop {
  id: number
  number: number
  name: string
  city: string | null
}

export interface GlaDiff {
  new: GlaNewWorkshop[]
  modified: GlaModifiedWorkshop[]
  disabled: GlaDisabledWorkshop[]
}

export interface GlaApplyResult {
  message: string
  counts: { created: number; updated: number; disabled: number }
}

export async function previewGlaSync(): Promise<GlaDiff> {
  const { data } = await client.post<GlaDiff>('/admin/workshops/gla/preview')
  return data
}

export async function applyGlaSync(selected: {
  new: number[]
  modified: number[]
  disabled: number[]
}): Promise<GlaApplyResult> {
  const { data } = await client.post<GlaApplyResult>('/admin/workshops/gla/apply', selected)
  return data
}

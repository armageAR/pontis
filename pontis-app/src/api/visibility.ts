import client from './client'
export type VisibilityLevel = 'private' | 'workshop' | 'my_workshops' | 'talleres_seleccionados' | 'registered' | 'anonymous'
export type VisibilityBlock = 'identity' | 'masonic' | 'contact' | 'location' | 'profession' | 'bio' | 'degrees' | 'positions'
export interface VisibilitySetting { id: number; user_id: number; block: VisibilityBlock; visibility: VisibilityLevel }
export type VisibilityMap = Partial<Record<VisibilityBlock, VisibilitySetting>>
export const VISIBILITY_LABELS: Record<VisibilityLevel, string> = {
  private: 'Solo yo (privado)',
  workshop: 'Mi taller principal',
  my_workshops: 'Mis talleres',
  talleres_seleccionados: 'Talleres seleccionados',
  registered: 'Masones registrados',
  anonymous: 'Disponible en búsquedas sin revelar identidad',
}
export async function getVisibility(): Promise<VisibilityMap> {
  const r = await client.get<VisibilityMap>('/profile/visibility')
  return r.data
}
export async function updateVisibility(settings: { block: VisibilityBlock; visibility: VisibilityLevel }[]): Promise<VisibilityMap> {
  const r = await client.post<VisibilityMap>('/profile/visibility', { settings })
  return r.data
}

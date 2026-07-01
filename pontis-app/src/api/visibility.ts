import client from './client'
// La audiencia (quiénes ven la sección) es independiente de la aparición sin
// revelar identidad, que se guarda como el flag "anonymous_search" del bloque Identidad.
export type VisibilityLevel = 'private' | 'workshop' | 'my_workshops' | 'registered'
export type VisibilityBlock = 'identity' | 'masonic' | 'contact' | 'location' | 'profession' | 'bio' | 'degrees' | 'positions'
export interface VisibilitySetting { id: number; user_id: number; block: VisibilityBlock; visibility: VisibilityLevel; anonymous_search?: boolean }
export type VisibilityMap = Partial<Record<VisibilityBlock, VisibilitySetting>>
export interface VisibilityUpdate { block: VisibilityBlock; visibility: VisibilityLevel; anonymous_search?: boolean }
export const VISIBILITY_LABELS: Record<VisibilityLevel, string> = {
  private: 'Solo yo (privado)',
  workshop: 'Mi taller principal',
  my_workshops: 'Mis talleres',
  registered: 'Masones registrados',
}
export async function getVisibility(): Promise<VisibilityMap> {
  const r = await client.get<VisibilityMap>('/profile/visibility')
  return r.data
}
export async function updateVisibility(settings: VisibilityUpdate[]): Promise<VisibilityMap> {
  const r = await client.post<VisibilityMap>('/profile/visibility', { settings })
  return r.data
}

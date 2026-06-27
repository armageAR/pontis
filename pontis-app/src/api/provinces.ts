import client from './client'
export interface Province { id: number; name: string; code: string | null }
export async function getProvinces(): Promise<Province[]> {
  const r = await client.get<Province[]>('/provinces')
  return r.data
}

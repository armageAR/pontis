import client from './client'

export interface AuditLog {
  id: number
  actor_id: number | null
  action: string
  entity_type: string
  entity_id: number | null
  decision: string | null
  metadata: Record<string, unknown> | null
  created_at: string
  actor?: { id: number; name: string; last_name: string | null; email: string } | null
}

export interface AuditFilters {
  action?: string
  actor_id?: string
  entity_type?: string
  entity_id?: string
  date_from?: string
  date_to?: string
  page?: number
}

export interface PaginatedAuditLogs {
  data: AuditLog[]
  current_page: number
  last_page: number
  total: number
}

export async function getAuditLogs(filters: AuditFilters = {}): Promise<PaginatedAuditLogs> {
  const { data } = await client.get<PaginatedAuditLogs>('/audit-logs', { params: filters })
  return data
}

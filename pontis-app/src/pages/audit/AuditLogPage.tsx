import { useEffect, useState } from 'react'
import Alert from '@/components/Alert'
import Button from '@/components/Button'
import EmptyState from '@/components/EmptyState'
import Input from '@/components/Input'
import Pagination from '@/components/Pagination'
import Spinner from '@/components/Spinner'
import * as api from '@/api/audit'
import type { AuditLog } from '@/api/audit'
import { formatDate } from '@/utils/date'
import './AuditLogPage.css'

export default function AuditLogPage() {
  const [logs, setLogs] = useState<AuditLog[]>([])
  const [filters, setFilters] = useState({ action: '', actor_id: '', entity_type: '', entity_id: '', date_from: '', date_to: '' })
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  async function load(nextPage = page) {
    setLoading(true); setError('')
    try {
      const r = await api.getAuditLogs({ ...filters, page: nextPage })
      setLogs(r.data); setLastPage(r.last_page); setTotal(r.total)
    } catch {
      setError('No se pudo cargar la auditoría.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load(page) }, [page])

  function applyFilters() {
    setPage(1)
    load(1)
  }

  return (
    <div className="audit-page">
      {error && <Alert>{error}</Alert>}
      <div className="audit-filters">
        <Input placeholder="Acción" value={filters.action} onChange={e => setFilters(f => ({ ...f, action: e.target.value }))} />
        <Input placeholder="Actor ID" value={filters.actor_id} onChange={e => setFilters(f => ({ ...f, actor_id: e.target.value }))} />
        <Input placeholder="Entidad" value={filters.entity_type} onChange={e => setFilters(f => ({ ...f, entity_type: e.target.value }))} />
        <Input placeholder="Entidad ID" value={filters.entity_id} onChange={e => setFilters(f => ({ ...f, entity_id: e.target.value }))} />
        <Input type="date" value={filters.date_from} onChange={e => setFilters(f => ({ ...f, date_from: e.target.value }))} />
        <Input type="date" value={filters.date_to} onChange={e => setFilters(f => ({ ...f, date_to: e.target.value }))} />
        <Button type="button" onClick={applyFilters}>Filtrar</Button>
      </div>

      {loading ? <div className="audit-loading"><Spinner /></div> : logs.length === 0 ? (
        <EmptyState title="Sin eventos" description="No hay eventos de auditoría con esos filtros." />
      ) : (
        <>
          <div className="audit-table-wrap">
            <table className="audit-table">
              <thead>
                <tr><th>Fecha</th><th>Acción</th><th>Actor</th><th>Entidad</th><th>Decisión</th><th>Metadata</th></tr>
              </thead>
              <tbody>
                {logs.map(log => (
                  <tr key={log.id}>
                    <td>{formatDate(log.created_at)}</td>
                    <td>{log.action}</td>
                    <td>{log.actor ? `${log.actor.last_name ? `${log.actor.last_name}, ` : ''}${log.actor.name}` : '-'}</td>
                    <td>{log.entity_type}{log.entity_id ? ` #${log.entity_id}` : ''}</td>
                    <td>{log.decision ?? '-'}</td>
                    <td><code>{log.metadata ? JSON.stringify(log.metadata) : '-'}</code></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pagination currentPage={page} lastPage={lastPage} total={total} onPageChange={setPage} />
        </>
      )}
    </div>
  )
}

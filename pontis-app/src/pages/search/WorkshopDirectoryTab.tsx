import { useState, type FormEvent } from 'react'
import Input from '@/components/Input'
import Button from '@/components/Button'
import Spinner from '@/components/Spinner'
import EmptyState from '@/components/EmptyState'
import Alert from '@/components/Alert'
import Pagination from '@/components/Pagination'
import * as api from '@/api/workshops'
import type { Workshop } from '@/api/workshops'
import './WorkshopDirectoryTab.css'

const STATUS_LABELS: Record<string, string> = { active: 'Activo', disabled: 'Inactivo' }

export default function WorkshopDirectoryTab() {
  const [workshops, setWorkshops]   = useState<Workshop[]>([])
  const [loading, setLoading]       = useState(false)
  const [error, setError]           = useState('')
  const [searched, setSearched]     = useState(false)
  const [search, setSearch]         = useState('')
  const [page, setPage]             = useState(1)
  const [lastPage, setLastPage]     = useState(1)
  const [total, setTotal]           = useState(0)

  async function load(p = page) {
    setLoading(true); setError('')
    try {
      const r = await api.listWorkshops({ search: search || undefined, page: p, per_page: 20 })
      setWorkshops(r.data); setLastPage(r.meta.last_page); setTotal(r.meta.total); setSearched(true)
    } catch {
      setError('Error al buscar Talleres.')
    } finally {
      setLoading(false)
    }
  }

  function handleSearch(e: FormEvent) { e.preventDefault(); setPage(1); load(1) }
  function handlePageChange(p: number) { setPage(p); load(p) }

  return (
    <>
      <form onSubmit={handleSearch} className="workshop-dir-form">
        <Input
          className="workshop-dir-input"
          placeholder="Nombre o número de Taller…"
          value={search}
          onChange={e => setSearch(e.target.value)}
        />
        <Button type="submit">Buscar</Button>
      </form>

      {error && <Alert variant="error">{error}</Alert>}

      {loading ? (
        <div className="workshop-dir-loading"><Spinner /></div>
      ) : !searched ? null : workshops.length === 0 ? (
        <EmptyState title="Sin resultados" description="No se encontraron Talleres con esos criterios." />
      ) : (
        <>
          <p className="workshop-dir-count">{total} Taller{total !== 1 ? 'es' : ''}</p>
          <div className="workshop-dir-list">
            {workshops.map(w => (
              <div key={w.id} className="workshop-dir-card">
                <div className="workshop-dir-card-header">
                  <span className="workshop-dir-number">Nº{w.number}</span>
                  <span className="workshop-dir-name">{w.name}</span>
                  {w.status && (
                    <span className={`workshop-dir-status workshop-dir-status--${w.status}`}>
                      {STATUS_LABELS[w.status] ?? w.status}
                    </span>
                  )}
                </div>
                {(w.city || w.province || w.country) && (
                  <div className="workshop-dir-meta">
                    {[w.city, w.province, w.country].filter(Boolean).join(', ')}
                  </div>
                )}
                {w.work_day && (
                  <div className="workshop-dir-meta">{w.work_day}</div>
                )}
              </div>
            ))}
          </div>
          <Pagination currentPage={page} lastPage={lastPage} total={total} onPageChange={handlePageChange} />
        </>
      )}
    </>
  )
}

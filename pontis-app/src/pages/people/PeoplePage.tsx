import { useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import AppLayout from '@/components/AppLayout'
import Input from '@/components/Input'
import Button from '@/components/Button'
import Spinner from '@/components/Spinner'
import Badge from '@/components/Badge'
import EmptyState from '@/components/EmptyState'
import Pagination from '@/components/Pagination'
import Alert from '@/components/Alert'
import * as api from '@/api/people'
import type { Person } from '@/api/people'
import './PeoplePage.css'

const MASONIC_STATUS_LABELS: Record<string, string> = { active: 'Activo', inactive: 'Inactivo', suspended: 'Suspendido', discharged: 'Dado de baja', deceased: 'Fallecido' }
const MASONIC_STATUS_VARIANTS: Record<string, 'default'|'success'|'warning'|'error'> = { active: 'success', inactive: 'default', suspended: 'warning', discharged: 'error', deceased: 'default' }

export default function PeoplePage() {
  const [people, setPeople] = useState<Person[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [q, setQ] = useState('')
  const [province, setProvince] = useState('')
  const [masonicStatus, setMasonicStatus] = useState('')
  const [searched, setSearched] = useState(false)

  async function load(p = page) {
    setLoading(true); setError('')
    try {
      const r = await api.searchPeople({ q: q || undefined, province: province || undefined, masonic_status: masonicStatus || undefined, page: p })
      setPeople(r.data); setLastPage(r.last_page); setTotal(r.total); setSearched(true)
    } catch { setError('Error al buscar hermanos.') }
    finally { setLoading(false) }
  }

  function handleSearch(e: FormEvent) { e.preventDefault(); setPage(1); load(1) }

  function handlePageChange(p: number) { setPage(p); load(p) }

  return (
    <AppLayout>
      <h1 className="people-title">Buscar hermanos</h1>
      <form onSubmit={handleSearch} className="people-search-form">
        <Input className="people-search-input" placeholder="Nombre, apellido, matrícula o email…" value={q} onChange={e => setQ(e.target.value)} />
        <Input placeholder="Provincia" value={province} onChange={e => setProvince(e.target.value)} />
        <select className="people-select" value={masonicStatus} onChange={e => setMasonicStatus(e.target.value)}>
          <option value="">Todos los estados</option>
          {Object.entries(MASONIC_STATUS_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
        </select>
        <Button type="submit">Buscar</Button>
      </form>

      {error && <Alert>{error}</Alert>}

      {loading ? (
        <div className="people-loading"><Spinner /></div>
      ) : !searched ? null : people.length === 0 ? (
        <EmptyState title="Sin resultados" description="No se encontraron hermanos registrados con esos criterios." />
      ) : (
        <>
          <p className="people-results-count">{total} resultado{total !== 1 ? 's' : ''}</p>
          <div className="people-list">
            {people.map(p => (
              <div key={p.id} className="person-card">
                <div className="person-card-header">
                  <div>
                    <Link to={`/people/${p.id}`} className="person-name">{p.last_name ? `${p.last_name}, ${p.name}` : p.name}</Link>
                    {p.masonic_id && <span className="person-masonic-id">Mat. {p.masonic_id}</span>}
                  </div>
                  {p.masonic_status && (
                    <Badge variant={MASONIC_STATUS_VARIANTS[p.masonic_status] ?? 'default'}>{MASONIC_STATUS_LABELS[p.masonic_status] ?? p.masonic_status}</Badge>
                  )}
                </div>
                <div className="person-meta">
                  {p.profession && <span className="person-meta-item">{p.profession}</span>}
                  {(p.locality || p.province) && <span className="person-meta-item">{[p.locality, p.province].filter(Boolean).join(', ')}</span>}
                </div>
                {p.workshops && p.workshops.length > 0 && (
                  <div className="person-workshops">
                    {p.workshops.map(w => <span key={w.id} className="person-workshop-tag">Nº{w.number} {w.name}</span>)}
                  </div>
                )}
              </div>
            ))}
          </div>
          <Pagination currentPage={page} lastPage={lastPage} total={total} onPageChange={handlePageChange} />
        </>
      )}
    </AppLayout>
  )
}

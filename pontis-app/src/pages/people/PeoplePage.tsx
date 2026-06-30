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
import WorkshopPicker from '@/components/WorkshopPicker'
import { PROVINCIAS } from '@/constants/provincias'
import * as api from '@/api/people'
import type { Person } from '@/api/people'
import type { WorkshopSearchResult } from '@/api/workshops'
import './PeoplePage.css'

const MASONIC_STATUS_LABELS: Record<string, string> = {
  active:     'Activo',
  inactive:   'Inactivo',
  suspended:  'Suspendido',
  discharged: 'Dado de baja',
  deceased:   'O∴ Eterno',
}
const MASONIC_STATUS_VARIANTS: Record<string, 'default'|'success'|'warning'|'error'> = {
  active:     'success',
  inactive:   'default',
  suspended:  'warning',
  discharged: 'error',
  deceased:   'default',
}

export default function PeoplePage({ embedded = false }: { embedded?: boolean }) {
  const [people, setPeople]           = useState<Person[]>([])
  const [loading, setLoading]         = useState(false)
  const [error, setError]             = useState('')
  const [page, setPage]               = useState(1)
  const [lastPage, setLastPage]       = useState(1)
  const [total, setTotal]             = useState(0)
  const [searched, setSearched]       = useState(false)
  const [perPage, setPerPage]         = useState(10)

  const [q, setQ]                       = useState('')
  const [workshop, setWorkshop]         = useState<WorkshopSearchResult | null>(null)
  const [locality, setLocality]         = useState('')
  const [province, setProvince]         = useState('')
  const [masonicStatus, setMasonicStatus] = useState('active')

  async function load(p = page, pp = perPage) {
    setLoading(true); setError('')
    try {
      const r = await api.searchPeople({
        q:              q        || undefined,
        workshop_id:    workshop?.id,
        locality:       locality || undefined,
        province:       province || undefined,
        masonic_status: masonicStatus || undefined,
        page:           p,
        per_page:       pp,
      })
      setPeople(r.data); setLastPage(r.last_page); setTotal(r.total); setSearched(true)
    } catch {
      setError('Error al buscar Hermanos.')
    } finally {
      setLoading(false)
    }
  }

  function handleSearch(e: FormEvent) { e.preventDefault(); setPage(1); load(1) }
  function handlePageChange(p: number) { setPage(p); load(p) }
  function handlePerPageChange(e: React.ChangeEvent<HTMLSelectElement>) {
    const pp = Number(e.target.value)
    setPerPage(pp); setPage(1); load(1, pp)
  }

  const content = (
    <>
      <h2 className="people-title">Buscar Hermanos</h2>
      <form onSubmit={handleSearch} className="people-search-form">
        <div className="people-search-row">
          <Input
            className="people-search-name"
            placeholder="Nombre, apellido, matrícula o email…"
            value={q}
            onChange={e => setQ(e.target.value)}
          />
        </div>
        <div className="people-search-row">
          <WorkshopPicker
            value={workshop}
            onChange={setWorkshop}
            placeholder="Taller (nombre o número)"
          />
          <select
            className="people-select"
            value={masonicStatus}
            onChange={e => setMasonicStatus(e.target.value)}
          >
            <option value="">Todos los estados</option>
            {Object.entries(MASONIC_STATUS_LABELS).map(([v, l]) => (
              <option key={v} value={v}>{l}</option>
            ))}
          </select>
        </div>
        <div className="people-search-row">
          <Input
            className="people-search-city"
            placeholder="Ciudad"
            value={locality}
            onChange={e => setLocality(e.target.value)}
          />
          <select
            className="people-select"
            value={province}
            onChange={e => setProvince(e.target.value)}
          >
            <option value="">Todas las provincias</option>
            {PROVINCIAS.map(p => <option key={p} value={p}>{p}</option>)}
          </select>
          <div className="people-search-submit">
            <Button type="submit">Buscar</Button>
          </div>
        </div>
      </form>

      {error && <Alert variant="error">{error}</Alert>}

      {loading ? (
        <div className="people-loading"><Spinner /></div>
      ) : !searched ? null : people.length === 0 ? (
        <EmptyState title="Sin resultados" description="No se encontraron Hermanos con esos criterios." />
      ) : (
        <>
          <div className="people-results-header">
            <p className="people-results-count">{total} resultado{total !== 1 ? 's' : ''}</p>
            <div className="people-per-page">
              <label>Mostrar</label>
              <select value={perPage} onChange={handlePerPageChange} className="people-select people-select-sm">
                <option value={10}>10</option>
                <option value={25}>25</option>
                <option value={50}>50</option>
                <option value={0}>Todos</option>
              </select>
            </div>
          </div>
          <div className="people-list">
            {people.map(p => (
              <div key={p.id} className="person-card">
                <div className="person-card-header">
                  <div>
                    <Link to={`/people/${p.id}`} className="person-name">
                      {p.last_name ? `${p.last_name}, ${p.name}` : p.name}
                    </Link>
                    {p.masonic_id && <span className="person-masonic-id">Mat. {p.masonic_id}</span>}
                  </div>
                  {p.masonic_status && (
                    <Badge variant={MASONIC_STATUS_VARIANTS[p.masonic_status] ?? 'default'}>
                      {MASONIC_STATUS_LABELS[p.masonic_status] ?? p.masonic_status}
                    </Badge>
                  )}
                </div>
                <div className="person-meta">
                  {p.profession && <span className="person-meta-item">{p.profession}</span>}
                  {(p.locality || p.province) && (
                    <span className="person-meta-item">
                      {[p.locality, p.province].filter(Boolean).join(', ')}
                    </span>
                  )}
                </div>
                {p.workshops && p.workshops.length > 0 && (
                  <div className="person-workshops">
                    {p.workshops.map(w => (
                      <span key={w.id} className="person-workshop-tag">Nº{w.number} {w.name}</span>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>
          <Pagination currentPage={page} lastPage={lastPage} total={total} onPageChange={handlePageChange} />
        </>
      )}
    </>
  )

  if (embedded) return content
  return <AppLayout>{content}</AppLayout>
}

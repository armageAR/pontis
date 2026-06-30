import { useEffect, useState, type FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { Eye, Pencil, KeyRound, ChevronUp, ChevronDown } from 'lucide-react'
import AppLayout from '@/components/AppLayout'
import Input from '@/components/Input'
import Spinner from '@/components/Spinner'
import EmptyState from '@/components/EmptyState'
import Pagination from '@/components/Pagination'
import Alert from '@/components/Alert'
import Modal from '@/components/Modal'
import Badge from '@/components/Badge'
import { useAuth } from '@/context/AuthContext'
import * as api from '@/api/people'
import type { Person } from '@/api/people'
import * as usersApi from '@/api/users'
import type { WorkshopOption } from '@/api/users'
import client from '@/api/client'
import { formatDate } from '@/utils/date'
import './MisHermanosPage.css'

const MASONIC_STATUS_LABELS: Record<string, string> = {
  active: 'Activo', inactive: 'Inactivo', suspended: 'Suspendido',
  discharged: 'Dado de baja', deceased: 'O∴ Eterno',
}
const DEGREE_LABELS: Record<string, string> = {
  aprendiz: 'Aprendiz', companero: 'Compañero', maestro: 'Maestro',
}

interface PublicProfile {
  id: number
  name: string
  last_name: string | null
  masonic_id: string | null
  masonic_status: string | null
  initiation_date: string | null
  province: string | null
  locality: string | null
  profession: string | null
  occupation: string | null
  bio: string | null
  workshops: { id: number; name: string; number: number }[]
  degrees: { id: number; degree: string; start_date: string; end_date: string | null; workshop?: { name: string } }[]
  positions: { id: number; start_date: string; end_date: string | null; position?: { name: string }; workshop?: { name: string } }[]
}

type SortKey = 'name' | 'workshop'
type SortDir = 'asc' | 'desc'

export default function MisHermanosPage() {
  const { user: currentUser } = useAuth()
  const navigate = useNavigate()

  const [people, setPeople] = useState<Person[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [total, setTotal] = useState(0)

  const [q, setQ] = useState('')
  const [workshopId, setWorkshopId] = useState('')
  const [workshops, setWorkshops] = useState<WorkshopOption[]>([])

  const [sortKey, setSortKey] = useState<SortKey>('name')
  const [sortDir, setSortDir] = useState<SortDir>('asc')

  const [viewId, setViewId] = useState<number | null>(null)
  const [profile, setProfile] = useState<PublicProfile | null>(null)
  const [profileLoading, setProfileLoading] = useState(false)

  useEffect(() => {
    usersApi.listMyWorkshops().then(setWorkshops).catch(() => {})
    doLoad(1)
  }, [])

  async function doLoad(p: number) {
    setLoading(true); setError('')
    try {
      const r = await api.searchPeople({
        q: q || undefined,
        workshop_id: workshopId ? Number(workshopId) : undefined,
        masonic_status: 'active',
        page: p,
        per_page: 20,
      })
      setPeople(r.data); setLastPage(r.last_page); setTotal(r.total); setPage(p)
    } catch {
      setError('Error al buscar Hermanos.')
    } finally {
      setLoading(false)
    }
  }

  function handleSearch(e: FormEvent) {
    e.preventDefault(); doLoad(1)
  }

  async function handleView(id: number) {
    setViewId(id); setProfile(null); setProfileLoading(true)
    try {
      const r = await client.get<PublicProfile>(`/people/${id}`)
      setProfile(r.data)
    } catch {
      setProfile(null)
    } finally {
      setProfileLoading(false)
    }
  }

  function getSorted() {
    return [...people].sort((a, b) => {
      let va = '', vb = ''
      if (sortKey === 'name') {
        va = [a.last_name, a.name].filter(Boolean).join(' ').toLowerCase()
        vb = [b.last_name, b.name].filter(Boolean).join(' ').toLowerCase()
      } else {
        va = (a.workshops?.[0]?.name ?? '').toLowerCase()
        vb = (b.workshops?.[0]?.name ?? '').toLowerCase()
      }
      const cmp = va < vb ? -1 : va > vb ? 1 : 0
      return sortDir === 'asc' ? cmp : -cmp
    })
  }

  function toggleSort(key: SortKey) {
    if (sortKey === key) setSortDir(d => d === 'asc' ? 'desc' : 'asc')
    else { setSortKey(key); setSortDir('asc') }
  }

  function sortIcon(key: SortKey) {
    if (sortKey !== key) return null
    return sortDir === 'asc' ? <ChevronUp size={13} /> : <ChevronDown size={13} />
  }

  const viewName = profile
    ? (profile.last_name ? `${profile.last_name}, ${profile.name}` : profile.name)
    : '…'

  return (
    <AppLayout>
      <h1 className="mh-title">Mis Hermanos</h1>
      <p className="mh-desc">
        Directorio de Hermanos activos. Solo ves los datos que cada uno eligió compartir.
      </p>

      <form className="mh-filters" onSubmit={handleSearch}>
        <Input
          placeholder="Nombre o email"
          value={q}
          onChange={e => setQ(e.target.value)}
          className="mh-search"
        />
        <select
          className="mh-select"
          value={workshopId}
          onChange={e => setWorkshopId(e.target.value)}
        >
          <option value="">Taller</option>
          {workshops.map(w => (
            <option key={w.id} value={String(w.id)}>Nº{w.number} {w.name}</option>
          ))}
        </select>
        <button type="submit" className="mh-btn-buscar">Buscar</button>
      </form>

      {error && <Alert variant="error">{error}</Alert>}

      {loading ? (
        <div className="mh-loading"><Spinner /></div>
      ) : people.length === 0 ? (
        <EmptyState title="Sin resultados" description="No se encontraron Hermanos con esos criterios." />
      ) : (
        <>
          <p className="mh-count">{total} Hermano{total !== 1 ? 's' : ''}</p>
          <div className="mh-table-wrap">
            <table className="mh-table">
              <thead>
                <tr>
                  <th className="mh-th-sort" onClick={() => toggleSort('name')}>
                    Apellido y nombre {sortIcon('name')}
                  </th>
                  <th className="mh-th-sort" onClick={() => toggleSort('workshop')}>
                    Taller {sortIcon('workshop')}
                  </th>
                  <th className="mh-th-actions" />
                </tr>
              </thead>
              <tbody>
                {getSorted().map(p => {
                  const isOwn = p.id === currentUser?.id
                  const fullName = p.last_name ? `${p.last_name}, ${p.name}` : p.name
                  const workshopLabel = p.workshops?.map(w => `Nº${w.number} ${w.name}`).join(', ') ?? '—'
                  return (
                    <tr key={p.id} className={isOwn ? 'mh-row-own' : ''}>
                      <td className="mh-cell-name">{fullName}</td>
                      <td className="mh-cell-workshop">{workshopLabel}</td>
                      <td>
                        <div className="mh-cell-actions">
                          <button className="mh-action-btn" title="Ver perfil" onClick={() => handleView(p.id)}>
                            <Eye size={15} />
                          </button>
                          {isOwn && (
                            <>
                              <button className="mh-action-btn" title="Editar mi perfil" onClick={() => navigate('/profile')}>
                                <Pencil size={15} />
                              </button>
                              <button className="mh-action-btn" title="Cambiar contraseña" onClick={() => navigate('/profile')}>
                                <KeyRound size={15} />
                              </button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
          <Pagination currentPage={page} lastPage={lastPage} total={total} onPageChange={doLoad} />
        </>
      )}

      <Modal
        open={viewId !== null}
        onClose={() => { setViewId(null); setProfile(null) }}
        title={viewName}
      >
        {profileLoading ? (
          <div className="mh-loading"><Spinner /></div>
        ) : !profile ? (
          <p className="mh-modal-empty">No se pudo cargar el perfil.</p>
        ) : (
          <div className="mh-profile">
            {profile.bio && <p className="mh-profile-bio">{profile.bio}</p>}
            <div className="mh-profile-grid">
              <div className="mh-field">
                <span className="mh-label">Matrícula</span>
                <span>{profile.masonic_id ?? '—'}</span>
              </div>
              <div className="mh-field">
                <span className="mh-label">Estado</span>
                <span>{profile.masonic_status ? (MASONIC_STATUS_LABELS[profile.masonic_status] ?? profile.masonic_status) : '—'}</span>
              </div>
              <div className="mh-field">
                <span className="mh-label">Iniciación</span>
                <span>{profile.initiation_date ? formatDate(profile.initiation_date) : '—'}</span>
              </div>
              <div className="mh-field">
                <span className="mh-label">Profesión</span>
                <span>{profile.profession ?? '—'}</span>
              </div>
              <div className="mh-field">
                <span className="mh-label">Ocupación</span>
                <span>{profile.occupation ?? '—'}</span>
              </div>
              <div className="mh-field">
                <span className="mh-label">Ubicación</span>
                <span>{[profile.locality, profile.province].filter(Boolean).join(', ') || '—'}</span>
              </div>
            </div>
            {profile.workshops.length > 0 && (
              <div className="mh-profile-section">
                <span className="mh-label">Talleres</span>
                <div className="mh-tags">
                  {profile.workshops.map(w => (
                    <span key={w.id} className="mh-tag">Nº{w.number} {w.name}</span>
                  ))}
                </div>
              </div>
            )}
            {profile.degrees.length > 0 && (
              <div className="mh-profile-section">
                <span className="mh-label">Grado</span>
                <div className="mh-history">
                  {profile.degrees.map(d => (
                    <div key={d.id} className="mh-history-item">
                      <Badge variant="default">{DEGREE_LABELS[d.degree] ?? d.degree}</Badge>
                      {d.workshop?.name && <span className="mh-history-detail">{d.workshop.name}</span>}
                      <span className="mh-history-dates">
                        {formatDate(d.start_date)}{d.end_date ? ` – ${formatDate(d.end_date)}` : ''}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            )}
            {profile.positions.length > 0 && (
              <div className="mh-profile-section">
                <span className="mh-label">Cargos</span>
                <div className="mh-history">
                  {profile.positions.map(pos => (
                    <div key={pos.id} className="mh-history-item">
                      <span className="mh-cargo">{pos.position?.name ?? '—'}</span>
                      {pos.workshop?.name && <span className="mh-history-detail">{pos.workshop.name}</span>}
                      <span className="mh-history-dates">
                        {formatDate(pos.start_date)}{pos.end_date ? ` – ${formatDate(pos.end_date)}` : ''}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
      </Modal>
    </AppLayout>
  )
}

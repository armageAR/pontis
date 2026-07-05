import { useEffect, useState } from 'react'
import Modal from './Modal'
import Spinner from './Spinner'
import Badge from './Badge'
import Button from './Button'
import Alert from './Alert'
import ContactRequestForm from './ContactRequestForm'
import { useAuth } from '@/context/AuthContext'
import client from '@/api/client'
import { formatDate } from '@/utils/date'
import './PersonProfileModal.css'

const MASONIC_STATUS_LABELS: Record<string, string> = {
  active: 'Activo', inactive: 'Inactivo', suspended: 'Suspendido',
  discharged: 'Dado de baja', deceased: 'O Eterno',
}
const DEGREE_LABELS: Record<string, string> = {
  aprendiz: 'Aprendiz', companero: 'Compañero', maestro: 'Maestro',
}

interface PublicProfile {
  id: number
  name: string
  last_name?: string | null
  anonymous?: boolean
  can_request_contact?: boolean
  masonic_id?: string | null
  masonic_status?: string | null
  initiation_date?: string | null
  phone?: string | null
  whatsapp?: string | null
  alternative_email?: string | null
  province?: string | null
  locality?: string | null
  profession?: string | null
  occupation?: string | null
  company?: string | null
  bio?: string | null
  workshops?: { id: number; name: string; number: number }[]
  degrees?: { id: number; degree: string; start_date: string; end_date: string | null; workshop?: { name: string } }[]
  positions?: { id: number; start_date: string; end_date: string | null; position?: { name: string }; workshop?: { name: string } }[]
}

interface PersonProfileModalProps {
  personId: number | null
  open: boolean
  onClose: () => void
}

export default function PersonProfileModal({ personId, open, onClose }: PersonProfileModalProps) {
  const { user: me } = useAuth()
  const [profile, setProfile] = useState<PublicProfile | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(false)
  // Vista del modal: perfil (por defecto), formulario de contacto o confirmación.
  const [view, setView] = useState<'profile' | 'contact' | 'sent'>('profile')

  useEffect(() => {
    if (!open || personId === null) { setProfile(null); setError(false); setView('profile'); return }
    setLoading(true); setError(false); setProfile(null); setView('profile')
    client.get<PublicProfile>(`/people/${personId}`)
      .then(r => setProfile(r.data))
      .catch(() => setError(true))
      .finally(() => setLoading(false))
  }, [open, personId])

  const fullName = profile
    ? (profile.last_name ? `${profile.last_name}, ${profile.name}` : profile.name)
    : 'Perfil del Hermano'

  const location = profile ? [profile.locality, profile.province].filter(Boolean).join(', ') : ''
  const hasMasonic = profile && (profile.masonic_id || profile.masonic_status || profile.initiation_date)
  const hasContact = profile && (profile.phone || profile.whatsapp || profile.alternative_email)
  const hasProfession = profile && (profile.profession || profile.occupation || profile.company)

  const isSelf = profile != null && me?.id === profile.id
  const canRequestContact = profile != null && !isSelf && profile.can_request_contact !== false

  return (
    <Modal open={open} onClose={onClose} title={loading ? 'Cargando…' : fullName}>
      {loading ? (
        <div className="ppm-loading"><Spinner /></div>
      ) : error || !profile ? (
        <p className="ppm-empty">No se pudo cargar el perfil.</p>
      ) : view === 'contact' ? (
        <ContactRequestForm
          requesteeId={profile.id}
          requesteeName={profile.name}
          source="search"
          onSent={() => setView('sent')}
          onCancel={() => setView('profile')}
        />
      ) : view === 'sent' ? (
        <div className="ppm-sent">
          <Alert variant="success">Solicitud enviada. El Hermano decidirá si la acepta.</Alert>
          <div className="ppm-actions">
            <Button variant="outline" onClick={() => setView('profile')}>Volver al perfil</Button>
          </div>
        </div>
      ) : (
        <div className="ppm-profile">
          {profile.anonymous && (
            <p className="ppm-anon-note">Este Hermano eligió no revelar su identidad. Solo ves lo que decidió compartir.</p>
          )}

          {profile.bio && <p className="ppm-bio">{profile.bio}</p>}

          {hasMasonic && (
            <div className="ppm-grid">
              {profile.masonic_id && (
                <div className="ppm-field"><span className="ppm-label">Matrícula</span><span>{profile.masonic_id}</span></div>
              )}
              {profile.masonic_status && (
                <div className="ppm-field"><span className="ppm-label">Estado</span><span>{MASONIC_STATUS_LABELS[profile.masonic_status] ?? profile.masonic_status}</span></div>
              )}
              {profile.initiation_date && (
                <div className="ppm-field"><span className="ppm-label">Iniciación</span><span>{formatDate(profile.initiation_date)}</span></div>
              )}
            </div>
          )}

          {hasProfession && (
            <div className="ppm-grid">
              {profile.profession && <div className="ppm-field"><span className="ppm-label">Profesión</span><span>{profile.profession}</span></div>}
              {profile.occupation && <div className="ppm-field"><span className="ppm-label">Ocupación</span><span>{profile.occupation}</span></div>}
              {profile.company && <div className="ppm-field"><span className="ppm-label">Empresa</span><span>{profile.company}</span></div>}
            </div>
          )}

          {location && (
            <div className="ppm-grid">
              <div className="ppm-field"><span className="ppm-label">Ubicación</span><span>{location}</span></div>
            </div>
          )}

          {hasContact && (
            <div className="ppm-grid">
              {profile.phone && <div className="ppm-field"><span className="ppm-label">Teléfono</span><span>{profile.phone}</span></div>}
              {profile.whatsapp && <div className="ppm-field"><span className="ppm-label">WhatsApp</span><span>{profile.whatsapp}</span></div>}
              {profile.alternative_email && <div className="ppm-field"><span className="ppm-label">Email</span><span>{profile.alternative_email}</span></div>}
            </div>
          )}

          {profile.workshops && profile.workshops.length > 0 && (
            <div className="ppm-section">
              <span className="ppm-label">Talleres</span>
              <div className="ppm-tags">
                {profile.workshops.map(w => <span key={w.id} className="ppm-tag">Nº{w.number} {w.name}</span>)}
              </div>
            </div>
          )}

          {profile.degrees && profile.degrees.length > 0 && (
            <div className="ppm-section">
              <span className="ppm-label">Grado</span>
              <div className="ppm-history">
                {profile.degrees.map(d => (
                  <div key={d.id} className="ppm-history-item">
                    <Badge variant="default">{DEGREE_LABELS[d.degree] ?? d.degree}</Badge>
                    {d.workshop?.name && <span className="ppm-history-detail">{d.workshop.name}</span>}
                    <span className="ppm-history-dates">{formatDate(d.start_date)}{d.end_date ? ` – ${formatDate(d.end_date)}` : ''}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {profile.positions && profile.positions.length > 0 && (
            <div className="ppm-section">
              <span className="ppm-label">Cargos</span>
              <div className="ppm-history">
                {profile.positions.map(pos => (
                  <div key={pos.id} className="ppm-history-item">
                    <span className="ppm-cargo">{pos.position?.name ?? '—'}</span>
                    {pos.workshop?.name && <span className="ppm-history-detail">{pos.workshop.name}</span>}
                    <span className="ppm-history-dates">{formatDate(pos.start_date)}{pos.end_date ? ` – ${formatDate(pos.end_date)}` : ''}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {!hasMasonic && !hasProfession && !hasContact && !location && (!profile.degrees || profile.degrees.length === 0) && (!profile.positions || profile.positions.length === 0) && !profile.bio && (
            <p className="ppm-empty">Este Hermano no comparte más datos con vos.</p>
          )}

          {!isSelf && (
            <div className="ppm-actions">
              {canRequestContact ? (
                <Button onClick={() => setView('contact')}>Solicitar contacto</Button>
              ) : (
                <Button
                  disabled
                  title="Este Hermano no acepta solicitudes de contacto desde búsquedas."
                >
                  Solicitar contacto
                </Button>
              )}
            </div>
          )}
        </div>
      )}
    </Modal>
  )
}

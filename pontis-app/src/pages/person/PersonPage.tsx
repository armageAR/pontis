import { useEffect, useState, type FormEvent } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import AppLayout from '@/components/AppLayout'
import Button from '@/components/Button'
import Badge from '@/components/Badge'
import Spinner from '@/components/Spinner'
import Alert from '@/components/Alert'
import Modal from '@/components/Modal'
import FormField from '@/components/FormField'
import PrivacyIndicator from '@/components/PrivacyIndicator'
import { useAuth } from '@/context/AuthContext'
import client from '@/api/client'
import * as contactApi from '@/api/contactRequests'
import type { SharedContactField } from '@/api/contactRequests'
import type { ContactReasonType } from '@/api/contactRequests'
import * as profileApi from '@/api/profile'
import { formatDate } from '@/utils/date'
import './PersonPage.css'

const DEGREE_LABELS: Record<string, string> = { aprendiz: 'Aprendiz', companero: 'Compañero', maestro: 'Maestro' }
const MASONIC_STATUS_LABELS: Record<string, string> = { active: 'Activo', inactive: 'Inactivo', suspended: 'Suspendido', discharged: 'Dado de baja', deceased: 'O Eterno' }
const MASONIC_STATUS_VARIANTS: Record<string, 'default'|'success'|'warning'|'error'> = { active: 'success', inactive: 'default', suspended: 'warning', discharged: 'error', deceased: 'default' }
const SHARED_FIELD_LABELS: Record<SharedContactField, string> = {
  identity: 'Identidad',
  email: 'Email',
  phone: 'Teléfono',
  whatsapp: 'WhatsApp',
  workshop: 'Taller principal',
  profession: 'Profesión/oficio',
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
  can_request_contact?: boolean
}

export default function PersonPage() {
  const { id } = useParams<{ id: string }>()
  const { user: me } = useAuth()
  const navigate = useNavigate()
  const [profile, setProfile] = useState<PublicProfile | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [showContactModal, setShowContactModal] = useState(false)
  const [contactMessage, setContactMessage] = useState('')
  const [sharedFields, setSharedFields] = useState<SharedContactField[]>(['identity'])
  const [contactReason, setContactReason] = useState<ContactReasonType>('profession_search')
  const [sending, setSending] = useState(false)
  const [contactSent, setContactSent] = useState(false)

  useEffect(() => {
    client.get<PublicProfile>(`/people/${id}`)
      .then(r => setProfile(r.data))
      .catch(() => setError('No se pudo cargar el perfil.'))
      .finally(() => setLoading(false))
  }, [id])

  useEffect(() => {
    profileApi.getContactConsent()
      .then(settings => setSharedFields(settings.default_shared_fields))
      .catch(() => {})
  }, [])

  async function handleContact(e: FormEvent) {
    e.preventDefault(); setSending(true)
    try {
      await contactApi.createContactRequest({
        requestee_id: Number(id),
        message: contactMessage,
        shared_fields: sharedFields,
        reason_type: contactReason,
        source: 'search',
      })
      setShowContactModal(false); setContactSent(true)
    } catch (err: any) {
      alert(err?.response?.data?.message ?? 'Error al enviar la solicitud.')
    } finally { setSending(false) }
  }

  if (loading) return <AppLayout><div className="person-loading"><Spinner /></div></AppLayout>
  if (error || !profile) return <AppLayout><Alert>{error || 'Perfil no encontrado.'}</Alert></AppLayout>

  const isSelf = me?.id === profile.id
  const fullName = profile.last_name ? `${profile.last_name}, ${profile.name}` : profile.name
  function toggleSharedField(field: SharedContactField) {
    setSharedFields((current) => current.includes(field)
      ? current.filter((item) => item !== field)
      : [...current, field])
  }

  return (
    <AppLayout>
      <div className="person-page">
        <button className="person-back" onClick={() => navigate(-1)}>← Volver</button>

        <div className="person-hero">
          <div className="person-hero-info">
            <h1 className="person-name-full">{fullName}</h1>
            {profile.masonic_id && <span className="person-mat">Mat. {profile.masonic_id}</span>}
            {profile.masonic_status && (
              <Badge variant={MASONIC_STATUS_VARIANTS[profile.masonic_status] ?? 'default'}>
                {MASONIC_STATUS_LABELS[profile.masonic_status] ?? profile.masonic_status}
              </Badge>
            )}
          </div>
          {!isSelf && !contactSent && profile.can_request_contact !== false && (
            <Button onClick={() => setShowContactModal(true)}>Solicitar contacto</Button>
          )}
          {!isSelf && profile.can_request_contact === false && (
            <Alert>Este Hermano no acepta solicitudes de contacto desde búsquedas.</Alert>
          )}
          {contactSent && <Alert variant="success">Solicitud de contacto enviada.</Alert>}
        </div>

        {profile.bio && (
          <section className="person-section">
            <h2 className="person-section-title">Presentación</h2>
            <p className="person-bio">{profile.bio}</p>
          </section>
        )}

        <section className="person-section">
          <h2 className="person-section-title">Información masónica</h2>
          <div className="person-info-grid">
            {profile.initiation_date && <div><span className="person-label">Fecha de iniciación</span><span>{formatDate(profile.initiation_date)}</span></div>}
            {(profile.province || profile.locality) && <div><span className="person-label">Ubicación</span><span>{[profile.locality, profile.province].filter(Boolean).join(', ')}</span></div>}
            {profile.profession && <div><span className="person-label">Profesión</span><span>{profile.profession}</span></div>}
            {profile.occupation && <div><span className="person-label">Ocupación</span><span>{profile.occupation}</span></div>}
          </div>
        </section>

        {profile.workshops.length > 0 && (
          <section className="person-section">
            <h2 className="person-section-title">Talleres</h2>
            <div className="person-workshops">
              {profile.workshops.map(w => <span key={w.id} className="person-workshop-tag">Nº{w.number} {w.name}</span>)}
            </div>
          </section>
        )}

        {profile.degrees.length > 0 && (
          <section className="person-section">
            <h2 className="person-section-title">Grados masónicos</h2>
            <div className="person-history-list">
              {profile.degrees.map(d => (
                <div key={d.id} className="person-history-item">
                  <Badge variant="default">{DEGREE_LABELS[d.degree] ?? d.degree}</Badge>
                  <span className="person-history-detail">{d.workshop?.name ?? ''}</span>
                  <span className="person-history-dates">{formatDate(d.start_date)}{d.end_date ? ` – ${formatDate(d.end_date)}` : ''}</span>
                </div>
              ))}
            </div>
          </section>
        )}

        {profile.positions.length > 0 && (
          <section className="person-section">
            <h2 className="person-section-title">Cargos</h2>
            <div className="person-history-list">
              {profile.positions.map(p => (
                <div key={p.id} className="person-history-item">
                  <span className="person-history-cargo">{p.position?.name ?? '-'}</span>
                  <span className="person-history-detail">{p.workshop?.name ?? ''}</span>
                  <span className="person-history-dates">{formatDate(p.start_date)}{p.end_date ? ` – ${formatDate(p.end_date)}` : ''}</span>
                </div>
              ))}
            </div>
          </section>
        )}
      </div>

      <Modal open={showContactModal} onClose={() => setShowContactModal(false)} title={`Contactar a ${profile.name}`}>
        <form onSubmit={handleContact} className="person-contact-form">
          <p className="person-contact-info">Elegí qué datos tuyos querés compartir con esta solicitud. Lo que no selecciones no se mostrará al destinatario.</p>
          <FormField label="Mensaje *">
            <textarea className="person-textarea" required rows={4} value={contactMessage} onChange={e => setContactMessage(e.target.value)} placeholder="Presentate y explicá por qué querés contactarte..." />
          </FormField>
          <FormField label="Motivo *">
            <select className="person-select" required value={contactReason} onChange={e => setContactReason(e.target.value as ContactReasonType)}>
              <option value="profession_search">Lo encontré por profesión u oficio</option>
              <option value="workshop_location_search">Lo encontré por Taller o ubicación</option>
              <option value="other">Otro motivo</option>
            </select>
          </FormField>
          <FormField label="Datos a compartir">
            <div className="person-shared-fields">
              {Object.entries(SHARED_FIELD_LABELS).map(([value, label]) => (
                <label key={value} className="person-shared-field">
                  <input
                    type="checkbox"
                    checked={sharedFields.includes(value as SharedContactField)}
                    onChange={() => toggleSharedField(value as SharedContactField)}
                  />
                  <span>{label}</span>
                </label>
              ))}
            </div>
          </FormField>
          <PrivacyIndicator
            summary={`Vas a compartir: ${sharedFields.map(field => SHARED_FIELD_LABELS[field]).join(', ') || 'ningún dato de contacto'}.`}
            details={[
              'El destinatario verá tu mensaje y el motivo de contacto.',
              'Los campos no seleccionados no se incluyen en la solicitud.',
              'La solicitud queda registrada para seguimiento interno.',
            ]}
          />
          <div className="person-modal-actions">
            <Button type="button" variant="outline" onClick={() => setShowContactModal(false)}>Cancelar</Button>
            <Button type="submit" loading={sending}>Enviar solicitud</Button>
          </div>
        </form>
      </Modal>
    </AppLayout>
  )
}

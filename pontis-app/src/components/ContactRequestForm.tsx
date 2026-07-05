import { useEffect, useState, type FormEvent } from 'react'
import Button from './Button'
import FormField from './FormField'
import PrivacyIndicator from './PrivacyIndicator'
import * as contactApi from '@/api/contactRequests'
import type { ContactReasonType, SharedContactField } from '@/api/contactRequests'
import * as profileApi from '@/api/profile'
import { apiError } from '@/utils/errors'
import './ContactRequestForm.css'

const SHARED_FIELD_LABELS: Record<SharedContactField, string> = {
  identity: 'Identidad',
  email: 'Email',
  phone: 'Teléfono',
  whatsapp: 'WhatsApp',
  workshop: 'Taller principal',
  profession: 'Profesión/oficio',
}

interface ContactRequestFormProps {
  requesteeId: number
  requesteeName: string
  source?: 'search'
  onSent: () => void
  onCancel: () => void
}

/**
 * Formulario reutilizable de solicitud de contacto (sin envoltura de Modal).
 * Lo usan la página completa de perfil y el modal de búsqueda. El motivo no
 * tiene default efectivo: el usuario debe elegir uno antes de enviar.
 */
export default function ContactRequestForm({ requesteeId, requesteeName, source = 'search', onSent, onCancel }: ContactRequestFormProps) {
  const [message, setMessage] = useState('')
  const [reason, setReason] = useState<ContactReasonType | ''>('')
  const [sharedFields, setSharedFields] = useState<SharedContactField[]>(['identity'])
  const [sending, setSending] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    profileApi.getContactConsent()
      .then(settings => setSharedFields(settings.default_shared_fields))
      .catch(() => {})
  }, [])

  function toggleSharedField(field: SharedContactField) {
    setSharedFields(current => current.includes(field)
      ? current.filter(item => item !== field)
      : [...current, field])
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    if (!reason) return // El motivo es obligatorio; el select `required` ya lo bloquea.
    setSending(true); setError('')
    try {
      await contactApi.createContactRequest({
        requestee_id: requesteeId,
        message,
        shared_fields: sharedFields,
        reason_type: reason,
        source,
      })
      onSent()
    } catch (err) {
      setError(apiError(err, 'Error al enviar la solicitud.'))
    } finally {
      setSending(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} className="crf-form">
      <p className="crf-info">
        Vas a contactar a <strong>{requesteeName}</strong>. Elegí qué datos tuyos querés compartir;
        lo que no selecciones no se mostrará al destinatario.
      </p>
      <FormField label="Mensaje *">
        <textarea className="crf-textarea" required rows={4} value={message} onChange={e => setMessage(e.target.value)} placeholder="Presentate y explicá por qué querés contactarte..." />
      </FormField>
      <FormField label="Motivo *">
        <select className="crf-select" required value={reason} onChange={e => setReason(e.target.value as ContactReasonType)}>
          <option value="" disabled>Seleccioná un motivo</option>
          <option value="profession_search">Lo encontré por profesión u oficio</option>
          <option value="workshop_location_search">Lo encontré por Taller o ubicación</option>
          <option value="other">Otro motivo</option>
        </select>
      </FormField>
      <FormField label="Datos a compartir">
        <div className="crf-shared-fields">
          {Object.entries(SHARED_FIELD_LABELS).map(([value, label]) => (
            <label key={value} className="crf-shared-field">
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
      {error && <p className="crf-error">{error}</p>}
      <div className="crf-actions">
        <Button type="button" variant="outline" onClick={onCancel}>Cancelar</Button>
        <Button type="submit" loading={sending}>Enviar solicitud</Button>
      </div>
    </form>
  )
}

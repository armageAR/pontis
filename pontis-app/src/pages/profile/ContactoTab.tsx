import type { ReactNode } from 'react'
import InlineField from './InlineField'
import type { Profile, ContactConsentSettings, ContactSource } from '@/api/profile'
import type { VisibilityBlock } from '@/api/visibility'
import type { FieldStatus } from './useProfileAutosave'

interface ContactoTabProps {
  profile: Profile
  statuses: Record<string, FieldStatus>
  commitField: (key: keyof Profile, value: string | null) => Promise<void>
  sectionPrivacy: (block: VisibilityBlock) => ReactNode
  contactConsent: ContactConsentSettings | null
  savingContactConsent: boolean
  toggleContactSource: (value: ContactSource) => void
}

export default function ContactoTab({ profile, statuses, commitField, sectionPrivacy, contactConsent, savingContactConsent, toggleContactSource }: ContactoTabProps) {
  return (
    <div className="profile-fields">
      <InlineField label="Teléfono móvil" value={profile.phone ?? ''} status={statuses.phone} onCommit={v => commitField('phone', v)} />
      <InlineField label="Teléfono fijo" value={profile.phone_fixed ?? ''} status={statuses.phone_fixed} onCommit={v => commitField('phone_fixed', v)} />
      <InlineField label="WhatsApp" value={profile.whatsapp ?? ''} status={statuses.whatsapp} onCommit={v => commitField('whatsapp', v)} />
      <InlineField label="Email alternativo" type="email" validate="email" value={profile.alternative_email ?? ''} status={statuses.alternative_email} onCommit={v => commitField('alternative_email', v)} />
      <InlineField label="LinkedIn" type="url" validate="url" placeholder="https://linkedin.com/in/..." value={profile.linkedin ?? ''} status={statuses.linkedin} onCommit={v => commitField('linkedin', v)} />
      <InlineField label="Sitio web" type="url" validate="url" placeholder="https://..." value={profile.website ?? ''} status={statuses.website} onCommit={v => commitField('website', v)} />
      <InlineField label="Instagram" placeholder="@usuario" value={profile.instagram ?? ''} status={statuses.instagram} onCommit={v => commitField('instagram', v)} />
      <InlineField label="Facebook" placeholder="URL o usuario" value={profile.facebook ?? ''} status={statuses.facebook} onCommit={v => commitField('facebook', v)} />
      <InlineField label="Disponibilidad / notas de horario" as="textarea" rows={2} placeholder="Ej: disponible de lunes a viernes por las tardes..." value={profile.availability_notes ?? ''} status={statuses.availability_notes} onCommit={v => commitField('availability_notes', v)} />
      {sectionPrivacy('contact')}
      {contactConsent && (
        <div className="profile-contact-consent">
          <h3 className="profile-subsection-title">Solicitudes de contacto</h3>
          <p className="profile-section-desc">
            Elegí desde dónde otros Hermanos pueden enviarte solicitudes. Tus datos no se comparten hasta que aceptes o respondas.
          </p>
          <label className="profile-check-row">
            <input
              type="checkbox"
              disabled={savingContactConsent}
              checked={contactConsent.allowed_sources.includes('search')}
              onChange={() => toggleContactSource('search')}
            />
            <span>Desde búsquedas de Hermanos</span>
          </label>
          <label className="profile-check-row">
            <input
              type="checkbox"
              disabled={savingContactConsent}
              checked={contactConsent.allowed_sources.includes('publications')}
              onChange={() => toggleContactSource('publications')}
            />
            <span>Desde publicaciones</span>
          </label>
        </div>
      )}
    </div>
  )
}

import type { ReactNode } from 'react'
import FormField from '@/components/FormField'
import Input from '@/components/Input'
import Button from '@/components/Button'
import InlineField from './InlineField'
import SensitiveField from './SensitiveField'
import type { Profile } from '@/api/profile'
import type { FieldStatus } from './useProfileAutosave'
import { toDateInputValue } from '@/utils/date'

interface IdentidadTabProps {
  profile: Profile
  isSuperAdmin: boolean
  statuses: Record<string, FieldStatus>
  commitField: (key: keyof Profile, value: string | null) => Promise<void>
  onOpenEmailModal: () => void
  identityPrivacy: () => ReactNode
}

export default function IdentidadTab({ profile, isSuperAdmin, statuses, commitField, onOpenEmailModal, identityPrivacy }: IdentidadTabProps) {
  return (
    <div className="profile-fields">
      <SensitiveField label="Nombre" fieldKey="name" value={profile.name ?? ''} isSuperAdmin={isSuperAdmin} status={statuses.name} onCommit={v => commitField('name', v)} />
      <SensitiveField label="Apellido" fieldKey="last_name" value={profile.last_name ?? ''} isSuperAdmin={isSuperAdmin} status={statuses.last_name} onCommit={v => commitField('last_name', v)} />
      <SensitiveField label="DNI / Documento" fieldKey="dni" value={profile.dni ?? ''} isSuperAdmin={isSuperAdmin} status={statuses.dni} onCommit={v => commitField('dni', v)} />
      <FormField label="Email" status="locked">
        <div className="profile-email-field">
          <Input value={profile.email ?? ''} disabled />
          <Button type="button" variant="outline" onClick={onOpenEmailModal}>Cambiar</Button>
        </div>
        {profile.pending_email && (
          <p className="profile-email-pending">
            Cambio pendiente: confirmá desde el correo que enviamos a <strong>{profile.pending_email}</strong>.
          </p>
        )}
      </FormField>
      <InlineField label="Fecha de nacimiento" type="date" value={toDateInputValue(profile.birth_date)} status={statuses.birth_date} onCommit={v => commitField('birth_date', v)} />
      <InlineField label="Foto de perfil (URL)" type="url" validate="url" placeholder="https://..." value={profile.photo_url ?? ''} status={statuses.photo_url} onCommit={v => commitField('photo_url', v)} />
      {identityPrivacy()}
    </div>
  )
}

import type { ReactNode } from 'react'
import FormField from '@/components/FormField'
import InlineField from './InlineField'
import type { Profile } from '@/api/profile'
import type { Province } from '@/api/provinces'
import type { VisibilityBlock } from '@/api/visibility'
import type { FieldStatus } from './useProfileAutosave'

interface UbicacionTabProps {
  profile: Profile
  statuses: Record<string, FieldStatus>
  commitField: (key: keyof Profile, value: string | null) => Promise<void>
  provinces: Province[]
  localities: { id: number; name: string }[]
  onProvinceChange: (value: string) => void
  sectionPrivacy: (block: VisibilityBlock) => ReactNode
}

export default function UbicacionTab({ profile, statuses, commitField, provinces, localities, onProvinceChange, sectionPrivacy }: UbicacionTabProps) {
  return (
    <div className="profile-fields">
      <InlineField label="País" value={profile.country ?? ''} status={statuses.country} onCommit={v => commitField('country', v)} />
      <FormField label="Provincia" status={statuses.province === 'saving' ? 'saving' : statuses.province === 'saved' ? 'saved' : statuses.province === 'error' ? 'error' : undefined}>
        <select className="profile-select" value={profile.province ?? ''} onChange={e => onProvinceChange(e.target.value)}>
          <option value="">-- Seleccionar --</option>
          {provinces.map(p => <option key={p.id} value={p.name}>{p.name}</option>)}
        </select>
      </FormField>
      <FormField label="Localidad" status={statuses.locality === 'saving' ? 'saving' : statuses.locality === 'saved' ? 'saved' : statuses.locality === 'error' ? 'error' : undefined}>
        <select className="profile-select" value={profile.locality ?? ''} onChange={e => commitField('locality', e.target.value)}>
          <option value="">-- Seleccionar --</option>
          {localities.map(l => <option key={l.id} value={l.name}>{l.name}</option>)}
        </select>
      </FormField>
      <InlineField label="Barrio" value={profile.neighborhood ?? ''} status={statuses.neighborhood} onCommit={v => commitField('neighborhood', v)} />
      <InlineField label="Dirección" value={profile.address ?? ''} status={statuses.address} onCommit={v => commitField('address', v)} />
      {sectionPrivacy('location')}
    </div>
  )
}

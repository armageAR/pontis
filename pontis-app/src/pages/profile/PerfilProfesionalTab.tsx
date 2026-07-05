import type { ReactNode } from 'react'
import InlineField from './InlineField'
import type { Profile } from '@/api/profile'
import type { VisibilityBlock } from '@/api/visibility'
import type { FieldStatus } from './useProfileAutosave'

interface PerfilProfesionalTabProps {
  profile: Profile
  statuses: Record<string, FieldStatus>
  commitField: (key: keyof Profile, value: string | null) => Promise<void>
  sectionPrivacy: (block: VisibilityBlock) => ReactNode
}

export default function PerfilProfesionalTab({ profile, statuses, commitField, sectionPrivacy }: PerfilProfesionalTabProps) {
  return (
    <div className="profile-fields">
      <InlineField label="Profesión" value={profile.profession ?? ''} status={statuses.profession} onCommit={v => commitField('profession', v)} />
      <InlineField label="Ocupación / Cargo" value={profile.occupation ?? ''} status={statuses.occupation} onCommit={v => commitField('occupation', v)} />
      <InlineField label="Empresa / Organización" value={profile.company ?? ''} status={statuses.company} onCommit={v => commitField('company', v)} />
      <InlineField label="Descripción profesional" as="textarea" rows={3} value={profile.profession_description ?? ''} status={statuses.profession_description} onCommit={v => commitField('profession_description', v)} />
      <InlineField label="Actividades secundarias" as="textarea" rows={2} placeholder="Otras actividades profesionales o laborales" value={profile.secondary_activities ?? ''} status={statuses.secondary_activities} onCommit={v => commitField('secondary_activities', v)} />
      <InlineField label="Áreas de conocimiento" as="textarea" rows={2} placeholder="Ej: derecho laboral, desarrollo web, diseño gráfico..." value={profile.knowledge_areas ?? ''} status={statuses.knowledge_areas} onCommit={v => commitField('knowledge_areas', v)} />
      <InlineField label="Matrículas / Habilitaciones" as="textarea" rows={2} placeholder="Ej: Abogado matriculado (CABA), Contador habilitado..." value={profile.certifications ?? ''} status={statuses.certifications} onCommit={v => commitField('certifications', v)} />
      {sectionPrivacy('profession')}
      <InlineField label="Bio" as="textarea" rows={4} placeholder="Contá algo sobre vos..." value={profile.bio ?? ''} status={statuses.bio} onCommit={v => commitField('bio', v)} />
      {sectionPrivacy('bio')}
    </div>
  )
}

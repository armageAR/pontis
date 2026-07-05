import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Pencil } from 'lucide-react'
import FormField from '@/components/FormField'
import ConfirmDialog from '@/components/ConfirmDialog'
import InlineField from './InlineField'
import type { FieldStatus } from './useProfileAutosave'

export type SensitiveFieldKey = 'name' | 'last_name' | 'dni' | 'masonic_id'

interface SensitiveFieldProps {
  label: string
  fieldKey: SensitiveFieldKey
  value: string
  isSuperAdmin: boolean
  status?: FieldStatus
  onCommit?: (value: string) => Promise<void> | void
}

// Dato sensible: para superadmin se edita inline; para el resto es una fila
// bloqueada de solo lectura cuyo cambio se inicia como trámite validable.
export default function SensitiveField({ label, fieldKey, value, isSuperAdmin, status, onCommit }: SensitiveFieldProps) {
  const navigate = useNavigate()
  const [confirm, setConfirm] = useState(false)

  if (isSuperAdmin && onCommit) {
    return <InlineField label={label} value={value} status={status} onCommit={onCommit} />
  }

  return (
    <FormField label={label} status="locked">
      <div className="profile-locked-row">
        <span className="profile-locked-value">{value || '—'}</span>
        <button
          type="button"
          className="profile-pencil-btn"
          title={`Solicitar cambio de ${label}`}
          aria-label={`Solicitar cambio de ${label}`}
          onClick={() => setConfirm(true)}
        >
          <Pencil size={16} />
        </button>
      </div>
      <ConfirmDialog
        open={confirm}
        title={`Solicitar cambio de ${label}`}
        message={`${label} es un dato sensible. Su modificación se gestiona como un trámite que requiere la validación de un administrador. ¿Querés iniciar la solicitud?`}
        confirmLabel="Solicitar cambio"
        onConfirm={() => { setConfirm(false); navigate(`/bandeja?tramite=${fieldKey}`) }}
        onClose={() => setConfirm(false)}
      />
    </FormField>
  )
}

import { useState } from 'react'
import type { UserListItem } from '@/api/users'
import Spinner from '@/components/Spinner'
import Badge from '@/components/Badge'
import './UserStatusSelect.css'

const STATUS_OPTIONS = [
  { value: 'active', label: 'Activo' },
  { value: 'pending', label: 'Pendiente' },
  { value: 'rejected', label: 'Rechazado' },
  { value: 'suspended', label: 'Suspendido' },
  { value: 'inactive', label: 'Baja' },
]

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'error' | 'default'> = {
  active: 'success',
  verifying: 'warning',
  pending: 'warning',
  rejected: 'error',
  suspended: 'error',
  inactive: 'default',
}

const STATUS_LABELS: Record<string, string> = {
  active: 'Activo',
  verifying: 'Verificando',
  pending: 'Pendiente',
  rejected: 'Rechazado',
  suspended: 'Suspendido',
  inactive: 'Baja',
}

interface UserStatusSelectProps {
  user: UserListItem
  editable: boolean
  onStatusChange: (userId: number, status: string) => Promise<void>
}

export default function UserStatusSelect({ user, editable, onStatusChange }: UserStatusSelectProps) {
  const [saving, setSaving] = useState(false)

  // Si el email no está verificado, el estado mostrado es "Verificando"
  // sin importar el status almacenado. Al activarlo, el backend sella la fecha.
  const displayStatus = user.email_verified_at ? user.status : 'verifying'

  if (!editable) {
    return (
      <Badge variant={STATUS_VARIANT[displayStatus] ?? 'default'}>
        {STATUS_LABELS[displayStatus] ?? displayStatus}
      </Badge>
    )
  }

  async function handleChange(newStatus: string) {
    if (newStatus === displayStatus) return
    setSaving(true)
    try {
      await onStatusChange(user.id, newStatus)
    } finally {
      setSaving(false)
    }
  }

  if (saving) {
    return <Spinner size={16} />
  }

  return (
    <select
      className={`user-status-select user-status-select-${displayStatus}`}
      value={displayStatus}
      onChange={(e) => handleChange(e.target.value)}
    >
      {displayStatus === 'verifying' && (
        <option value="verifying">Verificando</option>
      )}
      {STATUS_OPTIONS.map((opt) => (
        <option key={opt.value} value={opt.value}>{opt.label}</option>
      ))}
    </select>
  )
}

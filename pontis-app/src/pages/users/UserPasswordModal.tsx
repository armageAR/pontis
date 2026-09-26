import { type FormEvent, useState } from 'react'
import type { UserListItem } from '@/api/users'
import Modal from '@/components/Modal'
import FormField from '@/components/FormField'
import Input from '@/components/Input'
import Button from '@/components/Button'
import Alert from '@/components/Alert'
import type { AxiosError } from 'axios'
import './UserPasswordModal.css'

interface UserPasswordModalProps {
  user: UserListItem | null
  open: boolean
  onClose: () => void
  onSave: (userId: number, password: string, confirmation: string) => Promise<void>
}

type FieldErrors = Record<string, string[]>

export default function UserPasswordModal({ user, open, onClose, onSave }: UserPasswordModalProps) {
  const [password, setPassword] = useState('')
  const [confirmation, setConfirmation] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({})

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    if (!user) return
    setError('')
    setFieldErrors({})
    setLoading(true)

    try {
      await onSave(user.id, password, confirmation)
      onClose()
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string; errors?: FieldErrors }>
      if (axiosErr.response?.status === 422) {
        setFieldErrors(axiosErr.response.data.errors ?? {})
        setError(axiosErr.response.data.message ?? 'Datos inválidos.')
      } else {
        const msg = axiosErr.response?.data?.message ?? 'Error al guardar. Intentá de nuevo.'
        setError(msg)
      }
    } finally {
      setLoading(false)
    }
  }

  if (!user) return null

  return (
    <Modal open={open} onClose={onClose} title={`Cambiar contraseña — ${user.name}`}>
      <form className="user-password-form" onSubmit={handleSubmit}>
        {error && <Alert>{error}</Alert>}

        <p className="user-password-hint">
          La nueva contraseña debe tener al menos 8 caracteres.
        </p>

        <FormField label="Nueva contraseña" error={fieldErrors.password?.[0]}>
          <Input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            error={!!fieldErrors.password}
            required
            autoFocus
            autoComplete="new-password"
          />
        </FormField>

        <FormField label="Confirmar contraseña">
          <Input
            type="password"
            value={confirmation}
            onChange={(e) => setConfirmation(e.target.value)}
            required
            autoComplete="new-password"
          />
        </FormField>

        <div className="user-password-actions">
          <Button variant="outline" type="button" onClick={onClose} disabled={loading}>
            Cancelar
          </Button>
          <Button type="submit" loading={loading}>
            Cambiar contraseña
          </Button>
        </div>
      </form>
    </Modal>
  )
}

import { type FormEvent, useEffect, useState } from 'react'
import type { UserListItem, UserUpdatePayload } from '@/api/users'
import Modal from '@/components/Modal'
import FormField from '@/components/FormField'
import Input from '@/components/Input'
import Select from '@/components/Select'
import Button from '@/components/Button'
import Alert from '@/components/Alert'
import type { AxiosError } from 'axios'
import './UserEditModal.css'

interface UserEditModalProps {
  user: UserListItem | null
  open: boolean
  onClose: () => void
  onSave: (userId: number, payload: UserUpdatePayload) => Promise<void>
  currentUserRole: string
}

type FieldErrors = Record<string, string[]>

export default function UserEditModal({ user, open, onClose, onSave, currentUserRole }: UserEditModalProps) {
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [role, setRole] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({})

  useEffect(() => {
    if (user) {
      setName(user.name)
      setEmail(user.email)
      setRole(user.role ?? 'user')
      setError('')
      setFieldErrors({})
    }
  }, [user])

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    if (!user) return
    setError('')
    setFieldErrors({})
    setLoading(true)

    try {
      const payload: UserUpdatePayload = {}
      if (name !== user.name) payload.name = name
      if (email !== user.email) payload.email = email
      if (role !== user.role && currentUserRole === 'superadmin') payload.role = role

      if (Object.keys(payload).length === 0) {
        onClose()
        return
      }

      await onSave(user.id, payload)
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
    <Modal open={open} onClose={onClose} title="Editar usuario">
      <form className="user-edit-form" onSubmit={handleSubmit}>
        {error && <Alert>{error}</Alert>}

        <FormField label="Nombre" error={fieldErrors.name?.[0]}>
          <Input
            value={name}
            onChange={(e) => setName(e.target.value)}
            error={!!fieldErrors.name}
            required
            autoFocus
          />
        </FormField>

        <FormField label="Email" error={fieldErrors.email?.[0]}>
          <Input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            error={!!fieldErrors.email}
            required
          />
        </FormField>

        {currentUserRole === 'superadmin' && (
          <FormField label="Rol" error={fieldErrors.role?.[0]}>
            <Select value={role} onChange={(e) => setRole(e.target.value)}>
              <option value="user">Usuario</option>
              <option value="admin">Admin</option>
              <option value="superadmin">Super Admin</option>
            </Select>
          </FormField>
        )}

        <div className="user-edit-actions">
          <Button variant="outline" type="button" onClick={onClose} disabled={loading}>
            Cancelar
          </Button>
          <Button type="submit" loading={loading}>
            Guardar cambios
          </Button>
        </div>
      </form>
    </Modal>
  )
}

import { type FormEvent, useState } from 'react'
import { X, ShieldCheck, User as UserIcon } from 'lucide-react'
import type { UserListItem, UserUpdatePayload, UserWorkshop, WorkshopOption } from '@/api/users'
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
  allWorkshops: WorkshopOption[]
  onWorkshopAdd: (userId: number, workshopId: number) => Promise<UserListItem>
  onWorkshopRemove: (userId: number, workshopId: number) => Promise<UserListItem>
  onWorkshopRoleToggle: (userId: number, workshopId: number, newRole: 'admin' | 'member') => Promise<UserListItem>
}

type FieldErrors = Record<string, string[]>

export default function UserEditModal({
  user,
  open,
  onClose,
  onSave,
  allWorkshops,
  onWorkshopAdd,
  onWorkshopRemove,
  onWorkshopRoleToggle,
}: UserEditModalProps) {
  // Seeded from the user being edited instead of synced by an effect. UsersPage
  // keys this modal by user id, so opening it — or switching users — remounts the
  // component and these initial values run again.
  const [name, setName] = useState(user?.name ?? '')
  const [email, setEmail] = useState(user?.email ?? '')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({})

  const [localWorkshops, setLocalWorkshops] = useState<UserWorkshop[]>(user?.workshops ?? [])
  const [workshopToAdd, setWorkshopToAdd] = useState('')
  const [workshopLoading, setWorkshopLoading] = useState<string | null>(null)
  const [workshopError, setWorkshopError] = useState('')

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

  async function handleRoleToggle(workshop: UserWorkshop) {
    if (!user) return
    const newRole = workshop.workshop_role === 'admin' ? 'member' : 'admin'
    setWorkshopLoading(`role-${workshop.id}`)
    setWorkshopError('')
    try {
      const updated = await onWorkshopRoleToggle(user.id, workshop.id, newRole)
      setLocalWorkshops(updated.workshops ?? [])
    } catch (err) {
      const msg = (err as AxiosError<{ message?: string }>)?.response?.data?.message ?? 'Error al cambiar el rol.'
      setWorkshopError(msg)
    } finally {
      setWorkshopLoading(null)
    }
  }

  async function handleRemoveWorkshop(workshopId: number) {
    if (!user) return
    setWorkshopLoading(`remove-${workshopId}`)
    setWorkshopError('')
    try {
      const updated = await onWorkshopRemove(user.id, workshopId)
      setLocalWorkshops(updated.workshops ?? [])
    } catch (err) {
      const msg = (err as AxiosError<{ message?: string }>)?.response?.data?.message ?? 'Error al quitar del taller.'
      setWorkshopError(msg)
    } finally {
      setWorkshopLoading(null)
    }
  }

  async function handleAddWorkshop() {
    if (!user || !workshopToAdd) return
    setWorkshopLoading('add')
    setWorkshopError('')
    try {
      const updated = await onWorkshopAdd(user.id, parseInt(workshopToAdd))
      setLocalWorkshops(updated.workshops ?? [])
      setWorkshopToAdd('')
    } catch (err) {
      const msg = (err as AxiosError<{ message?: string }>)?.response?.data?.message ?? 'Error al agregar al taller.'
      setWorkshopError(msg)
    } finally {
      setWorkshopLoading(null)
    }
  }

  if (!user) return null

  const assignedIds = new Set(localWorkshops.map((w) => w.id))
  const availableToAdd = allWorkshops.filter((w) => !assignedIds.has(w.id))

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

        <div className="user-edit-actions">
          <Button variant="outline" type="button" onClick={onClose} disabled={loading}>
            Cancelar
          </Button>
          <Button type="submit" loading={loading}>
            Guardar cambios
          </Button>
        </div>
      </form>

      <div className="user-workshops-section">
        <p className="user-workshops-title">Talleres</p>

        {workshopError && <Alert>{workshopError}</Alert>}

        {localWorkshops.length === 0 ? (
          <p className="user-workshops-empty">Sin talleres asignados.</p>
        ) : (
          <ul className="user-workshop-list">
            {localWorkshops.map((w) => {
              const isAdmin = w.workshop_role === 'admin'
              const roleKey = `role-${w.id}`
              const removeKey = `remove-${w.id}`
              return (
                <li key={w.id} className="user-workshop-item">
                  <span className="user-workshop-name">#{w.number} · {w.name}</span>
                  <button
                    type="button"
                    className={`user-workshop-role-btn ${isAdmin ? 'role-admin' : 'role-member'}`}
                    onClick={() => handleRoleToggle(w)}
                    disabled={workshopLoading === roleKey || workshopLoading === removeKey}
                    title={isAdmin ? 'Click para cambiar a Miembro' : 'Click para cambiar a Admin'}
                  >
                    {isAdmin ? <ShieldCheck size={12} /> : <UserIcon size={12} />}
                    {isAdmin ? 'Admin' : 'Miembro'}
                  </button>
                  <button
                    type="button"
                    className="user-workshop-remove-btn"
                    onClick={() => handleRemoveWorkshop(w.id)}
                    disabled={workshopLoading === removeKey || workshopLoading === roleKey}
                    aria-label="Quitar del taller"
                  >
                    <X size={14} />
                  </button>
                </li>
              )
            })}
          </ul>
        )}

        {availableToAdd.length > 0 && (
          <div className="user-workshops-add">
            <Select
              value={workshopToAdd}
              onChange={(e) => setWorkshopToAdd(e.target.value)}
              className="user-workshops-select"
            >
              <option value="">Seleccionar taller...</option>
              {availableToAdd.map((w) => (
                <option key={w.id} value={w.id}>#{w.number} · {w.name}</option>
              ))}
            </Select>
            <Button
              type="button"
              variant="outline"
              loading={workshopLoading === 'add'}
              disabled={!workshopToAdd}
              onClick={handleAddWorkshop}
            >
              Agregar
            </Button>
          </div>
        )}
      </div>
    </Modal>
  )
}

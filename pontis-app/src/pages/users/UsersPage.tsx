import { useCallback, useEffect, useState } from 'react'
import * as api from '@/api/users'
import type { UserListItem, UserFilters as Filters, UserUpdatePayload } from '@/api/users'
import { useAuth } from '@/context/AuthContext'
import AppLayout from '@/components/AppLayout'
import Spinner from '@/components/Spinner'
import Pagination from '@/components/Pagination'
import EmptyState from '@/components/EmptyState'
import Alert from '@/components/Alert'
import UserFiltersBar from './UserFilters'
import UserTable from './UserTable'
import UserEditModal from './UserEditModal'
import UserPasswordModal from './UserPasswordModal'
import './UsersPage.css'

export default function UsersPage() {
  const { user: currentUser } = useAuth()
  const [users, setUsers] = useState<UserListItem[]>([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
  const [filters, setFilters] = useState<Filters>({ sort_by: 'name', sort_direction: 'asc', page: 1 })
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [successMsg, setSuccessMsg] = useState('')

  const [editingUser, setEditingUser] = useState<UserListItem | null>(null)
  const [passwordUser, setPasswordUser] = useState<UserListItem | null>(null)

  const fetchUsers = useCallback(async (f: Filters) => {
    setLoading(true)
    setError('')
    try {
      const res = await api.listUsers(f)
      setUsers(res.data)
      setMeta(res.meta)
    } catch {
      setError('No se pudieron cargar los usuarios.')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    fetchUsers(filters)
  }, [filters, fetchUsers])

  function updateFilters(partial: Partial<Filters>) {
    setFilters((prev) => ({ ...prev, ...partial }))
  }

  function notify(msg: string) {
    setSuccessMsg(msg)
    setError('')
  }

  function notifyError(msg: string) {
    setError(msg)
    setSuccessMsg('')
  }

  function patchUser(updated: UserListItem) {
    setUsers((prev) => prev.map((u) => (u.id === updated.id ? updated : u)))
  }

  async function handleStatusChange(userId: number, status: string) {
    try {
      const updated = await api.updateUserStatus(userId, status)
      patchUser(updated)
      notify(`Estado de ${updated.name} actualizado.`)
    } catch (err) {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message
        ?? 'No se pudo actualizar el estado.'
      notifyError(msg)
    }
  }

  async function handleEdit(userId: number, payload: UserUpdatePayload) {
    const updated = await api.updateUser(userId, payload)
    patchUser(updated)
    notify(`${updated.name} actualizado correctamente.`)
  }

  async function handlePassword(userId: number, password: string, confirmation: string) {
    await api.updateUserPassword(userId, password, confirmation)
    const target = users.find((u) => u.id === userId)
    notify(`Contraseña de ${target?.name ?? 'usuario'} actualizada.`)
  }

  return (
    <AppLayout>
      <div className="users-header">
        <h1 className="users-title">Usuarios</h1>
      </div>

      {successMsg && <Alert variant="success">{successMsg}</Alert>}
      {error && <Alert variant="error">{error}</Alert>}

      <UserFiltersBar filters={filters} onChange={updateFilters} />

      {loading ? (
        <div className="users-loading">
          <Spinner size={28} />
        </div>
      ) : users.length === 0 ? (
        <EmptyState
          title="No se encontraron usuarios"
          description={filters.search ? 'Probá con otros filtros o términos de búsqueda.' : 'No hay usuarios registrados aún.'}
        />
      ) : (
        <>
          <UserTable
            users={users}
            sortBy={filters.sort_by ?? 'name'}
            sortDirection={filters.sort_direction ?? 'asc'}
            onSort={updateFilters}
            currentUserId={currentUser?.id ?? 0}
            currentUserRole={currentUser?.role ?? 'user'}
            onStatusChange={handleStatusChange}
            onEdit={setEditingUser}
            onChangePassword={setPasswordUser}
          />
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            total={meta.total}
            onPageChange={(page) => updateFilters({ page })}
          />
        </>
      )}

      <UserEditModal
        user={editingUser}
        open={editingUser !== null}
        onClose={() => setEditingUser(null)}
        onSave={handleEdit}
        currentUserRole={currentUser?.role ?? 'user'}
      />

      <UserPasswordModal
        user={passwordUser}
        open={passwordUser !== null}
        onClose={() => setPasswordUser(null)}
        onSave={handlePassword}
      />
    </AppLayout>
  )
}

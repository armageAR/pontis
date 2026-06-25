import { useCallback, useEffect, useState } from 'react'
import * as api from '@/api/users'
import type { UserListItem, UserFilters as Filters } from '@/api/users'
import { useAuth } from '@/context/AuthContext'
import AppLayout from '@/components/AppLayout'
import Spinner from '@/components/Spinner'
import Pagination from '@/components/Pagination'
import EmptyState from '@/components/EmptyState'
import Alert from '@/components/Alert'
import UserFiltersBar from './UserFilters'
import UserTable from './UserTable'
import './UsersPage.css'

export default function UsersPage() {
  const { user: currentUser } = useAuth()
  const [users, setUsers] = useState<UserListItem[]>([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
  const [filters, setFilters] = useState<Filters>({ sort_by: 'name', sort_direction: 'asc', page: 1 })
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [successMsg, setSuccessMsg] = useState('')

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

  async function handleStatusChange(userId: number, status: string) {
    setError('')
    setSuccessMsg('')
    try {
      const updated = await api.updateUserStatus(userId, status)
      setUsers((prev) => prev.map((u) => (u.id === userId ? updated : u)))
      setSuccessMsg(`Estado de ${updated.name} actualizado.`)
    } catch (err) {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message
        ?? 'No se pudo actualizar el estado.'
      setError(msg)
    }
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
          />
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            total={meta.total}
            onPageChange={(page) => updateFilters({ page })}
          />
        </>
      )}
    </AppLayout>
  )
}

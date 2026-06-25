import type { UserListItem, UserFilters } from '@/api/users'
import UserStatusSelect from './UserStatusSelect'
import './UserTable.css'

interface UserTableProps {
  users: UserListItem[]
  sortBy: string
  sortDirection: 'asc' | 'desc'
  onSort: (filters: Partial<UserFilters>) => void
  currentUserId: number
  currentUserRole: string
  onStatusChange: (userId: number, status: string) => Promise<void>
}

const ROLE_LABELS: Record<string, string> = {
  superadmin: 'Super Admin',
  admin: 'Admin',
  user: 'Usuario',
}

interface Column {
  key: string
  label: string
  sortable: boolean
}

const COLUMNS: Column[] = [
  { key: 'name', label: 'Nombre', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'role', label: 'Rol', sortable: true },
  { key: 'workshops', label: 'Talleres', sortable: false },
  { key: 'status', label: 'Estado', sortable: true },
  { key: 'created_at', label: 'Registro', sortable: true },
]

function formatDate(d: string): string {
  return new Date(d).toLocaleDateString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

export default function UserTable({
  users,
  sortBy,
  sortDirection,
  onSort,
  currentUserId,
  currentUserRole,
  onStatusChange,
}: UserTableProps) {
  function handleSort(key: string) {
    const newDir = sortBy === key && sortDirection === 'asc' ? 'desc' : 'asc'
    onSort({ sort_by: key, sort_direction: newDir })
  }

  function sortIndicator(key: string) {
    if (sortBy !== key) return ''
    return sortDirection === 'asc' ? ' ↑' : ' ↓'
  }

  const canEdit = currentUserRole === 'superadmin' || currentUserRole === 'admin'

  return (
    <div className="user-table-wrapper">
      <table className="user-table">
        <thead>
          <tr>
            {COLUMNS.map((col) => (
              <th
                key={col.key}
                className={col.sortable ? 'sortable' : ''}
                onClick={col.sortable ? () => handleSort(col.key) : undefined}
              >
                {col.label}{col.sortable ? sortIndicator(col.key) : ''}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {users.map((u) => (
            <tr key={u.id}>
              <td className="user-cell-name">{u.name}</td>
              <td className="user-cell-email">{u.email}</td>
              <td>{ROLE_LABELS[u.role ?? ''] ?? '—'}</td>
              <td>
                {u.workshops.length > 0 ? (
                  <span className="user-cell-workshops" title={u.workshops.map((w) => `${w.name} Nro ${w.number}`).join(', ')}>
                    {u.workshops.map((w) => `Nro ${w.number}`).join(', ')}
                  </span>
                ) : '—'}
              </td>
              <td>
                <UserStatusSelect
                  user={u}
                  editable={canEdit && u.id !== currentUserId}
                  onStatusChange={onStatusChange}
                />
              </td>
              <td className="user-cell-date">{formatDate(u.created_at)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

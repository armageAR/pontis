import { ChevronUp, ChevronDown, Pencil, KeyRound } from 'lucide-react'
import type { UserListItem, UserFilters } from '@/api/users'
import ActionMenu from '@/components/ActionMenu'
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
  onEdit: (user: UserListItem) => void
  onChangePassword: (user: UserListItem) => void
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
  { key: 'actions', label: '', sortable: false },
]

function SortIcon({ col, sortBy, sortDirection }: { col: string; sortBy: string; sortDirection: 'asc' | 'desc' }) {
  if (col !== sortBy) return <span className="sort-icon sort-icon-idle"><ChevronUp size={12} /></span>
  return sortDirection === 'asc'
    ? <span className="sort-icon sort-icon-active"><ChevronUp size={12} /></span>
    : <span className="sort-icon sort-icon-active"><ChevronDown size={12} /></span>
}

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
  onEdit,
  onChangePassword,
}: UserTableProps) {
  function handleSort(key: string) {
    const newDir = sortBy === key && sortDirection === 'asc' ? 'desc' : 'asc'
    onSort({ sort_by: key, sort_direction: newDir })
  }

  const canManage = currentUserRole === 'superadmin' || currentUserRole === 'admin'
  const isSuperAdmin = currentUserRole === 'superadmin'

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
                <span className="th-content">
                  {col.label}
                  {col.sortable && <SortIcon col={col.key} sortBy={sortBy} sortDirection={sortDirection} />}
                </span>
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {users.map((u) => {
            const isSelf = u.id === currentUserId
            const rowCanManage = canManage && !isSelf
            const rowCanEdit = rowCanManage || (isSelf && isSuperAdmin)

            return (
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
                    editable={rowCanManage}
                    onStatusChange={onStatusChange}
                  />
                </td>
                <td className="user-cell-date">{formatDate(u.created_at)}</td>
                <td className="user-cell-actions">
                  <ActionMenu
                    actions={[
                      {
                        icon: <Pencil size={15} />,
                        label: 'Editar usuario',
                        onClick: () => onEdit(u),
                        disabled: !rowCanEdit,
                      },
                      {
                        icon: <KeyRound size={15} />,
                        label: 'Cambiar contraseña',
                        onClick: () => onChangePassword(u),
                        disabled: !rowCanManage,
                      },
                    ]}
                  />
                </td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}

import { ChevronUp, ChevronDown, Pencil, KeyRound, Crown } from 'lucide-react'
import type { UserListItem, UserFilters } from '@/api/users'
import ActionMenu from '@/components/ActionMenu'
import UserStatusSelect from './UserStatusSelect'
import { formatDate } from '@/utils/date'
import './UserTable.css'

interface UserTableProps {
  users: UserListItem[]
  sortBy: string
  sortDirection: 'asc' | 'desc'
  onSort: (filters: Partial<UserFilters>) => void
  currentUserId: number
  currentUserRole: string
  isCurrentUserWorkshopAdmin: boolean
  onStatusChange: (userId: number, status: string) => Promise<void>
  onEdit: (user: UserListItem) => void
  onChangePassword: (user: UserListItem) => void
  onToggleSuperadmin: (user: UserListItem) => void
}

interface Column {
  key: string
  label: string
  sortable: boolean
}

const COLUMNS: Column[] = [
  { key: 'name', label: 'Nombre', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
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

export default function UserTable({
  users,
  sortBy,
  sortDirection,
  onSort,
  currentUserId,
  currentUserRole,
  isCurrentUserWorkshopAdmin,
  onStatusChange,
  onEdit,
  onChangePassword,
  onToggleSuperadmin,
}: UserTableProps) {
  function handleSort(key: string) {
    const newDir = sortBy === key && sortDirection === 'asc' ? 'desc' : 'asc'
    onSort({ sort_by: key, sort_direction: newDir })
  }

  const isSuperAdmin = currentUserRole === 'superadmin'
  const canManage = isSuperAdmin || isCurrentUserWorkshopAdmin

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
            const userIsSuperAdmin = u.role === 'superadmin'

            return (
              <tr key={u.id}>
                <td className="user-cell-name">
                  <span className="user-name-text">{u.name}</span>
                  {userIsSuperAdmin && (
                    <span className="user-superadmin-badge" title="Superadmin">
                      <Crown size={11} />
                    </span>
                  )}
                </td>
                <td className="user-cell-email">{u.email}</td>
                <td>
                  {u.workshops.length > 0 ? (
                    <span
                      className="user-cell-workshops"
                      title={u.workshops.map((w) => `${w.name} Nro ${w.number} (${w.workshop_role === 'admin' ? 'admin' : 'miembro'})`).join(', ')}
                    >
                      {u.workshops.map((w) => (
                        <span key={w.id} className="user-cell-workshop-tag">
                          Nro {w.number}
                          {w.workshop_role === 'admin' && <span className="user-cell-workshop-admin-badge">A</span>}
                        </span>
                      ))}
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
                      ...(isSuperAdmin && !isSelf ? [{
                        icon: <Crown size={15} />,
                        label: userIsSuperAdmin ? 'Quitar badge superadmin' : 'Dar badge superadmin',
                        onClick: () => onToggleSuperadmin(u),
                        danger: userIsSuperAdmin,
                      }] : []),
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

import { useState } from 'react'
import { Link } from 'react-router-dom'
import { LogOut } from 'lucide-react'
import type { User } from '@/api/auth'
import Button from './Button'
import NotificationBell from './NotificationBell'
import './SidebarUserSummary.css'

interface Props {
  user: User
  loggingOut: boolean
  onLogout: () => void
  onNavigate?: () => void
}

/**
 * Resumen de identidad del Hermano en el pie del sidebar: nombre + campana de
 * notificaciones, Taller principal, matrícula masónica y badges de rol. El
 * badge de Admin divulga los Talleres administrados al pasar el mouse o al
 * enfocarlo con el teclado.
 */
export default function SidebarUserSummary({ user, loggingOut, onLogout, onNavigate }: Props) {
  const fullName = user.last_name ? `${user.name} ${user.last_name}` : user.name
  const isSuperadmin = user.role === 'superadmin'
  const adminWorkshops = user.admin_workshops ?? []
  const principal = user.principal_workshop

  return (
    <div className="sidebar-summary">
      <div className="sidebar-summary-top">
        <Link to="/profile" className="sidebar-summary-name" onClick={onNavigate} title={fullName}>
          {fullName}
        </Link>
        <NotificationBell />
      </div>

      {principal && (
        <div className="sidebar-summary-taller" title={`Nº${principal.number} ${principal.name}`}>
          Nº{principal.number} · {principal.name}
        </div>
      )}

      {user.masonic_id && (
        <div className="sidebar-summary-masonic">{user.masonic_id}</div>
      )}

      {(isSuperadmin || adminWorkshops.length > 0) && (
        <div className="sidebar-summary-badges">
          {isSuperadmin && <span className="sidebar-badge sidebar-badge-super">Superadmin</span>}
          {adminWorkshops.length > 0 && <AdminBadge workshops={adminWorkshops} />}
        </div>
      )}

      <Button variant="outline" onClick={onLogout} loading={loggingOut}>
        <LogOut size={14} style={{ marginRight: 4 }} />
        Salir
      </Button>
    </div>
  )
}

function AdminBadge({ workshops }: { workshops: User['admin_workshops'] }) {
  const [open, setOpen] = useState(false)
  const label = `Admin de ${workshops.length === 1 ? 'Taller' : 'Talleres'}: ${workshops.map((w) => w.name).join(', ')}`

  return (
    <span
      className="sidebar-badge sidebar-badge-admin"
      tabIndex={0}
      role="button"
      aria-label={label}
      onMouseEnter={() => setOpen(true)}
      onMouseLeave={() => setOpen(false)}
      onFocus={() => setOpen(true)}
      onBlur={() => setOpen(false)}
    >
      Admin
      {open && (
        <span className="sidebar-badge-disclosure" role="tooltip">
          {workshops.map((w) => (
            <span key={w.id} className="sidebar-badge-disclosure-item">Nº{w.number} {w.name}</span>
          ))}
        </span>
      )}
    </span>
  )
}

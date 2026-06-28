import { useState, type ReactNode } from 'react'
import { NavLink, Link } from 'react-router-dom'
import { useAuth } from '@/context/AuthContext'
import Button from './Button'
import Logo from './Logo'
import NotificationBell from './NotificationBell'
import {
  LayoutDashboard,
  Search,
  BookOpen,
  FileText,
  Inbox,
  Shield,
  Menu,
  X,
  LogOut,
} from 'lucide-react'
import './AppLayout.css'
import './Logo.css'

interface AppLayoutProps {
  children: ReactNode
}

const NAV_ITEMS = [
  { to: '/dashboard',         label: 'Panel',            Icon: LayoutDashboard },
  { to: '/buscar',            label: 'Buscar',           Icon: Search },
  { to: '/workshops',         label: 'Talleres',         Icon: BookOpen },
  { to: '/mis-publicaciones', label: 'Mis publicaciones', Icon: FileText },
  { to: '/bandeja',           label: 'Bandeja',          Icon: Inbox },
]

export default function AppLayout({ children }: AppLayoutProps) {
  const { user, logout } = useAuth()
  const [loggingOut, setLoggingOut]   = useState(false)
  const [sidebarOpen, setSidebarOpen] = useState(false)

  async function handleLogout() {
    setLoggingOut(true)
    try {
      await logout()
    } finally {
      setLoggingOut(false)
    }
  }

  function closeSidebar() {
    setSidebarOpen(false)
  }

  return (
    <div className="app-layout">
      {/* ── Mobile top bar ── */}
      <header className="app-topbar">
        <button
          className="app-topbar-toggle"
          aria-label={sidebarOpen ? 'Cerrar menú' : 'Abrir menú'}
          onClick={() => setSidebarOpen(o => !o)}
        >
          {sidebarOpen ? <X size={20} /> : <Menu size={20} />}
        </button>
        <Link to="/dashboard" className="app-topbar-logo" onClick={closeSidebar}>
          <Logo size="sm" />
        </Link>
      </header>

      {/* ── Overlay (mobile only) ── */}
      {sidebarOpen && (
        <div
          className="app-sidebar-overlay"
          onClick={closeSidebar}
          aria-hidden="true"
        />
      )}

      {/* ── Sidebar wrapper: normal-flow on desktop, off-canvas on mobile ── */}
      <div className={`app-sidebar-wrapper${sidebarOpen ? ' is-open' : ''}`}>
        <aside className="app-sidebar">
          {/* Logo */}
          <div className="app-sidebar-logo">
            <Link to="/dashboard" className="app-sidebar-logo-link" onClick={closeSidebar}>
              <Logo size="sm" />
            </Link>
          </div>

          {/* Nav */}
          <nav className="app-sidebar-nav">
            {NAV_ITEMS.map(({ to, label, Icon }) => (
              <NavLink
                key={to}
                to={to}
                className="app-sidebar-link"
                onClick={closeSidebar}
              >
                <Icon size={16} className="app-sidebar-icon" />
                <span>{label}</span>
              </NavLink>
            ))}
            {user?.role === 'superadmin' && (
              <NavLink
                to="/administracion"
                className="app-sidebar-link"
                onClick={closeSidebar}
              >
                <Shield size={16} className="app-sidebar-icon" />
                <span>Administración</span>
              </NavLink>
            )}
          </nav>

          {/* User footer */}
          <div className="app-sidebar-footer">
            <NotificationBell />
            <Link to="/profile" className="app-sidebar-user" onClick={closeSidebar}>
              {user?.name}
            </Link>
            <Button variant="outline" onClick={handleLogout} loading={loggingOut}>
              <LogOut size={14} style={{ marginRight: 4 }} />
              Salir
            </Button>
          </div>
        </aside>
      </div>

      {/* ── Content ── */}
      <main className="app-main">{children}</main>
    </div>
  )
}

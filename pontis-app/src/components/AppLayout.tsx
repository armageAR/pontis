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
  PanelLeftClose,
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
  const [loggingOut, setLoggingOut]         = useState(false)
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false)
  const [desktopCollapsed, setDesktopCollapsed]   = useState(false)

  async function handleLogout() {
    setLoggingOut(true)
    try {
      await logout()
    } finally {
      setLoggingOut(false)
    }
  }

  function closeMobileSidebar() {
    setMobileSidebarOpen(false)
  }

  const wrapperClass = [
    'app-sidebar-wrapper',
    desktopCollapsed   ? 'desktop-collapsed' : '',
    mobileSidebarOpen  ? 'mobile-open'       : '',
  ].filter(Boolean).join(' ')

  return (
    <div className="app-layout">
      {/* ── Mobile top bar ── */}
      <header className="app-topbar">
        <button
          className="app-topbar-toggle"
          aria-label={mobileSidebarOpen ? 'Cerrar menú' : 'Abrir menú'}
          onClick={() => setMobileSidebarOpen(o => !o)}
        >
          {mobileSidebarOpen ? <X size={20} /> : <Menu size={20} />}
        </button>
        <Link to="/dashboard" className="app-topbar-logo" onClick={closeMobileSidebar}>
          <Logo size="sm" />
        </Link>
      </header>

      {/* ── Overlay (mobile only, always in DOM for smooth fade) ── */}
      <div
        className={`app-sidebar-overlay${mobileSidebarOpen ? ' is-visible' : ''}`}
        onClick={closeMobileSidebar}
        aria-hidden="true"
      />

      {/* ── Sidebar wrapper ── */}
      <div className={wrapperClass}>
        <aside className="app-sidebar">
          {/* Logo + desktop collapse toggle */}
          <div className="app-sidebar-logo">
            <Link to="/dashboard" className="app-sidebar-logo-link" onClick={closeMobileSidebar}>
              <Logo size="sm" />
            </Link>
            <button
              className="app-sidebar-desktop-toggle"
              aria-label="Colapsar menú"
              onClick={() => setDesktopCollapsed(true)}
            >
              <PanelLeftClose size={16} />
            </button>
          </div>

          {/* Nav */}
          <nav className="app-sidebar-nav">
            {NAV_ITEMS.map(({ to, label, Icon }) => (
              <NavLink
                key={to}
                to={to}
                className="app-sidebar-link"
                onClick={closeMobileSidebar}
              >
                <Icon size={16} className="app-sidebar-icon" />
                <span>{label}</span>
              </NavLink>
            ))}
            {user?.role === 'superadmin' && (
              <NavLink
                to="/administracion"
                className="app-sidebar-link"
                onClick={closeMobileSidebar}
              >
                <Shield size={16} className="app-sidebar-icon" />
                <span>Administración</span>
              </NavLink>
            )}
          </nav>

          {/* User footer */}
          <div className="app-sidebar-footer">
            <NotificationBell />
            <Link to="/profile" className="app-sidebar-user" onClick={closeMobileSidebar}>
              {user?.name}
            </Link>
            <Button variant="outline" onClick={handleLogout} loading={loggingOut}>
              <LogOut size={14} style={{ marginRight: 4 }} />
              Salir
            </Button>
          </div>
        </aside>
      </div>

      {/* ── Floating reopen button (desktop only, when sidebar collapsed) ── */}
      {desktopCollapsed && (
        <button
          className="app-sidebar-reopen-btn"
          aria-label="Abrir menú"
          onClick={() => setDesktopCollapsed(false)}
        >
          <Menu size={18} />
        </button>
      )}

      {/* ── Content ── */}
      <main className="app-main">{children}</main>
    </div>
  )
}

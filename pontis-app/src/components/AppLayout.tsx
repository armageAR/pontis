import { useState, type ReactNode } from 'react'
import { NavLink, Link } from 'react-router-dom'
import { useAuth } from '@/context/AuthContext'
import Button from './Button'
import Logo from './Logo'
import NotificationBell from './NotificationBell'
import './AppLayout.css'
import './Logo.css'

interface AppLayoutProps {
  children: ReactNode
}

export default function AppLayout({ children }: AppLayoutProps) {
  const { user, logout } = useAuth()
  const [loggingOut, setLoggingOut] = useState(false)

  async function handleLogout() {
    setLoggingOut(true)
    try {
      await logout()
    } finally {
      setLoggingOut(false)
    }
  }

  return (
    <div className="app-layout">
      <nav className="app-nav">
        <div className="app-nav-inner">
          <div className="app-nav-left">
            <Link to="/dashboard" className="app-nav-logo"><Logo size="sm" /></Link>
            <div className="app-nav-links">
              <NavLink to="/dashboard" className="app-nav-link">Panel</NavLink>
              <NavLink to="/buscar" className="app-nav-link">Buscar</NavLink>
              <NavLink to="/workshops" className="app-nav-link">Talleres</NavLink>
              <NavLink to="/mis-publicaciones" className="app-nav-link">Mis publicaciones</NavLink>
              <NavLink to="/bandeja" className="app-nav-link">Bandeja</NavLink>
              {user?.role === 'superadmin' && (
                <NavLink to="/administracion" className="app-nav-link">Administración</NavLink>
              )}
            </div>
          </div>
          <div className="app-nav-right">
            <NotificationBell />
            <Link to="/profile" className="app-nav-user">{user?.name}</Link>
            <Button variant="outline" onClick={handleLogout} loading={loggingOut}>
              Salir
            </Button>
          </div>
        </div>
      </nav>
      <main className="app-main">{children}</main>
    </div>
  )
}

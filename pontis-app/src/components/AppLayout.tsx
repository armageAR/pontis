import { useState, type ReactNode } from 'react'
import { Link } from 'react-router-dom'
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
        <div className="app-nav-left">
          <Link to="/dashboard" className="app-nav-logo"><Logo size="sm" /></Link>
          <div className="app-nav-links">
            <Link to="/dashboard" className="app-nav-link">Panel</Link>
            <Link to="/workshops" className="app-nav-link">Talleres</Link>
            <Link to="/people" className="app-nav-link">Hermanos</Link>
            <Link to="/services" className="app-nav-link">Servicios</Link>
            <Link to="/needs" className="app-nav-link">Necesidades</Link>
            {user?.role === 'superadmin' && <Link to="/users" className="app-nav-link">Admin hermanos</Link>}
          </div>
        </div>
        <div className="app-nav-right">
          <Link to="/contact-requests" className="app-nav-link app-nav-link-sm">Contactos</Link>
          <Link to="/change-requests" className="app-nav-link app-nav-link-sm">Solicitudes</Link>
          <NotificationBell />
          <Link to="/profile" className="app-nav-link">{user?.name}</Link>
          <Button variant="outline" onClick={handleLogout} loading={loggingOut}>
            Salir
          </Button>
        </div>
      </nav>
      <main className="app-main">{children}</main>
    </div>
  )
}

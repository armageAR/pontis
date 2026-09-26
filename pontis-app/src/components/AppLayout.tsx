import { useState, type ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '@/context/useAuth'
import Button from './Button'
import Logo from './Logo'
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
            <Link to="/users" className="app-nav-link">Usuarios</Link>
          </div>
        </div>
        <div className="app-nav-right">
          <span className="app-nav-user">{user?.name}</span>
          <Button variant="outline" onClick={handleLogout} loading={loggingOut}>
            Cerrar sesión
          </Button>
        </div>
      </nav>
      <main className="app-main">{children}</main>
    </div>
  )
}

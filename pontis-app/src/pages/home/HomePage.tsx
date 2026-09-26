import { Link } from 'react-router-dom'
import { LayoutGrid, RefreshCw, ShieldCheck } from 'lucide-react'
import { useAuth } from '@/context/useAuth'
import Button from '@/components/Button'
import './HomePage.css'

export default function HomePage() {
  const { user } = useAuth()

  return (
    <div className="layout">
      <nav className="nav">
        <Link to="/" className="nav-logo">Pontis</Link>
        {user ? (
          <Link to="/dashboard">
            <Button variant="outline">Panel</Button>
          </Link>
        ) : (
          <Link to="/login">
            <Button variant="outline">Ingresar</Button>
          </Link>
        )}
      </nav>

      <main>
        <section className="hero">
          <div className="hero-badge">En desarrollo</div>
          <h1 className="hero-title">
            La plataforma que<br />
            <span className="accent">conecta todo</span>
          </h1>
          <p className="hero-sub">
            Gestioná operaciones, usuarios y datos desde un solo lugar.
            Simple, rápido y escalable.
          </p>
          <div className="hero-actions">
            <Link to={user ? '/dashboard' : '/register'}>
              <Button>Comenzar</Button>
            </Link>
            <Button variant="ghost">Ver más</Button>
          </div>
        </section>

        <section className="features">
          <div className="feature-card">
            <div className="feature-icon"><LayoutGrid size={28} /></div>
            <h3>Gestión centralizada</h3>
            <p>Todos los módulos del sistema accesibles desde un panel unificado.</p>
          </div>
          <div className="feature-card">
            <div className="feature-icon"><RefreshCw size={28} /></div>
            <h3>Tiempo real</h3>
            <p>Datos actualizados al instante para tomar mejores decisiones.</p>
          </div>
          <div className="feature-card">
            <div className="feature-icon"><ShieldCheck size={28} /></div>
            <h3>Seguro y confiable</h3>
            <p>Autenticación robusta y trazabilidad completa de cada acción.</p>
          </div>
        </section>
      </main>

      <footer className="footer">
        <span>Pontis &copy; {new Date().getFullYear()}</span>
      </footer>
    </div>
  )
}

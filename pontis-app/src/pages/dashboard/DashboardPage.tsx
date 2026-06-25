import { Link } from 'react-router-dom'
import { Building2, Users } from 'lucide-react'
import { useAuth } from '@/context/AuthContext'
import AppLayout from '@/components/AppLayout'
import './DashboardPage.css'

export default function DashboardPage() {
  const { user } = useAuth()

  return (
    <AppLayout>
      <h1 className="dashboard-title">Panel</h1>
      <p className="dashboard-text">
        Bienvenido, <strong>{user?.name}</strong>. Tu rol es <strong>{user?.role}</strong>.
      </p>

      <div className="dashboard-cards">
        <Link to="/workshops" className="dashboard-card">
          <span className="dashboard-card-icon"><Building2 size={28} /></span>
          <h3>Talleres</h3>
          <p>Gestionar talleres, zonas y asignaciones.</p>
        </Link>
        <Link to="/users" className="dashboard-card">
          <span className="dashboard-card-icon"><Users size={28} /></span>
          <h3>Usuarios</h3>
          <p>Ver miembros y gestionar permisos.</p>
        </Link>
      </div>
    </AppLayout>
  )
}

import { Link } from 'react-router-dom'
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
          <span className="dashboard-card-icon">⬡</span>
          <h3>Talleres</h3>
          <p>Gestionar talleres, zonas y asignaciones.</p>
        </Link>
      </div>
    </AppLayout>
  )
}

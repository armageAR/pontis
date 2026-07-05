import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Building2, Users, Check, X, Info, Crown, ShieldCheck, UserCircle } from 'lucide-react'
import { useAuth } from '@/context/AuthContext'
import * as dashApi from '@/api/dashboard'
import type { MembershipNotification, ProfileCompletion } from '@/api/dashboard'
import AppLayout from '@/components/AppLayout'
import Button from '@/components/Button'
import './DashboardPage.css'

export default function DashboardPage() {
  const { user } = useAuth()

  const [notifications, setNotifications] = useState<MembershipNotification[]>([])
  const [isWorkshopAdmin, setIsWorkshopAdmin] = useState(false)
  const [pendingValidationCount, setPendingValidationCount] = useState(0)
  const [profileCompletion, setProfileCompletion] = useState<ProfileCompletion | null>(null)
  const [dashboardLoaded, setDashboardLoaded] = useState(false)
  const [actionLoading, setActionLoading] = useState<string | null>(null)

  useEffect(() => {
    dashApi.getDashboard().then((data) => {
      setNotifications(data.membership_notifications)
      setIsWorkshopAdmin(data.is_workshop_admin)
      setPendingValidationCount(data.pending_validation_count)
      setProfileCompletion(data.profile_completion)
    }).catch(() => {})
      .finally(() => setDashboardLoaded(true))
  }, [])

  async function handleDismiss(n: MembershipNotification) {
    setActionLoading(`dismiss-${n.workshop_id}`)
    try {
      await dashApi.dismissMembershipNotification(n.workshop_id)
      setNotifications((prev) => prev.filter((x) => x.workshop_id !== n.workshop_id))
    } finally {
      setActionLoading(null)
    }
  }

  return (
    <AppLayout>
      <div className="dashboard-heading">
        <h1 className="dashboard-title">Panel</h1>
        {user?.role === 'superadmin' && (
          <span className="dashboard-superadmin-badge">
            <Crown size={13} />
            Superadmin
          </span>
        )}
      </div>
      <p className="dashboard-text">Bienvenido, <strong>{user?.name}</strong>.</p>

      {notifications.length > 0 && (
        <div className="dashboard-notifications">
          {notifications.map((n) => (
            <div
              key={n.workshop_id}
              className={`dashboard-notification ${n.status === 'active' ? 'notification-accepted' : n.status === 'correction_requested' ? 'notification-correction' : 'notification-rejected'}`}
            >
              <div className="notification-icon">
                {n.status === 'active' ? <Check size={18} /> : n.status === 'correction_requested' ? <Info size={18} /> : <X size={18} />}
              </div>
              <div className="notification-body">
                <p className="notification-title">
                  {n.status === 'active' ? 'Solicitud aceptada' : n.status === 'correction_requested' ? 'Se requieren correcciones' : 'Solicitud rechazada'}
                </p>
                <p className="notification-text">
                  Tu solicitud para <strong>#{n.workshop_number} {n.workshop_name}</strong>{' '}
                  {n.status === 'active' ? 'fue aceptada.' : n.status === 'correction_requested' ? `requiere correcciones: ${n.correction_notes ?? ''}` : 'fue rechazada.'}
                </p>
              </div>
              <Button
                variant="outline"
                className="notification-ok-btn"
                loading={actionLoading === `dismiss-${n.workshop_id}`}
                onClick={() => handleDismiss(n)}
              >
                OK
              </Button>
            </div>
          ))}
        </div>
      )}

      {!dashboardLoaded ? (
        <div className="dashboard-cards dashboard-cards-loading" aria-label="Cargando panel">
          <div className="dashboard-card-skeleton" />
          <div className="dashboard-card-skeleton" />
        </div>
      ) : (
        <div className="dashboard-cards">
          <Link to="/profile" className="dashboard-card dashboard-card-profile">
            <span className="dashboard-card-icon"><UserCircle size={28} /></span>
            <h3>Mi Perfil</h3>
            <p>Completá y revisá tu ficha personal.</p>
            {profileCompletion && (
              <div className="dashboard-profile-progress">
                <div className="dashboard-progress-head">
                  <span>Completitud</span>
                  <span className="dashboard-progress-percent">{profileCompletion.percent}%</span>
                </div>
                <div
                  className="dashboard-progress-track"
                  role="progressbar"
                  aria-label="Completitud del perfil"
                  aria-valuenow={profileCompletion.percent}
                  aria-valuemin={0}
                  aria-valuemax={100}
                >
                  <div className="dashboard-progress-fill" style={{ width: `${profileCompletion.percent}%` }} />
                </div>
              </div>
            )}
          </Link>
          {(isWorkshopAdmin || user?.role === 'superadmin') && (
            <Link to="/administracion" className="dashboard-card dashboard-card-admin">
              {pendingValidationCount > 0 && (
                <span className="dashboard-card-indicator" aria-label="Validaciones pendientes" />
              )}
              <span className="dashboard-card-icon"><ShieldCheck size={28} /></span>
              <h3>Administracion</h3>
              <p>tareas de administracion en tu taller</p>
            </Link>
          )}
          {user?.role === 'superadmin' && (
            <Link to="/workshops" className="dashboard-card">
              <span className="dashboard-card-icon"><Building2 size={28} /></span>
              <h3>Talleres</h3>
              <p>Gestionar talleres, zonas y asignaciones.</p>
            </Link>
          )}
          <Link
            to={user?.role === 'superadmin' ? '/users' : '/mis-hermanos'}
            className="dashboard-card"
          >
            <span className="dashboard-card-icon"><Users size={28} /></span>
            <h3>{user?.role === 'superadmin' ? 'Hermanos' : 'Mis Hermanos'}</h3>
            <p>{user?.role === 'superadmin'
              ? 'Ver miembros y gestionar permisos.'
              : 'Directorio de Hermanos activos de la comunidad.'
            }</p>
          </Link>
        </div>
      )}
    </AppLayout>
  )
}

import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Building2, Users, Check, X, Info, Crown } from 'lucide-react'
import { useAuth } from '@/context/AuthContext'
import * as dashApi from '@/api/dashboard'
import type { PendingRequest, MembershipNotification } from '@/api/dashboard'
import AppLayout from '@/components/AppLayout'
import Button from '@/components/Button'
import Modal from '@/components/Modal'
import './DashboardPage.css'

export default function DashboardPage() {
  const { user } = useAuth()

  const [pendingRequests, setPendingRequests] = useState<PendingRequest[]>([])
  const [notifications, setNotifications] = useState<MembershipNotification[]>([])
  const [actionLoading, setActionLoading] = useState<string | null>(null)
  const [userInfoModal, setUserInfoModal] = useState<PendingRequest | null>(null)

  useEffect(() => {
    dashApi.getDashboard().then((data) => {
      setPendingRequests(data.pending_requests)
      setNotifications(data.membership_notifications)
    }).catch(() => {})
  }, [])

  async function handleApprove(req: PendingRequest) {
    const key = `approve-${req.workshop_id}-${req.user_id}`
    setActionLoading(key)
    try {
      await dashApi.approveJoinRequest(req.workshop_id, req.user_id)
      setPendingRequests((prev) => prev.filter(
        (r) => !(r.workshop_id === req.workshop_id && r.user_id === req.user_id)
      ))
    } finally {
      setActionLoading(null)
    }
  }

  async function handleReject(req: PendingRequest) {
    const key = `reject-${req.workshop_id}-${req.user_id}`
    setActionLoading(key)
    try {
      await dashApi.rejectJoinRequest(req.workshop_id, req.user_id)
      setPendingRequests((prev) => prev.filter(
        (r) => !(r.workshop_id === req.workshop_id && r.user_id === req.user_id)
      ))
    } finally {
      setActionLoading(null)
    }
  }

  async function handleDismiss(n: MembershipNotification) {
    setActionLoading(`dismiss-${n.workshop_id}`)
    try {
      await dashApi.dismissMembershipNotification(n.workshop_id)
      setNotifications((prev) => prev.filter((x) => x.workshop_id !== n.workshop_id))
    } finally {
      setActionLoading(null)
    }
  }

  function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString('es-AR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
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
              className={`dashboard-notification ${n.status === 'active' ? 'notification-accepted' : 'notification-rejected'}`}
            >
              <div className="notification-icon">
                {n.status === 'active' ? <Check size={18} /> : <X size={18} />}
              </div>
              <div className="notification-body">
                <p className="notification-title">
                  {n.status === 'active' ? 'Solicitud aceptada' : 'Solicitud rechazada'}
                </p>
                <p className="notification-text">
                  Tu solicitud para <strong>#{n.workshop_number} {n.workshop_name}</strong> fue{' '}
                  {n.status === 'active' ? 'aceptada' : 'rechazada'}.
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

      {pendingRequests.length > 0 && (
        <div className="dashboard-pending-section">
          <h2 className="dashboard-section-title">Solicitudes de ingreso</h2>
          <div className="dashboard-pending-list">
            {pendingRequests.map((req) => {
              const approveKey = `approve-${req.workshop_id}-${req.user_id}`
              const rejectKey = `reject-${req.workshop_id}-${req.user_id}`
              const busy = actionLoading === approveKey || actionLoading === rejectKey
              return (
                <div key={`${req.workshop_id}-${req.user_id}`} className="pending-request-card">
                  <div className="pending-request-header">
                    <span className="pending-dot" />
                    <span className="pending-workshop">#{req.workshop_number} · {req.workshop_name}</span>
                  </div>
                  <p className="pending-user-name">{req.user_name}</p>
                  <p className="pending-date">Solicitó el ingreso el {formatDate(req.requested_at)}</p>
                  <div className="pending-request-actions">
                    <button
                      type="button"
                      className="pending-info-btn"
                      onClick={() => setUserInfoModal(req)}
                    >
                      <Info size={14} />
                      Ver info
                    </button>
                    <Button
                      variant="outline"
                      className="pending-reject-btn"
                      loading={actionLoading === rejectKey}
                      disabled={busy}
                      onClick={() => handleReject(req)}
                    >
                      Rechazar
                    </Button>
                    <Button
                      className="pending-approve-btn"
                      loading={actionLoading === approveKey}
                      disabled={busy}
                      onClick={() => handleApprove(req)}
                    >
                      Aceptar
                    </Button>
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      )}

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

      <Modal open={userInfoModal !== null} onClose={() => setUserInfoModal(null)} title="Información del usuario">
        {userInfoModal && (
          <div className="user-info-modal">
            <div className="user-info-row">
              <span className="user-info-label">Nombre</span>
              <span className="user-info-value">{userInfoModal.user_name}</span>
            </div>
            <div className="user-info-row">
              <span className="user-info-label">Email</span>
              <span className="user-info-value">{userInfoModal.user_email}</span>
            </div>
            <div className="user-info-row">
              <span className="user-info-label">Estado</span>
              <span className="user-info-value">{userInfoModal.user_status}</span>
            </div>
            <div className="user-info-row">
              <span className="user-info-label">Taller solicitado</span>
              <span className="user-info-value">#{userInfoModal.workshop_number} · {userInfoModal.workshop_name}</span>
            </div>
            <div className="user-info-row">
              <span className="user-info-label">Fecha de solicitud</span>
              <span className="user-info-value">{formatDate(userInfoModal.requested_at)}</span>
            </div>
          </div>
        )}
      </Modal>
    </AppLayout>
  )
}

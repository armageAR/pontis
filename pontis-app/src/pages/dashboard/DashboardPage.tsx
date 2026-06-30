import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Building2, Users, Check, X, Info, Crown } from 'lucide-react'
import { useAuth } from '@/context/AuthContext'
import * as dashApi from '@/api/dashboard'
import type { PendingRequest, MembershipNotification } from '@/api/dashboard'
import AppLayout from '@/components/AppLayout'
import Button from '@/components/Button'
import Modal from '@/components/Modal'
import { formatDate } from '@/utils/date'
import './DashboardPage.css'

export default function DashboardPage() {
  const { user } = useAuth()

  const [pendingRequests, setPendingRequests] = useState<PendingRequest[]>([])
  const [notifications, setNotifications] = useState<MembershipNotification[]>([])
  const [actionLoading, setActionLoading] = useState<string | null>(null)
  const [userInfoModal, setUserInfoModal] = useState<PendingRequest | null>(null)
  const [correctionModal, setCorrectionModal] = useState<PendingRequest | null>(null)
  const [correctionNotes, setCorrectionNotes] = useState('')

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

  async function handleRequestCorrection() {
    if (!correctionModal || !correctionNotes.trim()) return
    const req = correctionModal
    const key = `correction-${req.workshop_id}-${req.user_id}`
    setActionLoading(key)
    try {
      await dashApi.requestCorrection(req.workshop_id, req.user_id, correctionNotes)
      setPendingRequests(prev => prev.map(r =>
        r.workshop_id === req.workshop_id && r.user_id === req.user_id
          ? { ...r, membership_status: 'correction_requested', correction_notes: correctionNotes }
          : r
      ))
      setCorrectionModal(null); setCorrectionNotes('')
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

      {pendingRequests.length > 0 && (
        <div className="dashboard-pending-section">
          <h2 className="dashboard-section-title">Solicitudes de ingreso</h2>
          <div className="dashboard-pending-list">
            {pendingRequests.map((req) => {
              const approveKey = `approve-${req.workshop_id}-${req.user_id}`
              const rejectKey = `reject-${req.workshop_id}-${req.user_id}`
              const correctionKey = `correction-${req.workshop_id}-${req.user_id}`
              const busy = actionLoading === approveKey || actionLoading === rejectKey || actionLoading === correctionKey
              const isCorrection = req.membership_status === 'correction_requested'
              return (
                <div key={`${req.workshop_id}-${req.user_id}`} className={`pending-request-card ${isCorrection ? 'correction-requested' : ''}`}>
                  <div className="pending-request-header">
                    <span className={`pending-dot ${isCorrection ? 'dot-correction' : ''}`} />
                    <span className="pending-workshop">#{req.workshop_number} · {req.workshop_name}</span>
                    {isCorrection && <span className="pending-correction-badge">Corrección solicitada</span>}
                  </div>
                  <p className="pending-user-name">{req.user_last_name ? `${req.user_last_name}, ${req.user_name}` : req.user_name}</p>
                  <p className="pending-date">Solicitó el ingreso el {formatDate(req.requested_at)}</p>
                  {isCorrection && req.correction_notes && (
                    <p className="pending-correction-notes">Corrección pedida: {req.correction_notes}</p>
                  )}
                  <div className="pending-request-actions">
                    <button type="button" className="pending-info-btn" onClick={() => setUserInfoModal(req)}>
                      <Info size={14} /> Ver info
                    </button>
                    <button type="button" className="pending-info-btn" onClick={() => { setCorrectionModal(req); setCorrectionNotes(req.correction_notes ?? '') }}>
                      Pedir corrección
                    </button>
                    <Button variant="outline" className="pending-reject-btn" loading={actionLoading === rejectKey} disabled={busy} onClick={() => handleReject(req)}>
                      Rechazar
                    </Button>
                    <Button className="pending-approve-btn" loading={actionLoading === approveKey} disabled={busy} onClick={() => handleApprove(req)}>
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
        {user?.role === 'superadmin' && (
          <Link to="/workshops" className="dashboard-card">
            <span className="dashboard-card-icon"><Building2 size={28} /></span>
            <h3>Talleres</h3>
            <p>Gestionar talleres, zonas y asignaciones.</p>
          </Link>
        )}
        <Link to="/users" className="dashboard-card">
          <span className="dashboard-card-icon"><Users size={28} /></span>
          <h3>Hermanos</h3>
          <p>{user?.role === 'superadmin'
            ? 'Ver miembros y gestionar permisos.'
            : 'Buscar y ver el directorio de Hermanos.'
          }</p>
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

      <Modal open={correctionModal !== null} onClose={() => { setCorrectionModal(null); setCorrectionNotes('') }} title="Solicitar corrección de datos">
        {correctionModal && (
          <div className="correction-modal-body">
            <p className="correction-modal-info">Indicá qué datos debe corregir <strong>{correctionModal.user_last_name ? `${correctionModal.user_last_name}, ${correctionModal.user_name}` : correctionModal.user_name}</strong> antes de aprobar su ingreso.</p>
            <textarea
              className="correction-textarea"
              rows={4}
              value={correctionNotes}
              onChange={e => setCorrectionNotes(e.target.value)}
              placeholder="Ej: El DNI no coincide con el nombre, por favor actualizá tus datos..."
            />
            <div className="correction-modal-actions">
              <Button variant="outline" onClick={() => { setCorrectionModal(null); setCorrectionNotes('') }}>Cancelar</Button>
              <Button
                loading={actionLoading === `correction-${correctionModal.workshop_id}-${correctionModal.user_id}`}
                disabled={!correctionNotes.trim()}
                onClick={handleRequestCorrection}
              >Enviar solicitud de corrección</Button>
            </div>
          </div>
        )}
      </Modal>
    </AppLayout>
  )
}

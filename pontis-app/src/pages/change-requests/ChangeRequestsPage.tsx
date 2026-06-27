import { useEffect, useState, type FormEvent } from 'react'
import AppLayout from '@/components/AppLayout'
import Button from '@/components/Button'
import Badge from '@/components/Badge'
import Modal from '@/components/Modal'
import FormField from '@/components/FormField'
import Input from '@/components/Input'
import Alert from '@/components/Alert'
import Spinner from '@/components/Spinner'
import EmptyState from '@/components/EmptyState'
import Pagination from '@/components/Pagination'
import { useAuth } from '@/context/AuthContext'
import * as api from '@/api/changeRequests'
import type { ChangeRequest } from '@/api/changeRequests'
import './ChangeRequestsPage.css'

const FIELD_LABELS: Record<string, string> = { name: 'Nombre', last_name: 'Apellido', dni: 'DNI / Documento', masonic_id: 'Matrícula masónica' }
const STATUS_LABELS: Record<string, string> = { pending: 'Pendiente', approved: 'Aprobada', rejected: 'Rechazada' }
const STATUS_VARIANTS: Record<string, 'default'|'success'|'warning'|'error'> = { pending: 'warning', approved: 'success', rejected: 'error' }

export default function ChangeRequestsPage() {
  const { user } = useAuth()
  const isSuperAdmin = user?.role === 'superadmin'

  const [requests, setRequests] = useState<ChangeRequest[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [statusFilter, setStatusFilter] = useState('pending')
  const [showModal, setShowModal] = useState(false)
  const [reviewModal, setReviewModal] = useState<ChangeRequest | null>(null)
  const [form, setForm] = useState({ field: 'name', new_value: '', reason: '' })
  const [reviewNotes, setReviewNotes] = useState('')
  const [saving, setSaving] = useState(false)
  const [actionMsg, setActionMsg] = useState('')

  async function load() {
    setLoading(true)
    try {
      const r = await api.getChangeRequests({ status: statusFilter || undefined })
      setRequests(r.data); setLastPage(r.last_page); setTotal(r.total)
    } catch { setError('Error cargando solicitudes.') }
    finally { setLoading(false) }
  }

  useEffect(() => { load() }, [page, statusFilter])

  async function handleCreate(e: FormEvent) {
    e.preventDefault(); setSaving(true)
    try {
      await api.createChangeRequest(form)
      setShowModal(false); setActionMsg('Solicitud enviada. Un administrador la revisará pronto.')
      load()
    } catch (err: any) {
      alert(err?.response?.data?.message ?? 'Error al enviar la solicitud.')
    } finally { setSaving(false) }
  }

  async function handleApprove() {
    if (!reviewModal) return; setSaving(true)
    try {
      await api.approveChangeRequest(reviewModal.id, reviewNotes || undefined)
      setReviewModal(null); load()
    } catch { alert('Error al aprobar.') }
    finally { setSaving(false) }
  }

  async function handleReject() {
    if (!reviewModal) return; setSaving(true)
    try {
      await api.rejectChangeRequest(reviewModal.id, reviewNotes || undefined)
      setReviewModal(null); load()
    } catch { alert('Error al rechazar.') }
    finally { setSaving(false) }
  }

  return (
    <AppLayout>
      <div className="cr-header">
        <h1 className="cr-title">Cambios de datos sensibles</h1>
        {!isSuperAdmin && <Button onClick={() => { setForm({ field: 'name', new_value: '', reason: '' }); setShowModal(true) }}>+ Solicitar cambio</Button>}
      </div>

      {actionMsg && <Alert variant="success">{actionMsg}</Alert>}
      {error && <Alert>{error}</Alert>}

      <div className="cr-filters">
        <select className="cr-select" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(1) }}>
          <option value="">Todos</option>
          <option value="pending">Pendientes</option>
          <option value="approved">Aprobadas</option>
          <option value="rejected">Rechazadas</option>
        </select>
      </div>

      {loading ? <div className="cr-loading"><Spinner /></div> : requests.length === 0 ? (
        <EmptyState title="Sin solicitudes" description={isSuperAdmin ? 'No hay solicitudes de cambio pendientes.' : 'No enviaste ninguna solicitud de cambio aún.'} />
      ) : (
        <>
          <div className="cr-list">
            {requests.map(r => (
              <div key={r.id} className="cr-card">
                <div className="cr-card-header">
                  <div>
                    {isSuperAdmin && r.user && <span className="cr-user">{r.user.name} {r.user.last_name} · {r.user.email}</span>}
                    <span className="cr-field">{FIELD_LABELS[r.field]}</span>
                  </div>
                  <Badge variant={STATUS_VARIANTS[r.status]}>{STATUS_LABELS[r.status]}</Badge>
                </div>
                <div className="cr-values">
                  <span className="cr-value-label">Valor actual:</span> <span className="cr-value">{r.current_value ?? '(vacío)'}</span>
                  <span className="cr-arrow">→</span>
                  <span className="cr-value-label">Nuevo valor:</span> <span className="cr-value cr-value-new">{r.new_value}</span>
                </div>
                {r.reason && <p className="cr-reason">Motivo: {r.reason}</p>}
                {r.reviewer_notes && <p className="cr-reason">Nota del revisor: {r.reviewer_notes}</p>}
                <div className="cr-meta">Solicitado: {new Date(r.created_at).toLocaleDateString('es-AR')}</div>
                {isSuperAdmin && r.status === 'pending' && (
                  <div className="cr-actions">
                    <Button onClick={() => { setReviewModal(r); setReviewNotes('') }}>Revisar</Button>
                  </div>
                )}
              </div>
            ))}
          </div>
          <Pagination currentPage={page} lastPage={lastPage} total={total} onPageChange={setPage} />
        </>
      )}

      <Modal open={showModal} onClose={() => setShowModal(false)} title="Solicitar cambio de dato sensible">
        <form onSubmit={handleCreate} className="cr-form">
          <FormField label="Campo a cambiar">
            <select className="cr-select" value={form.field} onChange={e => setForm(f => ({ ...f, field: e.target.value }))}>
              {Object.entries(FIELD_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </select>
          </FormField>
          <FormField label="Nuevo valor *"><Input required value={form.new_value} onChange={e => setForm(f => ({ ...f, new_value: e.target.value }))} /></FormField>
          <FormField label="Motivo (opcional)"><Input value={form.reason} onChange={e => setForm(f => ({ ...f, reason: e.target.value }))} /></FormField>
          <p className="cr-info">Los cambios en nombre, apellido, DNI y matrícula requieren aprobación de un administrador por razones de trazabilidad.</p>
          <div className="cr-modal-actions">
            <Button type="button" variant="outline" onClick={() => setShowModal(false)}>Cancelar</Button>
            <Button type="submit" loading={saving}>Enviar solicitud</Button>
          </div>
        </form>
      </Modal>

      <Modal open={reviewModal !== null} onClose={() => setReviewModal(null)} title="Revisar solicitud">
        {reviewModal && (
          <div className="cr-form">
            <p><strong>Usuario:</strong> {reviewModal.user?.name} {reviewModal.user?.last_name}</p>
            <p><strong>Campo:</strong> {FIELD_LABELS[reviewModal.field]}</p>
            <p><strong>Valor actual:</strong> {reviewModal.current_value ?? '(vacío)'}</p>
            <p><strong>Nuevo valor:</strong> {reviewModal.new_value}</p>
            {reviewModal.reason && <p><strong>Motivo:</strong> {reviewModal.reason}</p>}
            <FormField label="Nota (opcional)">
              <textarea className="cr-textarea" value={reviewNotes} onChange={e => setReviewNotes(e.target.value)} rows={3} />
            </FormField>
            <div className="cr-modal-actions">
              <Button type="button" variant="outline" onClick={() => setReviewModal(null)}>Cancelar</Button>
              <Button type="button" variant="outline" onClick={handleReject} loading={saving}>Rechazar</Button>
              <Button type="button" onClick={handleApprove} loading={saving}>Aprobar</Button>
            </div>
          </div>
        )}
      </Modal>
    </AppLayout>
  )
}

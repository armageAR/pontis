import { useEffect, useState } from 'react'
import AppLayout from '@/components/AppLayout'
import Button from '@/components/Button'
import Badge from '@/components/Badge'
import Modal from '@/components/Modal'
import FormField from '@/components/FormField'
import Alert from '@/components/Alert'
import Spinner from '@/components/Spinner'
import EmptyState from '@/components/EmptyState'
import Pagination from '@/components/Pagination'
import * as api from '@/api/contactRequests'
import type { ContactRequest } from '@/api/contactRequests'
import { formatDate } from '@/utils/date'
import './ContactRequestsPage.css'

const STATUS_LABELS: Record<string, string> = { pending: 'Pendiente', accepted: 'Aceptada', rejected: 'Rechazada', cancelled: 'Cancelada', expired: 'Expirada', closed: 'Cerrada' }
const STATUS_VARIANTS: Record<string, 'default'|'success'|'warning'|'error'> = { pending: 'warning', accepted: 'success', rejected: 'error', cancelled: 'default', expired: 'default', closed: 'default' }

function personName(p?: { name: string; last_name: string | null }) {
  if (!p) return '-'
  return p.last_name ? `${p.last_name}, ${p.name}` : p.name
}

export default function ContactRequestsPage({ embedded = false }: { embedded?: boolean }) {
  const [requests, setRequests] = useState<ContactRequest[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [direction, setDirection] = useState<'received' | 'sent'>('received')
  const [statusFilter, setStatusFilter] = useState('')
  const [respondModal, setRespondModal] = useState<ContactRequest | null>(null)
  const [responseMsg, setResponseMsg] = useState('')
  const [saving, setSaving] = useState(false)

  async function load() {
    setLoading(true)
    try {
      const r = await api.getContactRequests({ direction, status: statusFilter || undefined })
      setRequests(r.data); setLastPage(r.last_page); setTotal(r.total)
    } catch { setError('Error cargando solicitudes.') }
    finally { setLoading(false) }
  }

  useEffect(() => { load() }, [page, direction, statusFilter])

  async function handleAccept() {
    if (!respondModal) return; setSaving(true)
    try { await api.acceptContactRequest(respondModal.id, responseMsg || undefined); setRespondModal(null); load() }
    catch { alert('Error al aceptar.') } finally { setSaving(false) }
  }

  async function handleReject() {
    if (!respondModal) return; setSaving(true)
    try { await api.rejectContactRequest(respondModal.id, responseMsg || undefined); setRespondModal(null); load() }
    catch { alert('Error al rechazar.') } finally { setSaving(false) }
  }

  async function handleCancel(cr: ContactRequest) {
    if (!confirm('¿Cancelar esta solicitud?')) return
    await api.cancelContactRequest(cr.id); load()
  }

  async function handleClose(cr: ContactRequest) {
    if (!confirm('¿Cerrar esta solicitud? Indica que el contacto fue resuelto.')) return
    await api.closeContactRequest(cr.id); load()
  }

  const inner = (
    <>
      <div className="cont-header">
        <h1 className="cont-title">Solicitudes de contacto</h1>
      </div>
      {error && <Alert>{error}</Alert>}

      <div className="cont-tabs">
        <button className={`cont-tab ${direction === 'received' ? 'cont-tab-active' : ''}`} onClick={() => { setDirection('received'); setPage(1) }}>Recibidas</button>
        <button className={`cont-tab ${direction === 'sent' ? 'cont-tab-active' : ''}`} onClick={() => { setDirection('sent'); setPage(1) }}>Enviadas</button>
      </div>

      <div className="cont-filters">
        <select className="cont-select" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(1) }}>
          <option value="">Todos los estados</option>
          {Object.entries(STATUS_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
        </select>
      </div>

      {loading ? <div className="cont-loading"><Spinner /></div> : requests.length === 0 ? (
        <EmptyState title="Sin solicitudes" description={direction === 'received' ? 'No recibiste solicitudes de contacto.' : 'No enviaste solicitudes de contacto.'} />
      ) : (
        <>
          <div className="cont-list">
            {requests.map(cr => (
              <div key={cr.id} className="cont-card">
                <div className="cont-card-header">
                  <div>
                    <span className="cont-person">
                      {direction === 'received' ? `De: ${personName(cr.requester)}` : `Para: ${personName(cr.requestee)}`}
                    </span>
                    <span className="cont-date">{formatDate(cr.created_at)}</span>
                  </div>
                  <Badge variant={STATUS_VARIANTS[cr.status]}>{STATUS_LABELS[cr.status]}</Badge>
                </div>
                <p className="cont-message">{cr.message}</p>
                {cr.response_message && <p className="cont-response"><strong>Respuesta:</strong> {cr.response_message}</p>}
                {cr.status === 'accepted' && direction === 'sent' && cr.requestee && (
                  <div className="cont-contact-info">
                    <strong>Datos de contacto:</strong>
                    {cr.requestee.email && <span> · {cr.requestee.email}</span>}
                    {cr.requestee.phone && <span> · Tel: {cr.requestee.phone}</span>}
                    {cr.requestee.whatsapp && <span> · WA: {cr.requestee.whatsapp}</span>}
                  </div>
                )}
                {cr.status === 'accepted' && direction === 'received' && cr.requester && (
                  <div className="cont-contact-info">
                    <strong>Datos del solicitante:</strong>
                    {cr.requester.email && <span> · {cr.requester.email}</span>}
                    {cr.requester.phone && <span> · Tel: {cr.requester.phone}</span>}
                    {cr.requester.whatsapp && <span> · WA: {cr.requester.whatsapp}</span>}
                  </div>
                )}
                <div className="cont-actions">
                  {direction === 'received' && cr.status === 'pending' && (
                    <Button onClick={() => { setRespondModal(cr); setResponseMsg('') }}>Responder</Button>
                  )}
                  {direction === 'sent' && cr.status === 'pending' && (
                    <Button variant="outline" onClick={() => handleCancel(cr)}>Cancelar</Button>
                  )}
                  {cr.status === 'accepted' && (
                    <Button variant="outline" onClick={() => handleClose(cr)}>Cerrar</Button>
                  )}
                </div>
              </div>
            ))}
          </div>
          <Pagination currentPage={page} lastPage={lastPage} total={total} onPageChange={setPage} />
        </>
      )}

      <Modal open={respondModal !== null} onClose={() => setRespondModal(null)} title="Responder solicitud de contacto">
        {respondModal && (
          <div className="cont-form">
            <p><strong>Mensaje recibido:</strong></p>
            <p className="cont-modal-msg">{respondModal.message}</p>
            <FormField label="Mensaje de respuesta (opcional)">
              <textarea className="cont-textarea" value={responseMsg} onChange={e => setResponseMsg(e.target.value)} rows={3} placeholder="Escribí un mensaje opcional..." />
            </FormField>
            <div className="cont-modal-actions">
              <Button type="button" variant="outline" onClick={() => setRespondModal(null)}>Cancelar</Button>
              <Button type="button" variant="outline" onClick={handleReject} loading={saving}>Rechazar</Button>
              <Button type="button" onClick={handleAccept} loading={saving}>Aceptar</Button>
            </div>
          </div>
        )}
      </Modal>
    </>
  )
  return embedded ? inner : <AppLayout>{inner}</AppLayout>
}

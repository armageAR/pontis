import { useEffect, useState } from 'react'
import { Info } from 'lucide-react'
import Alert from '@/components/Alert'
import Button from '@/components/Button'
import EmptyState from '@/components/EmptyState'
import Modal from '@/components/Modal'
import Spinner from '@/components/Spinner'
import * as dashApi from '@/api/dashboard'
import type { PendingRequest } from '@/api/dashboard'
import { formatDate } from '@/utils/date'
import './JoinRequestsSection.css'

function hermanoName(r: PendingRequest): string {
  return r.user_last_name ? `${r.user_last_name}, ${r.user_name}` : r.user_name
}

/**
 * Sección de solicitudes de ingreso a Talleres dentro de Administración →
 * Validaciones. El revisor (Superadmin o Admin de Taller) puede aceptar,
 * rechazar, pedir corrección o ver el detalle de cada solicitud con alcance.
 */
export default function JoinRequestsSection() {
  const [rows, setRows] = useState<PendingRequest[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [actionKey, setActionKey] = useState<string | null>(null)
  const [infoModal, setInfoModal] = useState<PendingRequest | null>(null)
  const [correctionModal, setCorrectionModal] = useState<PendingRequest | null>(null)
  const [correctionNotes, setCorrectionNotes] = useState('')

  async function load() {
    setLoading(true); setError('')
    try {
      setRows(await dashApi.getPendingJoinRequests())
    } catch {
      setError('No se pudieron cargar las solicitudes de ingreso.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [])

  const rowKey = (r: PendingRequest) => `${r.workshop_id}-${r.user_id}`

  function removeRow(r: PendingRequest) {
    setRows((prev) => prev.filter((x) => rowKey(x) !== rowKey(r)))
  }

  async function accept(r: PendingRequest) {
    setActionKey(`accept-${rowKey(r)}`)
    try {
      await dashApi.approveJoinRequest(r.workshop_id, r.user_id)
      removeRow(r)
    } finally {
      setActionKey(null)
    }
  }

  async function reject(r: PendingRequest) {
    setActionKey(`reject-${rowKey(r)}`)
    try {
      await dashApi.rejectJoinRequest(r.workshop_id, r.user_id)
      removeRow(r)
    } finally {
      setActionKey(null)
    }
  }

  async function submitCorrection() {
    if (!correctionModal || !correctionNotes.trim()) return
    const r = correctionModal
    setActionKey(`correction-${rowKey(r)}`)
    try {
      await dashApi.requestCorrection(r.workshop_id, r.user_id, correctionNotes)
      setRows((prev) => prev.map((x) =>
        rowKey(x) === rowKey(r)
          ? { ...x, membership_status: 'correction_requested', correction_notes: correctionNotes }
          : x
      ))
      setCorrectionModal(null); setCorrectionNotes('')
    } finally {
      setActionKey(null)
    }
  }

  return (
    <section className="jr-section">
      <h2 className="validation-section-title">Solicitudes de ingreso</h2>
      {error && <Alert>{error}</Alert>}

      {loading ? (
        <div className="jr-loading"><Spinner /></div>
      ) : rows.length === 0 ? (
        <EmptyState
          title="Sin solicitudes de ingreso"
          description="No hay solicitudes de ingreso pendientes en tus Talleres."
        />
      ) : (
        <div className="validation-table-wrap">
          <table className="validation-table">
            <thead>
              <tr>
                <th>Hermano</th>
                <th>Fecha de solicitud</th>
                <th>Taller</th>
                <th>Estado</th>
                <th className="validation-th-actions">Acciones</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => {
                const busy = actionKey === `accept-${rowKey(r)}` || actionKey === `reject-${rowKey(r)}` || actionKey === `correction-${rowKey(r)}`
                const isCorrection = r.membership_status === 'correction_requested'
                return (
                  <tr key={rowKey(r)}>
                    <td data-label="Hermano"><strong>{hermanoName(r)}</strong></td>
                    <td data-label="Fecha de solicitud">{formatDate(r.requested_at)}</td>
                    <td data-label="Taller">Taller Nº{r.workshop_number} {r.workshop_name}</td>
                    <td data-label="Estado">
                      {isCorrection
                        ? <span className="jr-badge jr-badge-correction">Corrección solicitada</span>
                        : <span className="jr-badge">Pendiente</span>}
                    </td>
                    <td data-label="Acciones">
                      <div className="validation-actions">
                        <Button variant="outline" onClick={() => setInfoModal(r)}>Ver</Button>
                        <Button variant="outline" disabled={busy} onClick={() => { setCorrectionModal(r); setCorrectionNotes(r.correction_notes ?? '') }}>Pedir corrección</Button>
                        <Button variant="outline" disabled={busy} loading={actionKey === `reject-${rowKey(r)}`} onClick={() => reject(r)}>Rechazar</Button>
                        <Button disabled={busy} loading={actionKey === `accept-${rowKey(r)}`} onClick={() => accept(r)}>Aceptar</Button>
                      </div>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      )}

      <Modal open={infoModal !== null} onClose={() => setInfoModal(null)} title="Información del usuario">
        {infoModal && (
          <div className="jr-info">
            <div className="jr-info-row"><span className="jr-info-label">Nombre</span><span className="jr-info-value">{hermanoName(infoModal)}</span></div>
            <div className="jr-info-row"><span className="jr-info-label">Email</span><span className="jr-info-value">{infoModal.user_email}</span></div>
            <div className="jr-info-row"><span className="jr-info-label">Estado</span><span className="jr-info-value">{infoModal.user_status}</span></div>
            <div className="jr-info-row"><span className="jr-info-label">Taller solicitado</span><span className="jr-info-value">Nº{infoModal.workshop_number} · {infoModal.workshop_name}</span></div>
            <div className="jr-info-row"><span className="jr-info-label">Fecha de solicitud</span><span className="jr-info-value">{formatDate(infoModal.requested_at)}</span></div>
            {infoModal.correction_notes && (
              <div className="jr-info-row"><span className="jr-info-label">Corrección pedida</span><span className="jr-info-value">{infoModal.correction_notes}</span></div>
            )}
          </div>
        )}
      </Modal>

      <Modal open={correctionModal !== null} onClose={() => { setCorrectionModal(null); setCorrectionNotes('') }} title="Solicitar corrección de datos">
        {correctionModal && (
          <div className="jr-correction">
            <p className="jr-correction-info"><Info size={14} /> Indicá qué datos debe corregir <strong>{hermanoName(correctionModal)}</strong> antes de aprobar su ingreso.</p>
            <textarea
              className="jr-textarea"
              rows={4}
              value={correctionNotes}
              onChange={(e) => setCorrectionNotes(e.target.value)}
              placeholder="Ej: El DNI no coincide con el nombre, por favor actualizá tus datos..."
            />
            <div className="jr-correction-actions">
              <Button variant="outline" onClick={() => { setCorrectionModal(null); setCorrectionNotes('') }}>Cancelar</Button>
              <Button
                loading={actionKey === `correction-${rowKey(correctionModal)}`}
                disabled={!correctionNotes.trim()}
                onClick={submitCorrection}
              >Enviar solicitud de corrección</Button>
            </div>
          </div>
        )}
      </Modal>
    </section>
  )
}

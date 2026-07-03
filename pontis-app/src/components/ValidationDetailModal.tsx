import Badge from '@/components/Badge'
import Button from '@/components/Button'
import Modal from '@/components/Modal'
import { formatDate } from '@/utils/date'
import type { ValidationRow } from '@/pages/validations/validationRow'
import './ValidationDetailModal.css'

const STATUS_LABELS: Record<string, string> = {
  declared: 'Pendiente de validación',
  validated: 'Validado',
  rejected: 'Rechazado',
}
const STATUS_VARIANTS: Record<string, 'default' | 'success' | 'warning' | 'error'> = {
  declared: 'warning',
  validated: 'success',
  rejected: 'error',
}

interface Props {
  row: ValidationRow | null
  busy?: boolean
  onClose: () => void
  onAccept: (row: ValidationRow) => void
  onReject: (row: ValidationRow) => void
}

/**
 * Modal de detalle de una validación masónica, compartido por el flujo de
 * Admin de Taller y de Superadmin. Muestra todos los datos disponibles y
 * expone las mismas acciones de aceptar/rechazar que la fila de la tabla.
 */
export default function ValidationDetailModal({ row, busy, onClose, onAccept, onReject }: Props) {
  return (
    <Modal open={row !== null} onClose={onClose} title="Detalle de validación">
      {row && (
        <div className="validation-detail">
          <div className="validation-detail-grid">
            <div className="validation-detail-row">
              <span className="validation-detail-label">Hermano</span>
              <span className="validation-detail-value">{row.hermano}</span>
            </div>
            <div className="validation-detail-row">
              <span className="validation-detail-label">Fecha de solicitud</span>
              <span className="validation-detail-value">{row.requestDate ? formatDate(row.requestDate) : '-'}</span>
            </div>
            <div className="validation-detail-row">
              <span className="validation-detail-label">Taller</span>
              <span className="validation-detail-value">{row.taller}</span>
            </div>
            <div className="validation-detail-row">
              <span className="validation-detail-label">Tipo de registro</span>
              <span className="validation-detail-value">{row.recordTypeLabel}</span>
            </div>
            <div className="validation-detail-row">
              <span className="validation-detail-label">Dato solicitado</span>
              <span className="validation-detail-value">{row.details}</span>
            </div>
            <div className="validation-detail-row">
              <span className="validation-detail-label">Notas</span>
              <span className="validation-detail-value">{row.notes ?? '—'}</span>
            </div>
            <div className="validation-detail-row">
              <span className="validation-detail-label">Estado</span>
              <span className="validation-detail-value">
                <Badge variant={STATUS_VARIANTS[row.status]}>{STATUS_LABELS[row.status]}</Badge>
              </span>
            </div>
          </div>
          <div className="validation-detail-actions">
            <Button variant="outline" disabled={busy} onClick={() => onReject(row)}>Rechazar</Button>
            <Button loading={busy} onClick={() => onAccept(row)}>Aceptar</Button>
          </div>
        </div>
      )}
    </Modal>
  )
}

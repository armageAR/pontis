import { useEffect, useState } from 'react'
import Alert from '@/components/Alert'
import Button from '@/components/Button'
import EmptyState from '@/components/EmptyState'
import Spinner from '@/components/Spinner'
import ValidationDetailModal from '@/components/ValidationDetailModal'
import ChangeRequestsPage from '@/pages/change-requests/ChangeRequestsPage'
import JoinRequestsSection from './JoinRequestsSection'
import * as profileApi from '@/api/profile'
import { useAuth } from '@/context/AuthContext'
import { formatDate } from '@/utils/date'
import { degreeToRow, positionToRow, type ValidationRow } from './validationRow'
import './DegreeValidationPage.css'

export default function DegreeValidationPage() {
  const { user } = useAuth()
  const isReviewer = user?.role === 'superadmin' || (user?.admin_workshops?.length ?? 0) > 0
  const [rows, setRows] = useState<ValidationRow[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [detail, setDetail] = useState<ValidationRow | null>(null)
  const [actionKey, setActionKey] = useState<string | null>(null)

  async function load() {
    setLoading(true); setError('')
    try {
      const [d, p] = await Promise.all([
        profileApi.getPendingDegreeValidations(),
        profileApi.getPendingPositionValidations(),
      ])
      setRows([...d.data.map(degreeToRow), ...p.data.map(positionToRow)])
    } catch {
      setError('No se pudieron cargar las validaciones pendientes.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [])

  function removeRow(row: ValidationRow) {
    setRows((prev) => prev.filter((r) => r.key !== row.key))
  }

  async function accept(row: ValidationRow) {
    setActionKey(`accept-${row.key}`)
    try {
      if (row.type === 'degree') await profileApi.validateDegree(row.id)
      else await profileApi.validatePosition(row.id)
      removeRow(row)
      setDetail(null)
    } finally {
      setActionKey(null)
    }
  }

  async function reject(row: ValidationRow) {
    setActionKey(`reject-${row.key}`)
    try {
      if (row.type === 'degree') await profileApi.rejectDegree(row.id)
      else await profileApi.rejectPosition(row.id)
      removeRow(row)
      setDetail(null)
    } finally {
      setActionKey(null)
    }
  }

  if (loading) return <div className="validation-loading"><Spinner /></div>

  return (
    <div className="validation-page">
      {error && <Alert>{error}</Alert>}
      <h2 className="validation-section-title">Grados y cargos</h2>
      {rows.length === 0 ? (
        <EmptyState
          title="Sin validaciones pendientes"
          description="No hay grados ni cargos declarados pendientes de revisión."
        />
      ) : (
        <div className="validation-table-wrap">
          <table className="validation-table">
            <thead>
              <tr>
                <th>Hermano</th>
                <th>Fecha de solicitud</th>
                <th>Taller</th>
                <th>Registro</th>
                <th className="validation-th-actions">Acciones</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => {
                const busy = actionKey === `accept-${row.key}` || actionKey === `reject-${row.key}`
                return (
                  <tr key={row.key}>
                    <td data-label="Hermano"><strong>{row.hermano}</strong></td>
                    <td data-label="Fecha de solicitud">{row.requestDate ? formatDate(row.requestDate) : '-'}</td>
                    <td data-label="Taller">{row.taller}</td>
                    <td data-label="Registro">{row.recordTypeLabel}: {row.details}</td>
                    <td data-label="Acciones">
                      <div className="validation-actions">
                        <Button variant="outline" onClick={() => setDetail(row)}>Ver</Button>
                        <Button variant="outline" disabled={busy} loading={actionKey === `reject-${row.key}`} onClick={() => reject(row)}>Rechazar</Button>
                        <Button disabled={busy} loading={actionKey === `accept-${row.key}`} onClick={() => accept(row)}>Aceptar</Button>
                      </div>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      )}

      {isReviewer && <JoinRequestsSection />}

      {isReviewer && (
        <section className="validation-change-requests">
          <ChangeRequestsPage embedded mode="review" />
        </section>
      )}

      <ValidationDetailModal
        row={detail}
        busy={detail !== null && (actionKey === `accept-${detail.key}` || actionKey === `reject-${detail.key}`)}
        onClose={() => setDetail(null)}
        onAccept={accept}
        onReject={reject}
      />
    </div>
  )
}

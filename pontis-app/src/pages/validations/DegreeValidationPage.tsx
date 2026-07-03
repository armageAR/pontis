import { useEffect, useState } from 'react'
import Alert from '@/components/Alert'
import Badge from '@/components/Badge'
import Button from '@/components/Button'
import EmptyState from '@/components/EmptyState'
import Spinner from '@/components/Spinner'
import * as profileApi from '@/api/profile'
import type { UserDegree, UserPosition } from '@/api/profile'
import { formatDate } from '@/utils/date'
import './DegreeValidationPage.css'

const DEGREE_LABELS: Record<string, string> = { aprendiz: 'Aprendiz', companero: 'Compañero', maestro: 'Maestro' }

export default function DegreeValidationPage() {
  const [degrees, setDegrees] = useState<UserDegree[]>([])
  const [positions, setPositions] = useState<UserPosition[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  async function load() {
    setLoading(true); setError('')
    try {
      const [d, p] = await Promise.all([
        profileApi.getPendingDegreeValidations(),
        profileApi.getPendingPositionValidations(),
      ])
      setDegrees(d.data); setPositions(p.data)
    } catch {
      setError('No se pudieron cargar las validaciones pendientes.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [])

  async function validateDegree(id: number) { await profileApi.validateDegree(id); load() }
  async function rejectDegree(id: number) { await profileApi.rejectDegree(id); load() }
  async function validatePosition(id: number) { await profileApi.validatePosition(id); load() }
  async function rejectPosition(id: number) { await profileApi.rejectPosition(id); load() }

  if (loading) return <div className="validation-loading"><Spinner /></div>

  return (
    <div className="validation-page">
      {error && <Alert>{error}</Alert>}
      {degrees.length === 0 && positions.length === 0 ? (
        <EmptyState title="Sin validaciones pendientes" description="No hay grados ni cargos declarados pendientes de revisión." />
      ) : (
        <>
          <section>
            <h2 className="validation-title">Grados pendientes</h2>
            <div className="validation-list">
              {degrees.map(d => (
                <div key={d.id} className="validation-row">
                  <div>
                    <strong>{d.user ? `${d.user.last_name ? `${d.user.last_name}, ` : ''}${d.user.name}` : '-'}</strong>
                    <span>{DEGREE_LABELS[d.degree]} · {d.workshop ? `Taller Nº${d.workshop.number} ${d.workshop.name}` : 'Sin taller'} · {formatDate(d.start_date)}</span>
                  </div>
                  <Badge variant="warning">Pendiente</Badge>
                  <div className="validation-actions">
                    <Button variant="outline" onClick={() => rejectDegree(d.id)}>Rechazar</Button>
                    <Button onClick={() => validateDegree(d.id)}>Validar</Button>
                  </div>
                </div>
              ))}
            </div>
          </section>
          <section>
            <h2 className="validation-title">Cargos pendientes</h2>
            <div className="validation-list">
              {positions.map(p => (
                <div key={p.id} className="validation-row">
                  <div>
                    <strong>{p.user ? `${p.user.last_name ? `${p.user.last_name}, ` : ''}${p.user.name}` : '-'}</strong>
                    <span>{p.position?.name ?? 'Cargo'} · Taller Nº{p.workshop?.number} {p.workshop?.name} · {formatDate(p.start_date)}</span>
                  </div>
                  <Badge variant="warning">Pendiente</Badge>
                  <div className="validation-actions">
                    <Button variant="outline" onClick={() => rejectPosition(p.id)}>Rechazar</Button>
                    <Button onClick={() => validatePosition(p.id)}>Validar</Button>
                  </div>
                </div>
              ))}
            </div>
          </section>
        </>
      )}
    </div>
  )
}

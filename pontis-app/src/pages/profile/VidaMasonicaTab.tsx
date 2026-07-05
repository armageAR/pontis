import { useState, type ReactNode, type FormEvent } from 'react'
import { Star, LogOut, Plus, Pencil, Trash2 } from 'lucide-react'
import Button from '@/components/Button'
import Badge from '@/components/Badge'
import Modal from '@/components/Modal'
import Alert from '@/components/Alert'
import ConfirmDialog from '@/components/ConfirmDialog'
import FormField from '@/components/FormField'
import Input from '@/components/Input'
import WorkshopPicker from '@/components/WorkshopPicker'
import InlineField from './InlineField'
import SensitiveField from './SensitiveField'
import * as profileApi from '@/api/profile'
import type { Profile, UserDegree, UserPosition } from '@/api/profile'
import * as workshopsApi from '@/api/workshops'
import type { WorkshopSearchResult, ProfileWorkshop } from '@/api/workshops'
import type { VisibilityBlock } from '@/api/visibility'
import type { FieldStatus } from './useProfileAutosave'
import { apiError } from '@/utils/errors'
import { formatDate, toDateInputValue } from '@/utils/date'

interface PositionCatalogItem { id: number; name: string }
interface WorkshopItem { id: number; name: string; number: number }

const DEGREE_LABELS: Record<string, string> = { aprendiz: 'Aprendiz', companero: 'Compañero', maestro: 'Maestro' }
const VALIDATION_LABELS: Record<string, string> = { declared: 'Pendiente de validación', validated: 'Validado', rejected: 'Rechazado' }
const VALIDATION_VARIANTS: Record<string, 'default'|'success'|'warning'|'error'> = { declared: 'warning', validated: 'success', rejected: 'error' }
const DEGREE_ORDER = ['aprendiz', 'companero', 'maestro']
const MASONIC_STATUS_LABELS: Record<string, string> = {
  active: 'Activo', inactive: 'Inactivo', suspended: 'Suspendido', discharged: 'Dado de baja', deceased: 'O Eterno',
}
const WORKSHOP_STATUS_LABELS: Record<string, string> = { active: 'Activo', pending: 'Pendiente de aprobación' }

// Próximo grado válido según el historial (aprendiz -> companero -> maestro), o null si ya es Maestro.
function nextValidDegree(list: { degree: string }[]): string | null {
  const maxRank = list.reduce((m, d) => Math.max(m, DEGREE_ORDER.indexOf(d.degree)), -1)
  return DEGREE_ORDER[maxRank + 1] ?? null
}

interface VidaMasonicaTabProps {
  profile: Profile
  isSuperAdmin: boolean
  statuses: Record<string, FieldStatus>
  commitField: (key: keyof Profile, value: string | null) => Promise<void>
  sectionPrivacy: (block: VisibilityBlock) => ReactNode
  degrees: UserDegree[]
  setDegrees: React.Dispatch<React.SetStateAction<UserDegree[]>>
  positions: UserPosition[]
  setPositions: React.Dispatch<React.SetStateAction<UserPosition[]>>
  positionCatalog: PositionCatalogItem[]
  myWorkshops: WorkshopItem[]
  profileWorkshops: ProfileWorkshop[]
  reloadProfileWorkshops: () => Promise<void>
  onError: (msg: string) => void
  onSuccess: (msg: string) => void
}

export default function VidaMasonicaTab({
  profile, isSuperAdmin, statuses, commitField, sectionPrivacy,
  degrees, setDegrees, positions, setPositions, positionCatalog, myWorkshops,
  profileWorkshops, reloadProfileWorkshops, onError, onSuccess,
}: VidaMasonicaTabProps) {
  const [showDegreeModal, setShowDegreeModal] = useState(false)
  const [showPositionModal, setShowPositionModal] = useState(false)
  const [editingDegree, setEditingDegree] = useState<UserDegree | null>(null)
  const [editingPosition, setEditingPosition] = useState<UserPosition | null>(null)
  const [confirmDeleteDegree, setConfirmDeleteDegree] = useState<UserDegree | null>(null)
  const [confirmDeletePosition, setConfirmDeletePosition] = useState<UserPosition | null>(null)
  const [degreeForm, setDegreeForm] = useState({ degree: 'aprendiz', workshop_id: '', start_date: '', notes: '' })
  const [positionForm, setPositionForm] = useState({ position_id: '', workshop_id: '', start_date: '', end_date: '', notes: '' })
  const [degreeModalError, setDegreeModalError] = useState('')
  const [positionModalError, setPositionModalError] = useState('')

  const [showAddWorkshopModal, setShowAddWorkshopModal] = useState(false)
  const [addWorkshopSel, setAddWorkshopSel] = useState<WorkshopSearchResult | null>(null)
  const [joiningWorkshop, setJoiningWorkshop] = useState(false)
  const [confirmLeaveWs, setConfirmLeaveWs] = useState<ProfileWorkshop | null>(null)
  const [confirmPrincipalWs, setConfirmPrincipalWs] = useState<ProfileWorkshop | null>(null)

  async function handleSaveDegree(e: FormEvent) {
    e.preventDefault()
    setDegreeModalError('')
    try {
      // El grado no lleva fecha de fin manual: el período se deriva del siguiente grado.
      const payload = {
        degree: degreeForm.degree as 'aprendiz'|'companero'|'maestro',
        workshop_id: degreeForm.workshop_id ? Number(degreeForm.workshop_id) : null,
        start_date: degreeForm.start_date,
        notes: degreeForm.notes || null,
      }
      if (editingDegree) {
        await profileApi.updateDegree(editingDegree.id, payload)
      } else {
        await profileApi.addDegree(payload)
      }
      // Recargar para reflejar los fines de período derivados del historial.
      setDegrees(await profileApi.getDegrees())
      setShowDegreeModal(false)
    } catch (err) { setDegreeModalError(apiError(err, 'Error al guardar el grado.')) }
  }

  async function handleDeleteDegree(id: number) {
    await profileApi.deleteDegree(id)
    setDegrees(await profileApi.getDegrees())
    setConfirmDeleteDegree(null)
  }

  async function handleSavePosition(e: FormEvent) {
    e.preventDefault()
    setPositionModalError('')
    try {
      const payload = { ...positionForm, position_id: Number(positionForm.position_id), workshop_id: Number(positionForm.workshop_id), end_date: positionForm.end_date || null, notes: positionForm.notes || null }
      if (editingPosition) {
        const updated = await profileApi.updatePosition(editingPosition.id, payload as never)
        setPositions(ps => ps.map(p => p.id === updated.id ? updated : p))
      } else {
        const created = await profileApi.addPosition(payload as never)
        setPositions(ps => [created, ...ps])
      }
      setShowPositionModal(false)
    } catch (err) { setPositionModalError(apiError(err, 'Error al guardar el cargo.')) }
  }

  async function handleDeletePosition(id: number) {
    await profileApi.deletePosition(id)
    setPositions(ps => ps.filter(p => p.id !== id))
    setConfirmDeletePosition(null)
  }

  async function handleJoinWorkshop() {
    if (!addWorkshopSel) return
    setJoiningWorkshop(true)
    try {
      await workshopsApi.joinWorkshop(addWorkshopSel.id)
      await reloadProfileWorkshops()
      onSuccess('Solicitud enviada. Queda pendiente de aprobación.')
      setShowAddWorkshopModal(false)
      setAddWorkshopSel(null)
    } catch (err) {
      onError(apiError(err, 'No se pudo enviar la solicitud de ingreso.'))
    } finally { setJoiningWorkshop(false) }
  }

  async function handleLeaveWorkshop(ws: ProfileWorkshop) {
    try {
      await workshopsApi.leaveWorkshop(ws.id)
      await reloadProfileWorkshops()
      onSuccess(`Saliste del taller ${ws.name}.`)
    } catch (err) {
      onError(apiError(err, 'No se pudo salir del taller.'))
    } finally { setConfirmLeaveWs(null) }
  }

  async function handleSetPrincipal(ws: ProfileWorkshop) {
    try {
      await workshopsApi.setPrincipalWorkshop(ws.id)
      await reloadProfileWorkshops()
      onSuccess(`${ws.name} es ahora tu taller principal.`)
    } catch (err) {
      onError(apiError(err, 'No se pudo cambiar el taller principal.'))
    } finally { setConfirmPrincipalWs(null) }
  }

  return (
    <div className="profile-masonica">
      <div className="profile-fields profile-masonica-scalars">
        <SensitiveField label="Matrícula masónica" fieldKey="masonic_id" value={profile.masonic_id ?? ''} isSuperAdmin={isSuperAdmin} status={statuses.masonic_id} onCommit={v => commitField('masonic_id', v)} />
        <InlineField label="Estado masónico" as="select" value={profile.masonic_status ?? 'active'} status={statuses.masonic_status} onCommit={v => commitField('masonic_status', v)}>
          {Object.entries(MASONIC_STATUS_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
        </InlineField>
        <InlineField label="Fecha de iniciación" type="date" value={toDateInputValue(profile.initiation_date)} status={statuses.initiation_date} onCommit={v => commitField('initiation_date', v)} />
        {sectionPrivacy('masonic')}
      </div>

      {/* Grados */}
      <section className="profile-section">
        <div className="profile-section-header">
          <h2 className="profile-section-title">Grados</h2>
          <Button
            disabled={nextValidDegree(degrees) === null}
            title={nextValidDegree(degrees) === null ? 'Ya alcanzaste el grado de Maestro.' : undefined}
            onClick={() => { setEditingDegree(null); setDegreeModalError(''); setDegreeForm({ degree: nextValidDegree(degrees) ?? 'aprendiz', workshop_id: '', start_date: '', notes: '' }); setShowDegreeModal(true) }}
          ><Plus size={16} /> Agregar</Button>
        </div>
        {degrees.length === 0 ? <p className="profile-empty">Sin grados registrados.</p> : (
          <div className="profile-table-scroll">
            <table className="profile-table">
              <thead><tr><th>Grado</th><th>Taller</th><th>Inicio</th><th>Fin</th><th>Validación</th><th></th></tr></thead>
              <tbody>
                {degrees.map(d => (
                  <tr key={d.id}>
                    <td><Badge variant="default">{DEGREE_LABELS[d.degree]}</Badge></td>
                    <td>{d.workshop?.name ?? '-'}</td>
                    <td>{formatDate(d.start_date)}</td>
                    <td>{formatDate(d.end_date)}</td>
                    <td><Badge variant={VALIDATION_VARIANTS[d.validation_status]}>{VALIDATION_LABELS[d.validation_status]}</Badge></td>
                    <td className="profile-table-actions">
                      <button className="profile-icon-btn" title={`Editar grado de ${DEGREE_LABELS[d.degree]}`} aria-label={`Editar grado de ${DEGREE_LABELS[d.degree]}`} onClick={() => { setEditingDegree(d); setDegreeModalError(''); setDegreeForm({ degree: d.degree, workshop_id: d.workshop_id?.toString() ?? '', start_date: toDateInputValue(d.start_date), notes: d.notes ?? '' }); setShowDegreeModal(true) }}>
                        <Pencil size={16} />
                      </button>
                      <button className="profile-icon-btn profile-icon-danger" title={`Eliminar grado de ${DEGREE_LABELS[d.degree]}`} aria-label={`Eliminar grado de ${DEGREE_LABELS[d.degree]}`} onClick={() => setConfirmDeleteDegree(d)}>
                        <Trash2 size={16} />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        {sectionPrivacy('degrees')}
      </section>

      {/* Cargos */}
      <section className="profile-section">
        <div className="profile-section-header">
          <h2 className="profile-section-title">Cargos</h2>
          <Button onClick={() => { setEditingPosition(null); setPositionModalError(''); setPositionForm({ position_id: '', workshop_id: '', start_date: '', end_date: '', notes: '' }); setShowPositionModal(true) }}>
            <Plus size={16} /> Agregar
          </Button>
        </div>
        {positions.length === 0 ? <p className="profile-empty">Sin cargos registrados.</p> : (
          <div className="profile-table-scroll">
            <table className="profile-table">
              <thead><tr><th>Cargo</th><th>Taller</th><th>Inicio</th><th>Fin</th><th>Validación</th><th></th></tr></thead>
              <tbody>
                {positions.map(p => (
                  <tr key={p.id}>
                    <td>{p.position?.name ?? '-'}</td>
                    <td>{p.workshop?.name ?? '-'}</td>
                    <td>{formatDate(p.start_date)}</td>
                    <td>{formatDate(p.end_date)}</td>
                    <td><Badge variant={VALIDATION_VARIANTS[p.validation_status]}>{VALIDATION_LABELS[p.validation_status]}</Badge></td>
                    <td className="profile-table-actions">
                      <button className="profile-icon-btn" title={`Editar cargo de ${p.position?.name ?? ''}`} aria-label={`Editar cargo de ${p.position?.name ?? ''}`} onClick={() => { setEditingPosition(p); setPositionModalError(''); setPositionForm({ position_id: p.position_id.toString(), workshop_id: p.workshop_id.toString(), start_date: toDateInputValue(p.start_date), end_date: toDateInputValue(p.end_date), notes: p.notes ?? '' }); setShowPositionModal(true) }}>
                        <Pencil size={16} />
                      </button>
                      <button className="profile-icon-btn profile-icon-danger" title={`Eliminar cargo de ${p.position?.name ?? ''}`} aria-label={`Eliminar cargo de ${p.position?.name ?? ''}`} onClick={() => setConfirmDeletePosition(p)}>
                        <Trash2 size={16} />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        {sectionPrivacy('positions')}
      </section>

      {/* Mis Talleres */}
      <section className="profile-section">
        <div className="profile-section-header">
          <h2 className="profile-section-title">Talleres</h2>
          <Button onClick={() => { setAddWorkshopSel(null); setShowAddWorkshopModal(true) }}>
            <Plus size={16} /> Agregar Taller
          </Button>
        </div>
        <p className="profile-section-desc">
          Talleres a los que pertenecés y solicitudes de ingreso pendientes. Elegí tu Taller principal o salí de un Taller.
        </p>
        {profileWorkshops.length === 0 ? (
          <p className="profile-empty">No perteneces a ningún Taller ni tenés solicitudes pendientes.</p>
        ) : (
          <div className="profile-table-scroll">
            <table className="profile-table">
              <thead><tr><th>Taller</th><th>Estado</th><th>Principal</th><th></th></tr></thead>
              <tbody>
                {profileWorkshops.map(w => (
                  <tr key={w.id}>
                    <td>
                      {w.name} <span className="profile-ws-number">N° {w.number}</span>
                      {w.my_role === 'admin' && <Badge variant="default">Admin</Badge>}
                    </td>
                    <td>
                      <Badge variant={w.status === 'active' ? 'success' : 'warning'}>
                        {WORKSHOP_STATUS_LABELS[w.status] ?? w.status}
                      </Badge>
                    </td>
                    <td>{w.is_principal ? <Badge variant="success">Principal</Badge> : <span className="profile-muted">—</span>}</td>
                    <td className="profile-table-actions">
                      {w.status === 'active' && !w.is_principal && (
                        <button className="profile-icon-btn" title={`Marcar ${w.name} como principal`} aria-label={`Marcar ${w.name} como principal`} onClick={() => setConfirmPrincipalWs(w)}>
                          <Star size={16} />
                        </button>
                      )}
                      {w.is_principal ? (
                        <button className="profile-icon-btn profile-icon-btn-disabled" title="No podés salir de tu Taller principal" aria-label="No podés salir de tu Taller principal" disabled>
                          <LogOut size={16} />
                        </button>
                      ) : (
                        <button className="profile-icon-btn profile-icon-danger" title={`Salir del taller ${w.name}`} aria-label={`Salir del taller ${w.name}`} onClick={() => setConfirmLeaveWs(w)}>
                          <LogOut size={16} />
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      <Modal open={showDegreeModal} onClose={() => setShowDegreeModal(false)} title={editingDegree ? 'Editar grado' : 'Agregar grado'}>
        <form onSubmit={handleSaveDegree} className="profile-modal-form">
          {degreeModalError && <Alert variant="error">{degreeModalError}</Alert>}
          <FormField label="Grado">
            <select className="profile-select" value={degreeForm.degree} onChange={e => setDegreeForm(f => ({ ...f, degree: e.target.value }))}>
              {editingDegree
                ? DEGREE_ORDER.map(dg => <option key={dg} value={dg}>{DEGREE_LABELS[dg]}</option>)
                : (() => { const nx = nextValidDegree(degrees); return nx ? <option value={nx}>{DEGREE_LABELS[nx]}</option> : null })()}
            </select>
            {!editingDegree && (
              <p className="profile-modal-note">Los grados progresan en orden: Aprendiz → Compañero → Maestro. Cada grado termina cuando comienza el siguiente.</p>
            )}
          </FormField>
          <FormField label="Taller">
            <select className="profile-select" value={degreeForm.workshop_id} onChange={e => setDegreeForm(f => ({ ...f, workshop_id: e.target.value }))}>
              <option value="">— Sin taller —</option>
              {myWorkshops.map(w => <option key={w.id} value={w.id}>{w.name} (N° {w.number})</option>)}
            </select>
          </FormField>
          <FormField label="Fecha de inicio *"><Input type="date" required value={degreeForm.start_date} onChange={e => setDegreeForm(f => ({ ...f, start_date: e.target.value }))} /></FormField>
          <FormField label="Notas"><Input value={degreeForm.notes} onChange={e => setDegreeForm(f => ({ ...f, notes: e.target.value }))} /></FormField>
          <div className="profile-modal-actions">
            <Button type="button" variant="outline" onClick={() => setShowDegreeModal(false)}>Cancelar</Button>
            <Button type="submit">Guardar</Button>
          </div>
        </form>
      </Modal>

      <Modal open={showPositionModal} onClose={() => setShowPositionModal(false)} title={editingPosition ? 'Editar cargo' : 'Agregar cargo'}>
        <form onSubmit={handleSavePosition} className="profile-modal-form">
          {positionModalError && <Alert variant="error">{positionModalError}</Alert>}
          <FormField label="Cargo *">
            <select className="profile-select" required value={positionForm.position_id} onChange={e => setPositionForm(f => ({ ...f, position_id: e.target.value }))}>
              <option value="">Seleccioná un cargo</option>
              {positionCatalog.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
            </select>
          </FormField>
          <FormField label="Taller *">
            <select className="profile-select" required value={positionForm.workshop_id} onChange={e => setPositionForm(f => ({ ...f, workshop_id: e.target.value }))}>
              <option value="">Seleccioná un taller</option>
              {myWorkshops.map(w => <option key={w.id} value={w.id}>{w.name} (N° {w.number})</option>)}
            </select>
          </FormField>
          <FormField label="Fecha de inicio *"><Input type="date" required value={positionForm.start_date} onChange={e => setPositionForm(f => ({ ...f, start_date: e.target.value }))} /></FormField>
          <FormField label="Fecha de fin"><Input type="date" value={positionForm.end_date} onChange={e => setPositionForm(f => ({ ...f, end_date: e.target.value }))} /></FormField>
          <FormField label="Notas"><Input value={positionForm.notes} onChange={e => setPositionForm(f => ({ ...f, notes: e.target.value }))} /></FormField>
          <div className="profile-modal-actions">
            <Button type="button" variant="outline" onClick={() => setShowPositionModal(false)}>Cancelar</Button>
            <Button type="submit">Guardar</Button>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        open={confirmDeleteDegree !== null}
        title="Eliminar grado"
        message={confirmDeleteDegree ? `¿Eliminar el registro de grado de ${DEGREE_LABELS[confirmDeleteDegree.degree]}?` : ''}
        confirmLabel="Eliminar"
        onConfirm={() => confirmDeleteDegree && handleDeleteDegree(confirmDeleteDegree.id)}
        onClose={() => setConfirmDeleteDegree(null)}
      />
      <ConfirmDialog
        open={confirmDeletePosition !== null}
        title="Eliminar cargo"
        message={confirmDeletePosition ? `¿Eliminar el registro de cargo de ${confirmDeletePosition.position?.name ?? ''}?` : ''}
        confirmLabel="Eliminar"
        onConfirm={() => confirmDeletePosition && handleDeletePosition(confirmDeletePosition.id)}
        onClose={() => setConfirmDeletePosition(null)}
      />

      <Modal open={showAddWorkshopModal} onClose={() => setShowAddWorkshopModal(false)} title="Agregar Taller">
        <div className="profile-modal-form">
          <p className="profile-modal-note">
            Buscá el Taller por nombre o número y solicitá tu ingreso. La solicitud queda pendiente hasta que un administrador del Taller la apruebe.
          </p>
          <FormField label="Taller">
            <WorkshopPicker value={addWorkshopSel} onChange={setAddWorkshopSel} />
          </FormField>
          <div className="profile-modal-actions">
            <Button type="button" variant="outline" onClick={() => setShowAddWorkshopModal(false)}>Cancelar</Button>
            {addWorkshopSel && (
              <Button type="button" loading={joiningWorkshop} onClick={handleJoinWorkshop}>Solicitar unirse</Button>
            )}
          </div>
        </div>
      </Modal>

      <ConfirmDialog
        open={confirmLeaveWs !== null}
        title="Salir del Taller"
        message={confirmLeaveWs ? `¿Salir del taller ${confirmLeaveWs.name} (N° ${confirmLeaveWs.number})? También se eliminarán tus cargos asociados a este Taller.` : ''}
        confirmLabel="Salir"
        onConfirm={() => confirmLeaveWs && handleLeaveWorkshop(confirmLeaveWs)}
        onClose={() => setConfirmLeaveWs(null)}
      />

      <ConfirmDialog
        open={confirmPrincipalWs !== null}
        title="Cambiar Taller principal"
        message={(() => {
          if (!confirmPrincipalWs) return ''
          const current = profileWorkshops.find(w => w.is_principal)
          return current
            ? `${current.name} dejará de ser tu Taller principal y ${confirmPrincipalWs.name} pasará a ser tu Taller principal.`
            : `${confirmPrincipalWs.name} pasará a ser tu Taller principal.`
        })()}
        confirmLabel="Confirmar"
        onConfirm={() => confirmPrincipalWs && handleSetPrincipal(confirmPrincipalWs)}
        onClose={() => setConfirmPrincipalWs(null)}
      />
    </div>
  )
}

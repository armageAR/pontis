import { useEffect, useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import AppLayout from '@/components/AppLayout'
import Button from '@/components/Button'
import FormField from '@/components/FormField'
import Input from '@/components/Input'
import Alert from '@/components/Alert'
import Spinner from '@/components/Spinner'
import Badge from '@/components/Badge'
import Modal from '@/components/Modal'
import ConfirmDialog from '@/components/ConfirmDialog'
import { useAuth } from '@/context/AuthContext'
import * as profileApi from '@/api/profile'
import type { Profile, UserDegree, UserPosition } from '@/api/profile'
import * as provinceApi from '@/api/provinces'
import * as visibilityApi from '@/api/visibility'
import type { VisibilityLevel, VisibilityBlock, VisibilityMap } from '@/api/visibility'
import client from '@/api/client'
import './ProfilePage.css'

interface PositionCatalogItem { id: number; name: string }
interface WorkshopItem { id: number; name: string; number: number }

const DEGREE_LABELS: Record<string, string> = { aprendiz: 'Aprendiz', companero: 'Compañero', maestro: 'Maestro' }
const MASONIC_STATUS_LABELS: Record<string, string> = {
  active: 'Activo', inactive: 'Inactivo', suspended: 'Suspendido', discharged: 'Dado de baja', deceased: 'Fallecido'
}

export default function ProfilePage() {
  const { user: authUser } = useAuth()
  const isSuperAdmin = authUser?.role === 'superadmin'
  const [profile, setProfile] = useState<Profile | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  const [degrees, setDegrees] = useState<UserDegree[]>([])
  const [positions, setPositions] = useState<UserPosition[]>([])
  const [positionCatalog, setPositionCatalog] = useState<PositionCatalogItem[]>([])
  const [myWorkshops, setMyWorkshops] = useState<WorkshopItem[]>([])
  const [provinces, setProvinces] = useState<provinceApi.Province[]>([])
  const [visibility, setVisibility] = useState<VisibilityMap>({})
  const [savingVisibility, setSavingVisibility] = useState(false)

  const [showDegreeModal, setShowDegreeModal] = useState(false)
  const [showPositionModal, setShowPositionModal] = useState(false)
  const [editingDegree, setEditingDegree] = useState<UserDegree | null>(null)
  const [editingPosition, setEditingPosition] = useState<UserPosition | null>(null)
  const [confirmDeleteDegree, setConfirmDeleteDegree] = useState<number | null>(null)
  const [confirmDeletePosition, setConfirmDeletePosition] = useState<number | null>(null)

  const [degreeForm, setDegreeForm] = useState({ degree: 'aprendiz', workshop_id: '', start_date: '', end_date: '', notes: '' })
  const [positionForm, setPositionForm] = useState({ position_id: '', workshop_id: '', start_date: '', end_date: '', notes: '' })

  useEffect(() => {
    Promise.all([
      profileApi.getProfile(),
      profileApi.getDegrees(),
      profileApi.getPositions(),
      client.get<PositionCatalogItem[]>('/positions').then(r => r.data),
      client.get('/my-workshops').then(r => r.data),
      provinceApi.getProvinces(),
      visibilityApi.getVisibility(),
    ]).then(([p, d, pos, catalog, ws, prov, vis]) => {
      setProfile(p)
      setDegrees(d)
      setPositions(pos)
      setPositionCatalog(catalog)
      const wsArr = Array.isArray(ws) ? ws : (ws.data ?? [])
      setMyWorkshops(wsArr)
      setProvinces(prov)
      setVisibility(vis)
    }).catch(() => setError('Error cargando el perfil.')).finally(() => setLoading(false))
  }, [])

  async function handleSave(e: FormEvent) {
    e.preventDefault()
    if (!profile) return
    setSaving(true); setError(''); setSuccess('')
    try {
      const updated = await profileApi.updateProfile(profile)
      setProfile(updated)
      setSuccess('Perfil actualizado correctamente.')
    } catch { setError('Error al guardar los cambios.') }
    finally { setSaving(false) }
  }

  function field(key: keyof Profile) {
    return (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) =>
      setProfile(p => p ? { ...p, [key]: e.target.value || null } : p)
  }

  async function handleSaveVisibility(e: FormEvent) {
    e.preventDefault(); setSavingVisibility(true)
    const blocks: VisibilityBlock[] = ['identity','masonic','contact','location','profession','bio','degrees','positions']
    const settings = blocks.map(b => ({ block: b, visibility: ((visibility[b]?.visibility) ?? 'workshop') as VisibilityLevel }))
    try {
      const updated = await visibilityApi.updateVisibility(settings)
      setVisibility(updated)
      setSuccess('Configuración de visibilidad guardada.')
    } catch { setError('Error guardando visibilidad.') }
    finally { setSavingVisibility(false) }
  }

  async function handleSaveDegree(e: FormEvent) {
    e.preventDefault()
    try {
      const payload = { ...degreeForm, degree: degreeForm.degree as 'aprendiz'|'companero'|'maestro', workshop_id: degreeForm.workshop_id ? Number(degreeForm.workshop_id) : null, end_date: degreeForm.end_date || null, notes: degreeForm.notes || null }
      if (editingDegree) {
        const updated = await profileApi.updateDegree(editingDegree.id, payload)
        setDegrees(ds => ds.map(d => d.id === updated.id ? updated : d))
      } else {
        const created = await profileApi.addDegree(payload as any)
        setDegrees(ds => [created, ...ds])
      }
      setShowDegreeModal(false)
    } catch { alert('Error al guardar el grado.') }
  }

  async function handleDeleteDegree(id: number) {
    await profileApi.deleteDegree(id)
    setDegrees(ds => ds.filter(d => d.id !== id))
    setConfirmDeleteDegree(null)
  }

  async function handleSavePosition(e: FormEvent) {
    e.preventDefault()
    try {
      const payload = { ...positionForm, position_id: Number(positionForm.position_id), workshop_id: Number(positionForm.workshop_id), end_date: positionForm.end_date || null, notes: positionForm.notes || null }
      if (editingPosition) {
        const updated = await profileApi.updatePosition(editingPosition.id, payload as any)
        setPositions(ps => ps.map(p => p.id === updated.id ? updated : p))
      } else {
        const created = await profileApi.addPosition(payload as any)
        setPositions(ps => [created, ...ps])
      }
      setShowPositionModal(false)
    } catch { alert('Error al guardar el cargo.') }
  }

  async function handleDeletePosition(id: number) {
    await profileApi.deletePosition(id)
    setPositions(ps => ps.filter(p => p.id !== id))
    setConfirmDeletePosition(null)
  }

  if (loading) return <AppLayout><div className="profile-loading"><Spinner /></div></AppLayout>

  return (
    <AppLayout>
      <div className="profile-page">
        <h1 className="profile-title">Mi Perfil</h1>

        {error && <Alert>{error}</Alert>}
        {success && <Alert variant="success">{success}</Alert>}

        <form onSubmit={handleSave}>
          <section className="profile-section">
            <h2 className="profile-section-title">Identidad</h2>
            {!isSuperAdmin && (
              <p className="profile-sensitive-note">Nombre, apellido y DNI son datos sensibles. Para modificarlos, <Link to="/change-requests">solicitá un cambio</Link>.</p>
            )}
            <div className="profile-grid">
              <FormField label="Nombre"><Input value={profile?.name ?? ''} onChange={field('name')} disabled={!isSuperAdmin} /></FormField>
              <FormField label="Apellido"><Input value={profile?.last_name ?? ''} onChange={field('last_name')} disabled={!isSuperAdmin} /></FormField>
              <FormField label="DNI / Documento"><Input value={profile?.dni ?? ''} onChange={field('dni')} disabled={!isSuperAdmin} /></FormField>
              <FormField label="Email"><Input value={profile?.email ?? ''} disabled /></FormField>
              <FormField label="Fecha de nacimiento"><Input type="date" value={profile?.birth_date ?? ''} onChange={field('birth_date')} /></FormField>
            </div>
          </section>

          <section className="profile-section">
            <h2 className="profile-section-title">Datos masónicos</h2>
            <div className="profile-grid">
              <FormField label="Matrícula masónica"><Input value={profile?.masonic_id ?? ''} onChange={field('masonic_id')} disabled={!isSuperAdmin} /></FormField>
              <FormField label="Estado masónico">
                <select className="profile-select" value={profile?.masonic_status ?? 'active'} onChange={field('masonic_status')}>
                  {Object.entries(MASONIC_STATUS_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                </select>
              </FormField>
              <FormField label="Fecha de iniciación"><Input type="date" value={profile?.initiation_date ?? ''} onChange={field('initiation_date')} /></FormField>
            </div>
          </section>

          <section className="profile-section">
            <h2 className="profile-section-title">Contacto</h2>
            <div className="profile-grid">
              <FormField label="Teléfono"><Input value={profile?.phone ?? ''} onChange={field('phone')} /></FormField>
              <FormField label="WhatsApp"><Input value={profile?.whatsapp ?? ''} onChange={field('whatsapp')} /></FormField>
              <FormField label="Email alternativo"><Input type="email" value={profile?.alternative_email ?? ''} onChange={field('alternative_email')} /></FormField>
            </div>
          </section>

          <section className="profile-section">
            <h2 className="profile-section-title">Ubicación</h2>
            <div className="profile-grid">
              <FormField label="País"><Input value={profile?.country ?? ''} onChange={field('country')} /></FormField>
              <FormField label="Provincia">
                <select className="profile-select" value={profile?.province ?? ''} onChange={e => setProfile(p => p ? { ...p, province: e.target.value } : p)}>
                  <option value="">-- Seleccionar --</option>
                  {provinces.map(p => <option key={p.id} value={p.name}>{p.name}</option>)}
                </select>
              </FormField>
              <FormField label="Localidad"><Input value={profile?.locality ?? ''} onChange={field('locality')} /></FormField>
              <FormField label="Barrio"><Input value={profile?.neighborhood ?? ''} onChange={field('neighborhood')} /></FormField>
              <FormField label="Dirección"><Input value={profile?.address ?? ''} onChange={field('address')} /></FormField>
            </div>
          </section>

          <section className="profile-section">
            <h2 className="profile-section-title">Profesión / Actividad</h2>
            <div className="profile-grid">
              <FormField label="Profesión"><Input value={profile?.profession ?? ''} onChange={field('profession')} /></FormField>
              <FormField label="Ocupación / Cargo"><Input value={profile?.occupation ?? ''} onChange={field('occupation')} /></FormField>
              <FormField label="Empresa / Organización"><Input value={profile?.company ?? ''} onChange={field('company')} /></FormField>
            </div>
            <FormField label="Descripción profesional">
              <textarea className="profile-textarea" value={profile?.profession_description ?? ''} onChange={field('profession_description')} rows={3} />
            </FormField>
          </section>

          <section className="profile-section">
            <h2 className="profile-section-title">Presentación</h2>
            <FormField label="Bio">
              <textarea className="profile-textarea" value={profile?.bio ?? ''} onChange={field('bio')} rows={4} placeholder="Contá algo sobre vos..." />
            </FormField>
          </section>

          <div className="profile-save-row">
            <Button type="submit" loading={saving}>Guardar cambios</Button>
          </div>
        </form>

        {/* Visibilidad */}
        <form onSubmit={handleSaveVisibility}>
          <section className="profile-section">
            <h2 className="profile-section-title">Privacidad por sección</h2>
            <p className="profile-sensitive-note">Controlá quién puede ver cada sección de tu perfil.</p>
            <div className="profile-visibility-grid">
              {([ ['identity','Identidad'], ['masonic','Información masónica'], ['contact','Contacto'], ['location','Ubicación'], ['profession','Profesión'], ['bio','Presentación'], ['degrees','Grados'], ['positions','Cargos'] ] as [VisibilityBlock, string][]).map(([block, label]) => (
                <div key={block} className="profile-visibility-row">
                  <span className="profile-visibility-label">{label}</span>
                  <select className="profile-select profile-select-sm"
                    value={visibility[block]?.visibility ?? 'workshop'}
                    onChange={e => setVisibility(v => ({ ...v, [block]: { ...(v[block] as object), block, visibility: e.target.value as VisibilityLevel } }))}>
                    {(Object.entries(visibilityApi.VISIBILITY_LABELS) as [VisibilityLevel, string][]).map(([k, lbl]) => (
                      <option key={k} value={k}>{lbl}</option>
                    ))}
                  </select>
                </div>
              ))}
            </div>
            <div className="profile-save-row">
              <Button type="submit" loading={savingVisibility}>Guardar privacidad</Button>
            </div>
          </section>
        </form>

        {/* Grados */}
        <section className="profile-section">
          <div className="profile-section-header">
            <h2 className="profile-section-title">Grados masónicos</h2>
            <Button onClick={() => { setEditingDegree(null); setDegreeForm({ degree: 'aprendiz', workshop_id: '', start_date: '', end_date: '', notes: '' }); setShowDegreeModal(true) }}>+ Agregar</Button>
          </div>
          {degrees.length === 0 ? <p className="profile-empty">Sin grados registrados.</p> : (
            <table className="profile-table">
              <thead><tr><th>Grado</th><th>Taller</th><th>Inicio</th><th>Fin</th><th></th></tr></thead>
              <tbody>
                {degrees.map(d => (
                  <tr key={d.id}>
                    <td><Badge variant="default">{DEGREE_LABELS[d.degree]}</Badge></td>
                    <td>{d.workshop?.name ?? '-'}</td>
                    <td>{d.start_date}</td>
                    <td>{d.end_date ?? '-'}</td>
                    <td className="profile-table-actions">
                      <button className="profile-link-btn" onClick={() => { setEditingDegree(d); setDegreeForm({ degree: d.degree, workshop_id: d.workshop_id?.toString() ?? '', start_date: d.start_date, end_date: d.end_date ?? '', notes: d.notes ?? '' }); setShowDegreeModal(true) }}>Editar</button>
                      <button className="profile-link-btn profile-link-danger" onClick={() => setConfirmDeleteDegree(d.id)}>Eliminar</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </section>

        {/* Cargos */}
        <section className="profile-section">
          <div className="profile-section-header">
            <h2 className="profile-section-title">Cargos en talleres</h2>
            <Button onClick={() => { setEditingPosition(null); setPositionForm({ position_id: '', workshop_id: '', start_date: '', end_date: '', notes: '' }); setShowPositionModal(true) }}>+ Agregar</Button>
          </div>
          {positions.length === 0 ? <p className="profile-empty">Sin cargos registrados.</p> : (
            <table className="profile-table">
              <thead><tr><th>Cargo</th><th>Taller</th><th>Inicio</th><th>Fin</th><th></th></tr></thead>
              <tbody>
                {positions.map(p => (
                  <tr key={p.id}>
                    <td>{p.position?.name ?? '-'}</td>
                    <td>{p.workshop?.name ?? '-'}</td>
                    <td>{p.start_date}</td>
                    <td>{p.end_date ?? '-'}</td>
                    <td className="profile-table-actions">
                      <button className="profile-link-btn" onClick={() => { setEditingPosition(p); setPositionForm({ position_id: p.position_id.toString(), workshop_id: p.workshop_id.toString(), start_date: p.start_date, end_date: p.end_date ?? '', notes: p.notes ?? '' }); setShowPositionModal(true) }}>Editar</button>
                      <button className="profile-link-btn profile-link-danger" onClick={() => setConfirmDeletePosition(p.id)}>Eliminar</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </section>
      </div>

      <Modal open={showDegreeModal} onClose={() => setShowDegreeModal(false)} title={editingDegree ? 'Editar grado' : 'Agregar grado'}>
        <form onSubmit={handleSaveDegree} className="profile-modal-form">
          <FormField label="Grado">
            <select className="profile-select" value={degreeForm.degree} onChange={e => setDegreeForm(f => ({ ...f, degree: e.target.value }))}>
              <option value="aprendiz">Aprendiz</option>
              <option value="companero">Compañero</option>
              <option value="maestro">Maestro</option>
            </select>
          </FormField>
          <FormField label="Taller">
            <select className="profile-select" value={degreeForm.workshop_id} onChange={e => setDegreeForm(f => ({ ...f, workshop_id: e.target.value }))}>
              <option value="">Sin taller</option>
              {myWorkshops.map(w => <option key={w.id} value={w.id}>Nº{w.number} {w.name}</option>)}
            </select>
          </FormField>
          <FormField label="Fecha de inicio *"><Input type="date" required value={degreeForm.start_date} onChange={e => setDegreeForm(f => ({ ...f, start_date: e.target.value }))} /></FormField>
          <FormField label="Fecha de fin"><Input type="date" value={degreeForm.end_date} onChange={e => setDegreeForm(f => ({ ...f, end_date: e.target.value }))} /></FormField>
          <FormField label="Notas"><Input value={degreeForm.notes} onChange={e => setDegreeForm(f => ({ ...f, notes: e.target.value }))} /></FormField>
          <div className="profile-modal-actions">
            <Button type="button" variant="outline" onClick={() => setShowDegreeModal(false)}>Cancelar</Button>
            <Button type="submit">Guardar</Button>
          </div>
        </form>
      </Modal>

      <Modal open={showPositionModal} onClose={() => setShowPositionModal(false)} title={editingPosition ? 'Editar cargo' : 'Agregar cargo'}>
        <form onSubmit={handleSavePosition} className="profile-modal-form">
          <FormField label="Cargo *">
            <select className="profile-select" required value={positionForm.position_id} onChange={e => setPositionForm(f => ({ ...f, position_id: e.target.value }))}>
              <option value="">Seleccioná un cargo</option>
              {positionCatalog.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
            </select>
          </FormField>
          <FormField label="Taller *">
            <select className="profile-select" required value={positionForm.workshop_id} onChange={e => setPositionForm(f => ({ ...f, workshop_id: e.target.value }))}>
              <option value="">Seleccioná un taller</option>
              {myWorkshops.map(w => <option key={w.id} value={w.id}>Nº{w.number} {w.name}</option>)}
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
        message="¿Eliminar este registro de grado?"
        onConfirm={() => confirmDeleteDegree !== null && handleDeleteDegree(confirmDeleteDegree)}
        onClose={() => setConfirmDeleteDegree(null)}
      />
      <ConfirmDialog
        open={confirmDeletePosition !== null}
        title="Eliminar cargo"
        message="¿Eliminar este registro de cargo?"
        onConfirm={() => confirmDeletePosition !== null && handleDeletePosition(confirmDeletePosition)}
        onClose={() => setConfirmDeletePosition(null)}
      />
    </AppLayout>
  )
}

import { useEffect, useState, type FormEvent } from 'react'
import AppLayout from '@/components/AppLayout'
import Button from '@/components/Button'
import Spinner from '@/components/Spinner'
import Modal from '@/components/Modal'
import FormField from '@/components/FormField'
import Input from '@/components/Input'
import Alert from '@/components/Alert'
import Badge from '@/components/Badge'
import EmptyState from '@/components/EmptyState'
import ConfirmDialog from '@/components/ConfirmDialog'
import Pagination from '@/components/Pagination'
import { useAuth } from '@/context/AuthContext'
import * as api from '@/api/needs'
import { getCategories } from '@/api/services'
import type { Need } from '@/api/needs'
import type { ServiceCategory } from '@/api/services'
import './NeedsPage.css'

const STATUS_LABELS: Record<string, string> = {
  draft: 'Borrador', open: 'Abierta', searching: 'En búsqueda', with_matches: 'Con coincidencias',
  contact_requested: 'Contacto solicitado', linked: 'Vinculada', closed: 'Cerrada', cancelled: 'Cancelada',
  pending_authorization: 'Pendiente de autorización', requires_correction: 'Requiere corrección', rejected: 'Rechazada',
}
const STATUS_VARIANTS: Record<string, 'default'|'success'|'warning'|'error'> = {
  draft: 'default', open: 'default', searching: 'warning', with_matches: 'success', contact_requested: 'warning',
  linked: 'success', closed: 'default', cancelled: 'error',
  pending_authorization: 'warning', requires_correction: 'warning', rejected: 'error',
}
const URGENCY_LABELS: Record<string, string> = { low: 'Urgencia baja', medium: 'Urgencia media', high: 'Urgencia alta' }
const URGENCY_VARIANTS: Record<string, 'default'|'success'|'warning'|'error'> = { low: 'success', medium: 'warning', high: 'error' }
const VISIBILITY_LABELS: Record<string, string> = {
  private: 'Privado', workshop: 'Mi taller', my_workshops: 'Mis talleres',
  talleres_seleccionados: 'Talleres seleccionados', registered: 'Masones registrados', anonymous: 'Búsqueda anónima',
}
const EMPTY_FORM = { title: '', description: '', service_category_id: '', location: '', urgency: '', visibility: 'private', status: 'draft' }

export default function NeedsPage({ embedded = false }: { embedded?: boolean }) {
  const { user } = useAuth()
  const isSuperAdmin = user?.role === 'superadmin'

  const [needs, setNeeds] = useState<Need[]>([])
  const [categories, setCategories] = useState<ServiceCategory[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [statusFilter, setStatusFilter] = useState('')
  const [showModal, setShowModal] = useState(false)
  const [editing, setEditing] = useState<Need | null>(null)
  const [form, setForm] = useState({ ...EMPTY_FORM })
  const [saving, setSaving] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState<number | null>(null)
  const [authModal, setAuthModal] = useState<{ need: Need; action: 'authorize' | 'reject' | 'correction' } | null>(null)
  const [authNotes, setAuthNotes] = useState('')

  async function load() {
    setLoading(true)
    try {
      const [r, cats] = await Promise.all([
        api.getNeeds({ page, status: statusFilter || undefined }),
        categories.length ? Promise.resolve(categories) : getCategories(),
      ])
      setNeeds(r.data); setLastPage(r.last_page); setTotal(r.total)
      if (!categories.length) setCategories(cats)
    } catch { setError('Error cargando necesidades.') }
    finally { setLoading(false) }
  }

  useEffect(() => { load() }, [page, statusFilter])

  function openCreate() { setEditing(null); setForm({ ...EMPTY_FORM }); setShowModal(true) }
  function openEdit(n: Need) {
    setEditing(n)
    setForm({ title: n.title, description: n.description ?? '', service_category_id: n.service_category_id?.toString() ?? '', location: n.location ?? '', urgency: n.urgency ?? '', visibility: n.visibility, status: n.status })
    setShowModal(true)
  }

  async function handleSave(e: FormEvent) {
    e.preventDefault(); setSaving(true)
    try {
      const payload = { ...form, service_category_id: form.service_category_id ? Number(form.service_category_id) : null, urgency: (form.urgency || null) as any }
      if (editing) { const u = await api.updateNeed(editing.id, payload as any); setNeeds(ns => ns.map(n => n.id === u.id ? u : n)) }
      else { const c = await api.createNeed(payload as any); setNeeds(ns => [c, ...ns]); setTotal(t => t + 1) }
      setShowModal(false)
    } catch { alert('Error al guardar.') }
    finally { setSaving(false) }
  }

  async function handleDelete(id: number) {
    await api.deleteNeed(id); setNeeds(ns => ns.filter(n => n.id !== id)); setTotal(t => t - 1); setConfirmDelete(null)
  }

  async function handleAuth() {
    if (!authModal) return; setSaving(true)
    try {
      let updated: Need
      if (authModal.action === 'authorize') updated = await api.authorizeNeed(authModal.need.id, authNotes || undefined)
      else if (authModal.action === 'reject') updated = await api.rejectNeed(authModal.need.id, authNotes || undefined)
      else updated = await api.requestNeedCorrection(authModal.need.id, authNotes)
      setNeeds(ns => ns.map(n => n.id === updated.id ? updated : n))
      setAuthModal(null)
    } catch { alert('Error al procesar la acción.') }
    finally { setSaving(false) }
  }

  function setField(k: string) { return (e: React.ChangeEvent<HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement>) => setForm(f => ({ ...f, [k]: e.target.value })) }

  const authActionLabel = authModal?.action === 'authorize' ? 'Aprobar' : authModal?.action === 'reject' ? 'Rechazar' : 'Pedir corrección'

  const content = (
    <>
      <div className="needs-header">
        <h1 className="needs-title">Mis necesidades <span className="needs-count">({total})</span></h1>
        <Button onClick={openCreate}>+ Agregar necesidad</Button>
      </div>
      <div className="needs-filters">
        <select className="needs-select" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(1) }}>
          <option value="">Todos los estados</option>
          {Object.entries(STATUS_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
        </select>
      </div>
      {error && <Alert>{error}</Alert>}
      {loading ? <div className="needs-loading"><Spinner /></div> : needs.length === 0 ? (
        <EmptyState title="Sin necesidades" description="Registrá lo que estás buscando para que otros hermanos puedan ayudarte." />
      ) : (
        <>
          <div className="needs-list">
            {needs.map(n => (
              <div key={n.id} className="need-card">
                <div className="need-card-header">
                  <div>
                    {isSuperAdmin && <span className="need-user-name">{(n as any).user?.name}</span>}
                    <span className="need-title">{n.title}</span>
                    {n.category && <span className="need-category">{n.category.name}</span>}
                  </div>
                  <div className="need-badges">
                    <Badge variant={STATUS_VARIANTS[n.status]}>{STATUS_LABELS[n.status]}</Badge>
                    {n.urgency && <Badge variant={URGENCY_VARIANTS[n.urgency]}>{URGENCY_LABELS[n.urgency]}</Badge>}
                  </div>
                </div>
                {n.status === 'pending_authorization' && (
                  <Alert variant="warning">Esta necesidad está pendiente de autorización por un Maestro o administrador del taller.</Alert>
                )}
                {n.status === 'requires_correction' && n.authorization_notes && (
                  <Alert variant="warning">Corrección requerida: {n.authorization_notes}</Alert>
                )}
                {n.status === 'rejected' && n.authorization_notes && (
                  <Alert variant="error">Motivo de rechazo: {n.authorization_notes}</Alert>
                )}
                {n.description && <p className="need-desc">{n.description}</p>}
                <div className="need-meta">
                  {n.location && <span>{n.location} · </span>}
                  <span>{VISIBILITY_LABELS[n.visibility]}</span>
                </div>
                <div className="need-actions">
                  <button className="needs-link-btn" onClick={() => openEdit(n)}>Editar</button>
                  <button className="needs-link-btn needs-link-danger" onClick={() => setConfirmDelete(n.id)}>Eliminar</button>
                  {isSuperAdmin && n.status === 'pending_authorization' && (
                    <>
                      <button className="needs-link-btn needs-link-success" onClick={() => { setAuthModal({ need: n, action: 'authorize' }); setAuthNotes('') }}>Aprobar</button>
                      <button className="needs-link-btn" onClick={() => { setAuthModal({ need: n, action: 'correction' }); setAuthNotes('') }}>Pedir corrección</button>
                      <button className="needs-link-btn needs-link-danger" onClick={() => { setAuthModal({ need: n, action: 'reject' }); setAuthNotes('') }}>Rechazar</button>
                    </>
                  )}
                </div>
              </div>
            ))}
          </div>
          <Pagination currentPage={page} lastPage={lastPage} total={total} onPageChange={setPage} />
        </>
      )}

      <Modal open={showModal} onClose={() => setShowModal(false)} title={editing ? 'Editar necesidad' : 'Nueva necesidad'}>
        <form onSubmit={handleSave} className="needs-form">
          <FormField label="Título *"><Input required value={form.title} onChange={setField('title')} /></FormField>
          <FormField label="Descripción"><textarea className="needs-textarea" value={form.description} onChange={setField('description')} rows={3} /></FormField>
          <FormField label="Categoría">
            <select className="needs-select" value={form.service_category_id} onChange={setField('service_category_id')}>
              <option value="">Sin categoría</option>
              {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
          </FormField>
          <FormField label="Zona / Ubicación"><Input value={form.location} onChange={setField('location')} /></FormField>
          <FormField label="Urgencia">
            <select className="needs-select" value={form.urgency} onChange={setField('urgency')}>
              <option value="">Sin especificar</option>
              <option value="low">Baja</option>
              <option value="medium">Media</option>
              <option value="high">Alta</option>
            </select>
          </FormField>
          <FormField label="Visibilidad">
            <select className="needs-select" value={form.visibility} onChange={setField('visibility')}>
              {Object.entries(VISIBILITY_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </select>
          </FormField>
          <FormField label="Estado">
            <select className="needs-select" value={form.status} onChange={setField('status')}>
              {Object.entries(STATUS_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </select>
          </FormField>
          <div className="needs-modal-actions">
            <Button type="button" variant="outline" onClick={() => setShowModal(false)}>Cancelar</Button>
            <Button type="submit" loading={saving}>Guardar</Button>
          </div>
        </form>
      </Modal>

      <Modal open={authModal !== null} onClose={() => setAuthModal(null)} title={authActionLabel}>
        {authModal && (
          <div className="needs-form">
            <p><strong>Necesidad:</strong> {authModal.need.title}</p>
            <FormField label={authModal.action === 'correction' ? 'Correcciones requeridas *' : 'Nota (opcional)'}>
              <textarea className="needs-textarea" value={authNotes} onChange={e => setAuthNotes(e.target.value)} rows={3} />
            </FormField>
            <div className="needs-modal-actions">
              <Button type="button" variant="outline" onClick={() => setAuthModal(null)}>Cancelar</Button>
              <Button type="button" loading={saving} onClick={handleAuth}
                disabled={authModal.action === 'correction' && !authNotes.trim()}>
                {authActionLabel}
              </Button>
            </div>
          </div>
        )}
      </Modal>

      <ConfirmDialog open={confirmDelete !== null} title="Eliminar necesidad" message="¿Eliminar esta necesidad?" onConfirm={() => confirmDelete !== null && handleDelete(confirmDelete)} onClose={() => setConfirmDelete(null)} />
    </>
  )
  if (embedded) return content
  return <AppLayout>{content}</AppLayout>
}

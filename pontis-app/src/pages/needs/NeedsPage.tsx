import { useEffect, useState } from 'react'
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
import PrivacyIndicator from '@/components/PrivacyIndicator'
import { useAuth } from '@/context/AuthContext'
import * as api from '@/api/needs'
import { getCategories } from '@/api/services'
import { getPublicationPreview, type PublicationPreview } from '@/api/profile'
import type { Need, NeedInput } from '@/api/needs'
import type { ServiceCategory } from '@/api/services'
import './NeedsPage.css'

const STATUS_LABELS: Record<string, string> = {
  draft: 'Borrador', active: 'Activa', suspended: 'Suspendida', expired: 'Vencida',
}
const STATUS_VARIANTS: Record<string, 'default'|'success'|'warning'|'error'> = {
  draft: 'default', active: 'success', suspended: 'warning', expired: 'error',
}
const URGENCY_LABELS: Record<string, string> = { low: 'Urgencia baja', medium: 'Urgencia media', high: 'Urgencia alta' }
const URGENCY_VARIANTS: Record<string, 'default'|'success'|'warning'|'error'> = { low: 'success', medium: 'warning', high: 'error' }
const VISIBILITY_LABELS: Record<string, string> = {
  private: 'Privado', workshop: 'Mi taller principal', my_workshops: 'Mis talleres', registered: 'Masones registrados', anonymous: 'Búsqueda anónima',
}
const VALIDITY_OPTIONS = [10, 30, 60, 90]
const EMPTY_FORM = { title: '', description: '', service_category_id: '', location: '', urgency: '', visibility: 'private', validity_days: 30 }

function formatDate(value: string | null): string {
  if (!value) return ''
  return new Date(value).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

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
  const [perPage, setPerPage] = useState(10)
  const [statusFilter, setStatusFilter] = useState('')
  const [showModal, setShowModal] = useState(false)
  const [editing, setEditing] = useState<Need | null>(null)
  const [republishing, setRepublishing] = useState(false)
  const [form, setForm] = useState({ ...EMPTY_FORM })
  const [saving, setSaving] = useState(false)
  const [previewLoading, setPreviewLoading] = useState(false)
  const [preview, setPreview] = useState<PublicationPreview | null>(null)
  const [confirmDelete, setConfirmDelete] = useState<number | null>(null)

  async function load() {
    setLoading(true)
    try {
      const [r, cats] = await Promise.all([
        api.getNeeds({ page, per_page: perPage, status: statusFilter || undefined }),
        categories.length ? Promise.resolve(categories) : getCategories(),
      ])
      setNeeds(r.data); setLastPage(r.last_page); setTotal(r.total)
      if (!categories.length) setCategories(cats)
    } catch { setError('Error cargando necesidades.') }
    finally { setLoading(false) }
  }

  useEffect(() => { load() }, [page, perPage, statusFilter])

  function openCreate() { setEditing(null); setRepublishing(false); setForm({ ...EMPTY_FORM }); setShowModal(true) }
  function openEdit(n: Need, republish = false) {
    setEditing(n); setRepublishing(republish)
    setForm({ title: n.title, description: n.description ?? '', service_category_id: n.service_category_id?.toString() ?? '', location: n.location ?? '', urgency: n.urgency ?? '', visibility: n.visibility, validity_days: 30 })
    setShowModal(true)
  }

  async function handleSubmit(publish: boolean, previewConfirmed = false) {
    setSaving(true)
    try {
      const payload: NeedInput = {
        title: form.title, description: form.description,
        service_category_id: form.service_category_id ? Number(form.service_category_id) : null,
        location: form.location, urgency: form.urgency || null, visibility: form.visibility,
        publish, validity_days: publish ? Number(form.validity_days) : null,
        preview_confirmed: publish ? previewConfirmed : undefined,
      }
      if (editing) { const u = await api.updateNeed(editing.id, payload); setNeeds(ns => ns.map(n => n.id === u.id ? u : n)) }
      else { const c = await api.createNeed(payload); setNeeds(ns => [c, ...ns]); setTotal(t => t + 1) }
      setPreview(null); setShowModal(false)
    } catch { alert('Error al guardar.') }
    finally { setSaving(false) }
  }

  async function handleSuspend(n: Need) {
    try { const u = await api.suspendNeed(n.id); setNeeds(ns => ns.map(x => x.id === u.id ? u : x)) }
    catch { alert('Error al suspender.') }
  }

  async function handleDelete(id: number) {
    await api.deleteNeed(id); setNeeds(ns => ns.filter(n => n.id !== id)); setTotal(t => t - 1); setConfirmDelete(null)
  }

  function setField(k: string) { return (e: React.ChangeEvent<HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement>) => setForm(f => ({ ...f, [k]: e.target.value })) }

  function guardarClick(e: React.MouseEvent<HTMLButtonElement>) {
    const formEl = e.currentTarget.closest('form')
    if (formEl && !formEl.reportValidity()) return
    handleSubmit(false)
  }

  async function openPublishPreview() {
    setPreviewLoading(true)
    try {
      setPreview(await getPublicationPreview(form.visibility))
    } catch {
      alert('No se pudo generar la vista previa.')
    } finally {
      setPreviewLoading(false)
    }
  }

  const content = (
    <>
      <div className="needs-header">
        <h1 className="needs-title">Lo que necesito <span className="needs-count">({total})</span></h1>
        <Button onClick={openCreate}>+ Agregar necesidad</Button>
      </div>
      <div className="needs-filters">
        <select className="needs-select needs-select-compact" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(1) }}>
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
                    <Badge variant={STATUS_VARIANTS[n.effective_status]}>{STATUS_LABELS[n.effective_status]}</Badge>
                    {n.urgency && <Badge variant={URGENCY_VARIANTS[n.urgency]}>{URGENCY_LABELS[n.urgency]}</Badge>}
                  </div>
                </div>
                {n.effective_status === 'expired' && (
                  <Alert variant="warning">Esta publicación venció el {formatDate(n.expires_at)}. Republicá para volver a mostrarla.</Alert>
                )}
                {n.description && <p className="need-desc">{n.description}</p>}
                <div className="need-meta">
                  {n.location && <span>{n.location} · </span>}
                  <span>{VISIBILITY_LABELS[n.visibility]}</span>
                  {n.effective_status === 'active' && n.expires_at && <span> · Vence el {formatDate(n.expires_at)}</span>}
                </div>
                <div className="need-actions">
                  <button className="needs-link-btn" onClick={() => openEdit(n)}>Editar</button>
                  {n.effective_status === 'active' && (
                    <button className="needs-link-btn" onClick={() => handleSuspend(n)}>Suspender</button>
                  )}
                  {n.effective_status === 'expired' && (
                    <button className="needs-link-btn needs-link-success" onClick={() => openEdit(n, true)}>Republicar</button>
                  )}
                  <button className="needs-link-btn needs-link-danger" onClick={() => setConfirmDelete(n.id)}>Eliminar</button>
                </div>
              </div>
            ))}
          </div>
          <Pagination
            currentPage={page}
            lastPage={lastPage}
            total={total}
            onPageChange={setPage}
            perPage={perPage}
            onPerPageChange={n => { setPerPage(n); setPage(1) }}
          />
        </>
      )}

      <Modal open={showModal} onClose={() => setShowModal(false)} title={republishing ? 'Republicar necesidad' : editing ? 'Editar necesidad' : 'Nueva necesidad'}>
        <form onSubmit={e => { e.preventDefault(); openPublishPreview() }} className="needs-form">
          <FormField label="Título *"><Input required value={form.title} onChange={setField('title')} /></FormField>
          <FormField label="Descripción *"><textarea className="needs-textarea" required value={form.description} onChange={setField('description')} rows={3} /></FormField>
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
          <FormField label="Vigencia de la publicación">
            <select className="needs-select" value={form.validity_days} onChange={setField('validity_days')}>
              {VALIDITY_OPTIONS.map(d => <option key={d} value={d}>{d} días</option>)}
            </select>
          </FormField>
          <div className="needs-modal-actions">
            <Button type="button" variant="ghost" onClick={() => setShowModal(false)}>Cancelar</Button>
            <Button type="button" variant="outline" loading={saving} onClick={guardarClick}>Guardar</Button>
            <Button type="submit" loading={previewLoading}>Publicar</Button>
          </div>
        </form>
      </Modal>

      <Modal open={preview !== null} onClose={() => setPreview(null)} title="Vista previa de publicación">
        {preview && (
          <div className="needs-preview">
            <div className="needs-preview-card">
              <div>
                <span className="need-title">{form.title}</span>
                {form.service_category_id && <span className="need-category">{categories.find(c => c.id === Number(form.service_category_id))?.name}</span>}
              </div>
              {form.description && <p className="need-desc">{form.description}</p>}
              <div className="need-meta">
                {form.location && <span>{form.location} · </span>}
                <span>{VISIBILITY_LABELS[form.visibility]}</span>
                {form.urgency && <span> · {URGENCY_LABELS[form.urgency]}</span>}
                <span> · Válida por {form.validity_days} días</span>
              </div>
              <div className="needs-preview-author">
                <strong>{preview.author.last_name ? `${preview.author.last_name}, ${preview.author.name}` : preview.author.name}</strong>
                {preview.author.profession && <span> · {preview.author.profession}</span>}
                {(preview.author.locality || preview.author.province) && <span> · {[preview.author.locality, preview.author.province].filter(Boolean).join(', ')}</span>}
              </div>
            </div>
            <p className="needs-preview-note">Audiencia: {preview.audience}</p>
            <PrivacyIndicator
              summary={preview.anonymous ? 'Tu identidad quedará reservada en esta publicación.' : 'Tu identidad será visible para la audiencia seleccionada.'}
              details={[
                `Audiencia: ${preview.audience}`,
                `Autor visible: ${preview.author.last_name ? `${preview.author.last_name}, ${preview.author.name}` : preview.author.name}`,
                'Esta acción quedará asociada a tu cuenta para controles internos.',
              ]}
            />
            <div className="needs-modal-actions">
              <Button type="button" variant="outline" onClick={() => setPreview(null)}>Volver a editar</Button>
              <Button type="button" loading={saving} onClick={() => handleSubmit(true, true)}>Confirmar y publicar</Button>
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

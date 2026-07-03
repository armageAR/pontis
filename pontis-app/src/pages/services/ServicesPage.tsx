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
import * as api from '@/api/services'
import type { Service, ServiceCategory, ServiceInput } from '@/api/services'
import { getPublicationPreview, type PublicationPreview } from '@/api/profile'
import './ServicesPage.css'

const STATUS_LABELS: Record<string, string> = {
  draft: 'Borrador', active: 'Activa', suspended: 'Suspendida', expired: 'Vencida',
}
const STATUS_VARIANTS: Record<string, 'default'|'success'|'warning'|'error'> = {
  draft: 'default', active: 'success', suspended: 'warning', expired: 'error',
}
const MODALITY_LABELS: Record<string, string> = { presencial: 'Presencial', remoto: 'Remoto', both: 'Ambas' }
const VISIBILITY_LABELS: Record<string, string> = {
  private: 'Privado', workshop: 'Mi taller principal', my_workshops: 'Mis talleres', registered: 'Masones registrados', anonymous: 'Búsqueda anónima',
}
const VALIDITY_OPTIONS = [10, 30, 60, 90]
const EMPTY_FORM = { title: '', description: '', service_category_id: '', modality: 'both', location: '', availability: '', conditions: '', visibility: 'private', validity_days: 30 }

function formatDate(value: string | null): string {
  if (!value) return ''
  return new Date(value).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

export default function ServicesPage({ embedded = false }: { embedded?: boolean }) {
  const { user } = useAuth()
  const isSuperAdmin = user?.role === 'superadmin'

  const [services, setServices] = useState<Service[]>([])
  const [categories, setCategories] = useState<ServiceCategory[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [perPage, setPerPage] = useState(10)
  const [statusFilter, setStatusFilter] = useState('')
  const [showModal, setShowModal] = useState(false)
  const [editing, setEditing] = useState<Service | null>(null)
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
        api.getServices({ page, per_page: perPage, status: statusFilter || undefined }),
        categories.length ? Promise.resolve(categories) : api.getCategories(),
      ])
      setServices(r.data); setLastPage(r.last_page); setTotal(r.total)
      if (!categories.length) setCategories(cats)
    } catch { setError('Error cargando servicios.') }
    finally { setLoading(false) }
  }

  useEffect(() => { load() }, [page, perPage, statusFilter])

  function openCreate() { setEditing(null); setRepublishing(false); setForm({ ...EMPTY_FORM }); setShowModal(true) }
  function openEdit(s: Service, republish = false) {
    setEditing(s); setRepublishing(republish)
    setForm({ title: s.title, description: s.description ?? '', service_category_id: s.service_category_id?.toString() ?? '', modality: s.modality, location: s.location ?? '', availability: s.availability ?? '', conditions: s.conditions ?? '', visibility: s.visibility, validity_days: 30 })
    setShowModal(true)
  }

  async function handleSubmit(publish: boolean, previewConfirmed = false) {
    setSaving(true)
    try {
      const payload: ServiceInput = {
        title: form.title, description: form.description,
        service_category_id: form.service_category_id ? Number(form.service_category_id) : null,
        modality: form.modality, location: form.location, availability: form.availability,
        conditions: form.conditions, visibility: form.visibility,
        publish, validity_days: publish ? Number(form.validity_days) : null,
        preview_confirmed: publish ? previewConfirmed : undefined,
      }
      if (editing) { const u = await api.updateService(editing.id, payload); setServices(ss => ss.map(s => s.id === u.id ? u : s)) }
      else { const c = await api.createService(payload); setServices(ss => [c, ...ss]); setTotal(t => t + 1) }
      setPreview(null); setShowModal(false)
    } catch { alert('Error al guardar.') }
    finally { setSaving(false) }
  }

  async function handleSuspend(s: Service) {
    try { const u = await api.suspendService(s.id); setServices(ss => ss.map(x => x.id === u.id ? u : x)) }
    catch { alert('Error al suspender.') }
  }

  async function handleDelete(id: number) {
    await api.deleteService(id); setServices(ss => ss.filter(s => s.id !== id)); setTotal(t => t - 1); setConfirmDelete(null)
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
      <div className="services-header">
        <h1 className="services-title">Lo que ofrezco <span className="services-count">({total})</span></h1>
        <Button onClick={openCreate}>+ Agregar servicio</Button>
      </div>
      <div className="services-filters">
        <select className="services-select services-select-compact" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(1) }}>
          <option value="">Todos los estados</option>
          {Object.entries(STATUS_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
        </select>
      </div>
      {error && <Alert>{error}</Alert>}
      {loading ? <div className="services-loading"><Spinner /></div> : services.length === 0 ? (
        <EmptyState title="Sin servicios" description="Agregá un servicio para empezar a compartir lo que podés ofrecer." />
      ) : (
        <>
          <div className="services-list">
            {services.map(s => (
              <div key={s.id} className="service-card">
                <div className="service-card-header">
                  <div>
                    {isSuperAdmin && <span className="service-user-name">{(s as any).user?.name}</span>}
                    <span className="service-title">{s.title}</span>
                    {s.category && <span className="service-category">{s.category.name}</span>}
                  </div>
                  <Badge variant={STATUS_VARIANTS[s.effective_status]}>{STATUS_LABELS[s.effective_status]}</Badge>
                </div>
                {s.effective_status === 'expired' && (
                  <Alert variant="warning">Esta publicación venció el {formatDate(s.expires_at)}. Republicá para volver a mostrarla.</Alert>
                )}
                {s.description && <p className="service-desc">{s.description}</p>}
                <div className="service-meta">
                  <span>{MODALITY_LABELS[s.modality]}</span>
                  {s.location && <span>· {s.location}</span>}
                  <span>· {VISIBILITY_LABELS[s.visibility]}</span>
                  {s.effective_status === 'active' && s.expires_at && <span>· Vence el {formatDate(s.expires_at)}</span>}
                </div>
                <div className="service-actions">
                  <button className="services-link-btn" onClick={() => openEdit(s)}>Editar</button>
                  {s.effective_status === 'active' && (
                    <button className="services-link-btn" onClick={() => handleSuspend(s)}>Suspender</button>
                  )}
                  {s.effective_status === 'expired' && (
                    <button className="services-link-btn services-link-success" onClick={() => openEdit(s, true)}>Republicar</button>
                  )}
                  <button className="services-link-btn services-link-danger" onClick={() => setConfirmDelete(s.id)}>Eliminar</button>
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

      <Modal open={showModal} onClose={() => setShowModal(false)} title={republishing ? 'Republicar servicio' : editing ? 'Editar servicio' : 'Nuevo servicio'}>
        <form onSubmit={e => { e.preventDefault(); openPublishPreview() }} className="services-form">
          <FormField label="Título *"><Input required value={form.title} onChange={setField('title')} /></FormField>
          <FormField label="Descripción *"><textarea className="services-textarea" required value={form.description} onChange={setField('description')} rows={3} /></FormField>
          <FormField label="Categoría">
            <select className="services-select" value={form.service_category_id} onChange={setField('service_category_id')}>
              <option value="">Sin categoría</option>
              {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
          </FormField>
          <FormField label="Modalidad">
            <select className="services-select" value={form.modality} onChange={setField('modality')}>
              {Object.entries(MODALITY_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </select>
          </FormField>
          <FormField label="Zona / Ubicación"><Input value={form.location} onChange={setField('location')} /></FormField>
          <FormField label="Disponibilidad"><Input value={form.availability} onChange={setField('availability')} placeholder="Ej: lunes y miércoles" /></FormField>
          <FormField label="Condiciones"><Input value={form.conditions} onChange={setField('conditions')} /></FormField>
          <FormField label="Visibilidad">
            <select className="services-select" value={form.visibility} onChange={setField('visibility')}>
              {Object.entries(VISIBILITY_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </select>
          </FormField>
          <FormField label="Vigencia de la publicación">
            <select className="services-select" value={form.validity_days} onChange={setField('validity_days')}>
              {VALIDITY_OPTIONS.map(d => <option key={d} value={d}>{d} días</option>)}
            </select>
          </FormField>
          <div className="services-modal-actions">
            <Button type="button" variant="ghost" onClick={() => setShowModal(false)}>Cancelar</Button>
            <Button type="button" variant="outline" loading={saving} onClick={guardarClick}>Guardar</Button>
            <Button type="submit" loading={previewLoading}>Publicar</Button>
          </div>
        </form>
      </Modal>

      <Modal open={preview !== null} onClose={() => setPreview(null)} title="Vista previa de publicación">
        {preview && (
          <div className="services-preview">
            <div className="services-preview-card">
              <div>
                <span className="service-title">{form.title}</span>
                {form.service_category_id && <span className="service-category">{categories.find(c => c.id === Number(form.service_category_id))?.name}</span>}
              </div>
              {form.description && <p className="service-desc">{form.description}</p>}
              <div className="service-meta">
                <span>{MODALITY_LABELS[form.modality]}</span>
                {form.location && <span>· {form.location}</span>}
                <span>· {VISIBILITY_LABELS[form.visibility]}</span>
                <span>· Válida por {form.validity_days} días</span>
              </div>
              <div className="services-preview-author">
                <strong>{preview.author.last_name ? `${preview.author.last_name}, ${preview.author.name}` : preview.author.name}</strong>
                {preview.author.profession && <span> · {preview.author.profession}</span>}
                {(preview.author.locality || preview.author.province) && <span> · {[preview.author.locality, preview.author.province].filter(Boolean).join(', ')}</span>}
              </div>
            </div>
            <p className="services-preview-note">Audiencia: {preview.audience}</p>
            <PrivacyIndicator
              summary={preview.anonymous ? 'Tu identidad quedará reservada en esta publicación.' : 'Tu identidad será visible para la audiencia seleccionada.'}
              details={[
                `Audiencia: ${preview.audience}`,
                `Autor visible: ${preview.author.last_name ? `${preview.author.last_name}, ${preview.author.name}` : preview.author.name}`,
                'Esta acción quedará asociada a tu cuenta para controles internos.',
              ]}
            />
            <div className="services-modal-actions">
              <Button type="button" variant="outline" onClick={() => setPreview(null)}>Volver a editar</Button>
              <Button type="button" loading={saving} onClick={() => handleSubmit(true, true)}>Confirmar y publicar</Button>
            </div>
          </div>
        )}
      </Modal>

      <ConfirmDialog open={confirmDelete !== null} title="Eliminar servicio" message="¿Eliminar este servicio?" onConfirm={() => confirmDelete !== null && handleDelete(confirmDelete)} onClose={() => setConfirmDelete(null)} />
    </>
  )
  if (embedded) return content
  return <AppLayout>{content}</AppLayout>
}

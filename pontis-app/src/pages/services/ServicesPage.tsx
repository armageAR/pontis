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
import * as api from '@/api/services'
import type { Service, ServiceCategory } from '@/api/services'
import './ServicesPage.css'

const STATUS_LABELS: Record<string, string> = { draft: 'Borrador', active: 'Activo', paused: 'Pausado', hidden: 'Oculto', disabled: 'Dado de baja' }
const STATUS_VARIANTS: Record<string, 'default'|'success'|'warning'|'error'> = { draft: 'default', active: 'success', paused: 'warning', hidden: 'default', disabled: 'error' }
const MODALITY_LABELS: Record<string, string> = { presencial: 'Presencial', remoto: 'Remoto', both: 'Ambas' }
const VISIBILITY_LABELS: Record<string, string> = { private: 'Privado', workshop: 'Mi taller', my_workshops: 'Mis talleres', registered: 'Masones registrados', anonymous: 'Búsqueda anónima' }
const EMPTY_FORM = { title: '', description: '', service_category_id: '', modality: 'both', location: '', availability: '', conditions: '', visibility: 'private', status: 'draft' }

export default function ServicesPage() {
  const [services, setServices] = useState<Service[]>([])
  const [categories, setCategories] = useState<ServiceCategory[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [statusFilter, setStatusFilter] = useState('')
  const [showModal, setShowModal] = useState(false)
  const [editing, setEditing] = useState<Service | null>(null)
  const [form, setForm] = useState({ ...EMPTY_FORM })
  const [saving, setSaving] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState<number | null>(null)

  async function load() {
    setLoading(true)
    try {
      const [r, cats] = await Promise.all([
        api.getServices({ page, status: statusFilter || undefined }),
        categories.length ? Promise.resolve(categories) : api.getCategories(),
      ])
      setServices(r.data); setLastPage(r.last_page); setTotal(r.total)
      if (!categories.length) setCategories(cats)
    } catch { setError('Error cargando servicios.') }
    finally { setLoading(false) }
  }

  useEffect(() => { load() }, [page, statusFilter])

  function openCreate() { setEditing(null); setForm({ ...EMPTY_FORM }); setShowModal(true) }
  function openEdit(s: Service) {
    setEditing(s)
    setForm({ title: s.title, description: s.description ?? '', service_category_id: s.service_category_id?.toString() ?? '', modality: s.modality, location: s.location ?? '', availability: s.availability ?? '', conditions: s.conditions ?? '', visibility: s.visibility, status: s.status })
    setShowModal(true)
  }

  async function handleSave(e: FormEvent) {
    e.preventDefault(); setSaving(true)
    try {
      const payload = { ...form, service_category_id: form.service_category_id ? Number(form.service_category_id) : null }
      if (editing) { const u = await api.updateService(editing.id, payload as any); setServices(ss => ss.map(s => s.id === u.id ? u : s)) }
      else { const c = await api.createService(payload as any); setServices(ss => [c, ...ss]); setTotal(t => t + 1) }
      setShowModal(false)
    } catch { alert('Error al guardar.') }
    finally { setSaving(false) }
  }

  async function handleDelete(id: number) {
    await api.deleteService(id); setServices(ss => ss.filter(s => s.id !== id)); setTotal(t => t - 1); setConfirmDelete(null)
  }

  function setField(k: string) { return (e: React.ChangeEvent<HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement>) => setForm(f => ({ ...f, [k]: e.target.value })) }

  return (
    <AppLayout>
      <div className="services-header">
        <h1 className="services-title">Mis servicios <span className="services-count">({total})</span></h1>
        <Button onClick={openCreate}>+ Agregar servicio</Button>
      </div>
      <div className="services-filters">
        <select className="services-select" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(1) }}>
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
                    <span className="service-title">{s.title}</span>
                    {s.category && <span className="service-category">{s.category.name}</span>}
                  </div>
                  <Badge variant={STATUS_VARIANTS[s.status]}>{STATUS_LABELS[s.status]}</Badge>
                </div>
                {s.description && <p className="service-desc">{s.description}</p>}
                <div className="service-meta">
                  <span>{MODALITY_LABELS[s.modality]}</span>
                  {s.location && <span>· {s.location}</span>}
                  <span>· {VISIBILITY_LABELS[s.visibility]}</span>
                </div>
                <div className="service-actions">
                  <button className="services-link-btn" onClick={() => openEdit(s)}>Editar</button>
                  <button className="services-link-btn services-link-danger" onClick={() => setConfirmDelete(s.id)}>Eliminar</button>
                </div>
              </div>
            ))}
          </div>
          <Pagination currentPage={page} lastPage={lastPage} total={total} onPageChange={setPage} />
        </>
      )}

      <Modal open={showModal} onClose={() => setShowModal(false)} title={editing ? 'Editar servicio' : 'Nuevo servicio'}>
        <form onSubmit={handleSave} className="services-form">
          <FormField label="Título *"><Input required value={form.title} onChange={setField('title')} /></FormField>
          <FormField label="Descripción"><textarea className="services-textarea" value={form.description} onChange={setField('description')} rows={3} /></FormField>
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
          <FormField label="Estado">
            <select className="services-select" value={form.status} onChange={setField('status')}>
              {Object.entries(STATUS_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </select>
          </FormField>
          <div className="services-modal-actions">
            <Button type="button" variant="outline" onClick={() => setShowModal(false)}>Cancelar</Button>
            <Button type="submit" loading={saving}>Guardar</Button>
          </div>
        </form>
      </Modal>

      <ConfirmDialog open={confirmDelete !== null} title="Eliminar servicio" message="¿Eliminar este servicio?" onConfirm={() => confirmDelete !== null && handleDelete(confirmDelete)} onClose={() => setConfirmDelete(null)} />
    </AppLayout>
  )
}

import { useState, useEffect, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import AppLayout from '@/components/AppLayout'
import Button from '@/components/Button'
import Input from '@/components/Input'
import Badge from '@/components/Badge'
import Spinner from '@/components/Spinner'
import Alert from '@/components/Alert'
import EmptyState from '@/components/EmptyState'
import Pagination from '@/components/Pagination'
import Modal from '@/components/Modal'
import FormField from '@/components/FormField'
import PrivacyIndicator from '@/components/PrivacyIndicator'
import * as exploreApi from '@/api/explore'
import type { ExploreService, ExploreNeed } from '@/api/explore'
import { getCategories } from '@/api/services'
import type { ServiceCategory } from '@/api/services'
import { createContactRequest, type SharedContactField } from '@/api/contactRequests'
import { getContactConsent } from '@/api/profile'
import { PROVINCIAS } from '@/constants/provincias'
import './ExplorePage.css'

const SCOPE_LABELS: Record<string, string> = {
  my_workshop: 'Mi taller principal',
  my_workshops: 'Mis talleres',
  all: 'Todo el sistema',
}

const MODALITY_LABELS: Record<string, string> = { presencial: 'Presencial', remoto: 'Remoto', both: 'Ambas' }
const SHARED_FIELD_LABELS: Record<SharedContactField, string> = {
  identity: 'Identidad',
  email: 'Email',
  phone: 'Teléfono',
  whatsapp: 'WhatsApp',
  workshop: 'Taller',
  profession: 'Profesión u oficio',
}

type Tab = 'services' | 'needs'
type ContactTarget = { type: 'service'; item: ExploreService } | { type: 'need'; item: ExploreNeed }

export default function ExplorePage({ embedded = false }: { embedded?: boolean }) {
  const [tab, setTab] = useState<Tab>('services')
  const [q, setQ] = useState('')
  const [categoryId, setCategoryId] = useState('')
  const [scope, setScope] = useState<'my_workshop'|'my_workshops'|'all'>('all')
  const [province, setProvince] = useState('')
  const [city, setCity] = useState('')
  const [categories, setCategories] = useState<ServiceCategory[]>([])
  const [services, setServices] = useState<ExploreService[]>([])
  const [needs, setNeeds] = useState<ExploreNeed[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [searched, setSearched] = useState(false)
  const [contactTarget, setContactTarget] = useState<ContactTarget | null>(null)
  const [contactMessage, setContactMessage] = useState('')
  const [sharedFields, setSharedFields] = useState<SharedContactField[]>(['identity'])
  const [sendingContact, setSendingContact] = useState(false)

  useEffect(() => {
    getCategories().then(setCategories).catch(() => {})
    getContactConsent().then(settings => setSharedFields(settings.default_shared_fields)).catch(() => {})
    // Load default results on mount
    handleSearch()
  }, [])

  async function handleSearch(e?: FormEvent) {
    e?.preventDefault()
    setLoading(true); setError(''); setPage(1)
    const filters = {
      q: q || undefined,
      category_id: categoryId ? Number(categoryId) : undefined,
      scope,
      province: province || undefined,
      locality: city || undefined,
      page: 1,
    }
    try {
      if (tab === 'services') {
        const r = await exploreApi.exploreServices(filters)
        setServices(r.data); setLastPage(r.last_page); setTotal(r.total)
      } else {
        const r = await exploreApi.exploreNeeds(filters)
        setNeeds(r.data); setLastPage(r.last_page); setTotal(r.total)
      }
      setSearched(true)
    } catch { setError('Error al buscar.') }
    finally { setLoading(false) }
  }

  async function handlePageChange(p: number) {
    setPage(p); setLoading(true)
    const filters = { q: q || undefined, category_id: categoryId ? Number(categoryId) : undefined, scope, province: province || undefined, locality: city || undefined, page: p }
    try {
      if (tab === 'services') {
        const r = await exploreApi.exploreServices(filters)
        setServices(r.data); setLastPage(r.last_page); setTotal(r.total)
      } else {
        const r = await exploreApi.exploreNeeds(filters)
        setNeeds(r.data); setLastPage(r.last_page); setTotal(r.total)
      }
    } catch { setError('Error al cargar página.') }
    finally { setLoading(false) }
  }

  function switchTab(t: Tab) {
    setTab(t); setServices([]); setNeeds([]); setTotal(0); setSearched(false)
  }

  function openContact(target: ContactTarget) {
    setContactTarget(target)
    setContactMessage('')
  }

  function toggleSharedField(field: SharedContactField) {
    setSharedFields(fields => fields.includes(field) ? fields.filter(f => f !== field) : [...fields, field])
  }

  async function sendContactRequest() {
    if (!contactTarget) return
    if (contactMessage.trim().length < 10) { setError('El mensaje de contacto debe tener al menos 10 caracteres.'); return }
    setSendingContact(true); setError('')
    try {
      await createContactRequest({
        service_id: contactTarget.type === 'service' ? contactTarget.item.id : undefined,
        need_id: contactTarget.type === 'need' ? contactTarget.item.id : undefined,
        message: contactMessage,
        shared_fields: sharedFields,
        reason_type: contactTarget.type === 'service' ? 'offer_publication' : 'need_publication',
        source: 'publications',
        source_context: {
          title: contactTarget.item.title,
          category: contactTarget.item.category?.name ?? null,
        },
      })
      setContactTarget(null)
      setContactMessage('')
    } catch {
      setError('No se pudo enviar la solicitud de contacto.')
    } finally {
      setSendingContact(false)
    }
  }

  const content = (
    <>
      <h1 className="explore-title">Buscar servicios y necesidades</h1>
      <p className="explore-subtitle">
        Encontrá hermanos que puedan ayudarte. Solo verás lo que cada hermano decidió compartir.
      </p>

      <div className="explore-tabs">
        <button className={`explore-tab ${tab === 'services' ? 'explore-tab-active' : ''}`} onClick={() => switchTab('services')}>Servicios ofrecidos</button>
        <button className={`explore-tab ${tab === 'needs' ? 'explore-tab-active' : ''}`} onClick={() => switchTab('needs')}>Necesidades abiertas</button>
      </div>

      <form onSubmit={handleSearch} className="explore-filters">
        <div className="explore-filters-row">
          <Input placeholder="¿Qué buscás? Ej: contador, electricista..." value={q} onChange={e => setQ(e.target.value)} className="explore-search-input" />
          <select className="explore-select" value={categoryId} onChange={e => setCategoryId(e.target.value)}>
            <option value="">Todas las categorías</option>
            {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
          </select>
          <select className="explore-select" value={scope} onChange={e => setScope(e.target.value as any)}>
            {Object.entries(SCOPE_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
          </select>
          <select className="explore-select" value={province} onChange={e => setProvince(e.target.value)}>
            <option value="">Todas las provincias</option>
            {PROVINCIAS.map(p => <option key={p} value={p}>{p}</option>)}
          </select>
          <Input placeholder="Ciudad" value={city} onChange={e => setCity(e.target.value)} className="explore-city-input" />
          <Button type="submit">Buscar</Button>
        </div>
      </form>

      {error && <Alert>{error}</Alert>}

      {loading ? (
        <div className="explore-loading"><Spinner /></div>
      ) : !searched ? null : total === 0 ? (
        <EmptyState title="Sin resultados" description="No se encontraron resultados visibles para vos con esos criterios." />
      ) : (
        <>
          <p className="explore-results-count">{total} resultado{total !== 1 ? 's' : ''}</p>

          {tab === 'services' && (
            <div className="explore-list">
              {services.map(s => (
                <div key={s.id} className="explore-card">
                  <div className="explore-card-header">
                    <div>
                      <span className="explore-card-title">{s.title}</span>
                      {s.category && <span className="explore-card-category">{s.category.name}</span>}
                    </div>
                    {s.modality && <Badge variant="default">{MODALITY_LABELS[s.modality] ?? s.modality}</Badge>}
                  </div>
                  {s.description && <p className="explore-card-desc">{s.description}</p>}
                  <div className="explore-card-meta">
                    {s.location && <span>{s.location}</span>}
                    {s.availability && <span>· {s.availability}</span>}
                  </div>
                  <div className="explore-card-footer">
                    <div className="explore-author">
                      {s.user?.anonymous ? (
                        <span className="explore-anonymous">Hermano registrado — identidad no publicada</span>
                      ) : s.user?.id ? (
                        <Link to={`/people/${s.user.id}`} className="explore-user-link">
                          {[s.user.last_name, s.user.name].filter(Boolean).join(', ')}
                          {s.user.profession && <span className="explore-user-profession"> · {s.user.profession}</span>}
                          {(s.user.locality || s.user.province) && <span className="explore-user-location"> · {[s.user.locality, s.user.province].filter(Boolean).join(', ')}</span>}
                        </Link>
                      ) : null}
                    </div>
                    <Button type="button" variant="outline" onClick={() => openContact({ type: 'service', item: s })}>Solicitar contacto</Button>
                  </div>
                </div>
              ))}
            </div>
          )}

          {tab === 'needs' && (
            <div className="explore-list">
              {needs.map(n => (
                <div key={n.id} className="explore-card">
                  <div className="explore-card-header">
                    <div>
                      <span className="explore-card-title">{n.title}</span>
                      {n.category && <span className="explore-card-category">{n.category.name}</span>}
                    </div>
                    {n.urgency && (
                      <Badge variant={n.urgency === 'high' ? 'error' : n.urgency === 'medium' ? 'warning' : 'success'}>
                        {n.urgency === 'high' ? 'Urgente' : n.urgency === 'medium' ? 'Media urgencia' : 'Baja urgencia'}
                      </Badge>
                    )}
                  </div>
                  {n.description && <p className="explore-card-desc">{n.description}</p>}
                  {n.location && <div className="explore-card-meta">{n.location}</div>}
                  <div className="explore-card-footer">
                    <div className="explore-author">
                      {n.user?.anonymous ? (
                        <span className="explore-anonymous">Hermano registrado — identidad no publicada</span>
                      ) : n.user?.id ? (
                        <Link to={`/people/${n.user.id}`} className="explore-user-link">
                          {[n.user.last_name, n.user.name].filter(Boolean).join(', ')}
                          {(n.user.locality || n.user.province) && <span className="explore-user-location"> · {[n.user.locality, n.user.province].filter(Boolean).join(', ')}</span>}
                        </Link>
                      ) : null}
                    </div>
                    <Button type="button" variant="outline" onClick={() => openContact({ type: 'need', item: n })}>Solicitar contacto</Button>
                  </div>
                </div>
              ))}
            </div>
          )}

          <Pagination currentPage={page} lastPage={lastPage} total={total} onPageChange={handlePageChange} />
        </>
      )}

      <Modal open={contactTarget !== null} onClose={() => setContactTarget(null)} title="Solicitar contacto">
        {contactTarget && (
          <div className="explore-contact-form">
            <p className="explore-contact-context">{contactTarget.item.title}</p>
            <FormField label="Mensaje">
              <textarea className="explore-textarea" value={contactMessage} onChange={e => setContactMessage(e.target.value)} rows={4} placeholder="Contá por qué querés ponerte en contacto..." />
            </FormField>
            <div className="explore-shared-fields">
              {Object.entries(SHARED_FIELD_LABELS).map(([field, label]) => (
                <label key={field} className="explore-shared-field">
                  <input
                    type="checkbox"
                    checked={sharedFields.includes(field as SharedContactField)}
                    onChange={() => toggleSharedField(field as SharedContactField)}
                  />
                  {label}
                </label>
              ))}
            </div>
            <PrivacyIndicator
              summary={`Vas a compartir: ${sharedFields.map(field => SHARED_FIELD_LABELS[field]).join(', ') || 'ningún dato de contacto'}.`}
              details={[
                'El destinatario verá el mensaje y el contexto de la publicación.',
                'Los datos no seleccionados no se incluyen en la solicitud.',
                'La solicitud queda registrada para seguimiento interno.',
              ]}
            />
            <div className="explore-modal-actions">
              <Button type="button" variant="outline" onClick={() => setContactTarget(null)}>Cancelar</Button>
              <Button type="button" onClick={sendContactRequest} loading={sendingContact}>Enviar solicitud</Button>
            </div>
          </div>
        )}
      </Modal>
    </>
  )
  if (embedded) return content
  return <AppLayout>{content}</AppLayout>
}

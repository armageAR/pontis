import { useCallback, useEffect, useState } from 'react'
import { RefreshCw, RefreshCcw } from 'lucide-react'
import * as api from '@/api/workshops'
import * as syncApi from '@/api/sync'
import type { Workshop, WorkshopFilters as Filters, WorkshopFormData } from '@/api/workshops'
import type { GlaDiff } from '@/api/sync'
import { useAuth } from '@/context/useAuth'
import AppLayout from '@/components/AppLayout'
import Button from '@/components/Button'
import Spinner from '@/components/Spinner'
import GlaSyncModal from './GlaSyncModal'
import Pagination from '@/components/Pagination'
import EmptyState from '@/components/EmptyState'
import Modal from '@/components/Modal'
import ConfirmDialog from '@/components/ConfirmDialog'
import Alert from '@/components/Alert'
import WorkshopFiltersBar from './WorkshopFilters'
import WorkshopTable from './WorkshopTable'
import WorkshopForm from './WorkshopForm'
import WorkshopDetail from './WorkshopDetail'
import './WorkshopsPage.css'

type ModalView = 'none' | 'create' | 'edit' | 'detail'

export default function WorkshopsPage() {
  const { user: currentUser } = useAuth()
  const [workshops, setWorkshops] = useState<Workshop[]>([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
  const [filters, setFilters] = useState<Filters>({ sort_by: 'number', sort_direction: 'asc', page: 1 })
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const [modalView, setModalView] = useState<ModalView>('none')
  const [selected, setSelected] = useState<Workshop | null>(null)

  const [confirmDelete, setConfirmDelete] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [toggling, setToggling] = useState(false)
  const [actionMsg, setActionMsg] = useState('')

  const [refreshing, setRefreshing] = useState(false)
  const [syncing, setSyncing]       = useState(false)
  const [glaDiff, setGlaDiff]       = useState<GlaDiff | null>(null)
  const [workshopToLeave, setWorkshopToLeave] = useState<Workshop | null>(null)
  const [leaving, setLeaving] = useState(false)
  const [joiningId, setJoiningId] = useState<number | null>(null)

  const fetchWorkshops = useCallback(async (f: Filters) => {
    setLoading(true)
    setError('')
    try {
      const res = await api.listWorkshops(f)
      setWorkshops(res.data)
      setMeta(res.meta)
    } catch {
      setError('No se pudieron cargar los talleres.')
    } finally {
      setLoading(false)
    }
  }, [])

  // `filters` only ever changes from user input, so this cannot cascade: the
  // fetch raises the loading flag before awaiting and lowers it when it settles.
  // Clearing this rule would mean adopting a data-fetching library; see issue #3.
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    fetchWorkshops(filters)
  }, [filters, fetchWorkshops])

  function updateFilters(partial: Partial<Filters>) {
    setFilters((prev) => ({ ...prev, ...partial }))
  }

  async function handleRefresh() {
    setRefreshing(true)
    await fetchWorkshops(filters)
    setRefreshing(false)
  }

  async function handleGlaSync() {
    setSyncing(true)
    setError('')
    try {
      const diff = await syncApi.previewGlaSync()
      setGlaDiff(diff)
    } catch {
      setError('No se pudo conectar con GLA para obtener los datos.')
    } finally {
      setSyncing(false)
    }
  }

  function handleGlaApplied() {
    setGlaDiff(null)
    setActionMsg('Sincronización con GLA aplicada correctamente.')
    fetchWorkshops(filters)
  }

  function openCreate() {
    setSelected(null)
    setModalView('create')
    setActionMsg('')
  }

  function openDetail(w: Workshop) {
    setSelected(w)
    setModalView('detail')
    setActionMsg('')
  }

  function openEdit() {
    setModalView('edit')
  }

  function closeModal() {
    setModalView('none')
    setSelected(null)
  }

  async function handleCreate(data: WorkshopFormData) {
    await api.createWorkshop(data)
    closeModal()
    setActionMsg('Taller creado correctamente.')
    fetchWorkshops(filters)
  }

  async function handleUpdate(data: WorkshopFormData) {
    if (!selected) return
    const updated = await api.updateWorkshop(selected.id, data)
    setSelected(updated)
    setModalView('detail')
    setActionMsg('Taller actualizado correctamente.')
    fetchWorkshops(filters)
  }

  async function handleDelete() {
    if (!selected) return
    setDeleting(true)
    try {
      await api.deleteWorkshop(selected.id)
      setConfirmDelete(false)
      closeModal()
      setActionMsg('Taller eliminado correctamente.')
      fetchWorkshops(filters)
    } catch {
      setError('No se pudo eliminar el taller.')
    } finally {
      setDeleting(false)
    }
  }

  function patchWorkshop(updated: Workshop) {
    setWorkshops((prev) => prev.map((w) => (w.id === updated.id ? updated : w)))
  }

  async function handleJoin(workshop: Workshop) {
    setJoiningId(workshop.id)
    try {
      const updated = await api.joinWorkshop(workshop.id)
      patchWorkshop(updated)
      setActionMsg(`Te uniste a ${workshop.name}.`)
    } catch {
      setError('No se pudo solicitar el ingreso.')
    } finally {
      setJoiningId(null)
    }
  }

  async function handleLeave() {
    if (!workshopToLeave) return
    setLeaving(true)
    try {
      const updated = await api.leaveWorkshop(workshopToLeave.id)
      patchWorkshop(updated)
      setActionMsg(`Saliste de ${workshopToLeave.name}.`)
      setWorkshopToLeave(null)
    } catch {
      setError('No se pudo salir del taller.')
    } finally {
      setLeaving(false)
    }
  }

  async function handleToggleStatus() {
    if (!selected) return
    setToggling(true)
    try {
      const updated = selected.status === 'active'
        ? await api.disableWorkshop(selected.id)
        : await api.enableWorkshop(selected.id)
      setSelected(updated)
      setActionMsg(updated.status === 'active' ? 'Taller reactivado.' : 'Taller deshabilitado.')
      fetchWorkshops(filters)
    } catch {
      setError('No se pudo cambiar el estado del taller.')
    } finally {
      setToggling(false)
    }
  }

  return (
    <AppLayout>
      <div className="workshops-header">
        <h1 className="workshops-title">Talleres</h1>
        <div className="workshops-header-actions">
          <Button variant="outline" onClick={handleRefresh} loading={refreshing} title="Actualizar lista">
            <RefreshCw size={15} />
            Actualizar
          </Button>
          {currentUser?.role === 'superadmin' && (
            <Button variant="outline" onClick={handleGlaSync} loading={syncing}>
              <RefreshCcw size={15} />
              Sincronizar con GLA
            </Button>
          )}
          <Button onClick={openCreate}>Nuevo taller</Button>
        </div>
      </div>

      {actionMsg && <Alert variant="success">{actionMsg}</Alert>}
      {error && <Alert variant="error">{error}</Alert>}

      <WorkshopFiltersBar filters={filters} onChange={updateFilters} />

      {loading ? (
        <div className="workshops-loading">
          <Spinner size={28} />
        </div>
      ) : workshops.length === 0 ? (
        <EmptyState
          title="No se encontraron talleres"
          description={filters.search ? 'Probá con otros filtros o términos de búsqueda.' : 'Creá el primer taller para comenzar.'}
        />
      ) : (
        <>
          <WorkshopTable
            workshops={workshops}
            sortBy={filters.sort_by ?? 'number'}
            sortDirection={filters.sort_direction ?? 'asc'}
            onSort={updateFilters}
            onSelect={openDetail}
            joiningId={joiningId}
            onJoin={handleJoin}
            onLeave={setWorkshopToLeave}
          />
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            total={meta.total}
            onPageChange={(page) => updateFilters({ page })}
          />
        </>
      )}

      {/* Create modal */}
      <Modal open={modalView === 'create'} onClose={closeModal} title="Nuevo taller">
        <WorkshopForm onSubmit={handleCreate} onCancel={closeModal} submitLabel="Crear taller" />
      </Modal>

      {/* Edit modal */}
      <Modal open={modalView === 'edit'} onClose={() => setModalView('detail')} title="Editar taller">
        <WorkshopForm
          workshop={selected}
          onSubmit={handleUpdate}
          onCancel={() => setModalView('detail')}
          submitLabel="Guardar cambios"
        />
      </Modal>

      {/* Detail modal */}
      <Modal open={modalView === 'detail'} onClose={closeModal} title={selected ? `${selected.name} Nro ${selected.number}` : ''}>
        {selected && (
          <>
            <WorkshopDetail workshop={selected} />
            <div className="workshops-detail-actions">
              <Button variant="outline" onClick={handleToggleStatus} loading={toggling}>
                {selected.status === 'active' ? 'Deshabilitar' : 'Reactivar'}
              </Button>
              <Button variant="outline" onClick={() => setConfirmDelete(true)}>
                Eliminar
              </Button>
              <Button onClick={openEdit}>Editar</Button>
            </div>
          </>
        )}
      </Modal>

      {/* Delete confirmation */}
      <ConfirmDialog
        open={confirmDelete}
        onClose={() => setConfirmDelete(false)}
        onConfirm={handleDelete}
        title="Eliminar taller"
        message={`¿Estás seguro de que querés eliminar "${selected?.name} Nro ${selected?.number}"? Esta acción se puede revertir.`}
        confirmLabel="Eliminar"
        loading={deleting}
      />

      {/* Leave confirmation */}
      <ConfirmDialog
        open={workshopToLeave !== null}
        onClose={() => setWorkshopToLeave(null)}
        onConfirm={handleLeave}
        title="Salir del taller"
        message={`¿Confirmás que querés salir de ${workshopToLeave?.name}?`}
        confirmLabel="Salir"
        loading={leaving}
      />

      {/* GLA sync modal */}
      {glaDiff && (
        <GlaSyncModal
          open
          diff={glaDiff}
          onClose={() => setGlaDiff(null)}
          onApplied={handleGlaApplied}
        />
      )}
    </AppLayout>
  )
}

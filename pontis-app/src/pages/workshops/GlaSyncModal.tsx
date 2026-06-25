import { useState } from 'react'
import Modal from '@/components/Modal'
import Button from '@/components/Button'
import type { GlaDiff, GlaModifiedWorkshop, GlaNewWorkshop, GlaDisabledWorkshop } from '@/api/sync'
import * as syncApi from '@/api/sync'
import './GlaSyncModal.css'

const FIELD_LABELS: Record<string, string> = {
  name:           'Nombre',
  zone_number:    'N° Zona',
  zone_name:      'Zona',
  work_day:       'Día',
  work_frequency: 'Frecuencia',
  address:        'Dirección',
  city:           'Ciudad',
  province:       'Provincia',
  language:       'Idioma',
}

interface Props {
  open: boolean
  diff: GlaDiff
  onClose: () => void
  onApplied: () => void
}

export default function GlaSyncModal({ open, diff, onClose, onApplied }: Props) {
  const [selectedNew, setSelectedNew]           = useState<Set<number>>(() => new Set(diff.new.map((w) => w.number)))
  const [selectedModified, setSelectedModified] = useState<Set<number>>(() => new Set(diff.modified.map((w) => w.id)))
  const [selectedDisabled, setSelectedDisabled] = useState<Set<number>>(() => new Set(diff.disabled.map((w) => w.id)))
  const [applying, setApplying]                 = useState(false)
  const [error, setError]                       = useState('')

  const totalSelected = selectedNew.size + selectedModified.size + selectedDisabled.size

  function toggleNew(number: number) {
    setSelectedNew((prev) => {
      const next = new Set(prev)
      next.has(number) ? next.delete(number) : next.add(number)
      return next
    })
  }

  function toggleModified(id: number) {
    setSelectedModified((prev) => {
      const next = new Set(prev)
      next.has(id) ? next.delete(id) : next.add(id)
      return next
    })
  }

  function toggleDisabled(id: number) {
    setSelectedDisabled((prev) => {
      const next = new Set(prev)
      next.has(id) ? next.delete(id) : next.add(id)
      return next
    })
  }

  async function handleApply() {
    setApplying(true)
    setError('')
    try {
      await syncApi.applyGlaSync({
        new:      Array.from(selectedNew),
        modified: Array.from(selectedModified),
        disabled: Array.from(selectedDisabled),
      })
      onApplied()
    } catch {
      setError('No se pudieron aplicar los cambios. Intentá de nuevo.')
    } finally {
      setApplying(false)
    }
  }

  const hasChanges = diff.new.length > 0 || diff.modified.length > 0 || diff.disabled.length > 0

  return (
    <Modal open={open} onClose={onClose} title="Sincronizar con GLA">
      <div className="gla-sync-modal">
        {!hasChanges && (
          <p className="gla-sync-empty">No se detectaron cambios. Los talleres están al día con GLA.</p>
        )}

        {diff.new.length > 0 && (
          <GlaSyncSection title="Talleres nuevos" badgeClass="gla-badge-new" count={diff.new.length}>
            {diff.new.map((w) => (
              <NewWorkshopItem
                key={w.number}
                workshop={w}
                checked={selectedNew.has(w.number)}
                onChange={() => toggleNew(w.number)}
              />
            ))}
          </GlaSyncSection>
        )}

        {diff.modified.length > 0 && (
          <GlaSyncSection title="Talleres modificados" badgeClass="gla-badge-modified" count={diff.modified.length}>
            {diff.modified.map((w) => (
              <ModifiedWorkshopItem
                key={w.id}
                workshop={w}
                checked={selectedModified.has(w.id)}
                onChange={() => toggleModified(w.id)}
              />
            ))}
          </GlaSyncSection>
        )}

        {diff.disabled.length > 0 && (
          <GlaSyncSection title="Talleres a deshabilitar" badgeClass="gla-badge-disabled" count={diff.disabled.length}>
            {diff.disabled.map((w) => (
              <DisabledWorkshopItem
                key={w.id}
                workshop={w}
                checked={selectedDisabled.has(w.id)}
                onChange={() => toggleDisabled(w.id)}
              />
            ))}
          </GlaSyncSection>
        )}

        {error && <p style={{ color: 'var(--error)', fontSize: 13, margin: 0 }}>{error}</p>}

        {hasChanges && (
          <div className="gla-sync-footer">
            <p className="gla-sync-footer-count">
              <strong>{totalSelected}</strong> cambio{totalSelected !== 1 ? 's' : ''} seleccionado{totalSelected !== 1 ? 's' : ''}
            </p>
            <Button variant="outline" onClick={onClose} disabled={applying}>Cancelar</Button>
            <Button onClick={handleApply} loading={applying} disabled={totalSelected === 0}>
              Aplicar cambios
            </Button>
          </div>
        )}
      </div>
    </Modal>
  )
}

function GlaSyncSection({
  title,
  badgeClass,
  count,
  children,
}: {
  title: string
  badgeClass: string
  count: number
  children: React.ReactNode
}) {
  return (
    <div className="gla-sync-section">
      <div className="gla-sync-section-header">
        <span className="gla-sync-section-title">{title}</span>
        <span className={`gla-sync-badge ${badgeClass}`}>{count}</span>
      </div>
      <div className="gla-sync-list">{children}</div>
    </div>
  )
}

function NewWorkshopItem({ workshop: w, checked, onChange }: { workshop: GlaNewWorkshop; checked: boolean; onChange: () => void }) {
  const sub = [w.city, w.province].filter(Boolean).join(', ')
  const meta = [w.work_day, w.work_frequency].filter(Boolean).join(' · ')

  return (
    <label className="gla-sync-item">
      <input type="checkbox" checked={checked} onChange={onChange} />
      <div className="gla-sync-item-body">
        <div className="gla-sync-item-title">Nro {w.number} — {w.name}</div>
        {(sub || meta) && (
          <div className="gla-sync-item-sub">
            {[sub, meta].filter(Boolean).join(' · ')}
          </div>
        )}
      </div>
    </label>
  )
}

function ModifiedWorkshopItem({ workshop: w, checked, onChange }: { workshop: GlaModifiedWorkshop; checked: boolean; onChange: () => void }) {
  return (
    <label className="gla-sync-item">
      <input type="checkbox" checked={checked} onChange={onChange} />
      <div className="gla-sync-item-body">
        <div className="gla-sync-item-title">Nro {w.number} — {w.name}</div>
        <div className="gla-sync-changes">
          {Object.entries(w.changes).map(([field, { from, to }]) => (
            <div key={field} className="gla-sync-change">
              <span className="gla-change-field">{FIELD_LABELS[field] ?? field}</span>
              <span className="gla-change-from">{from ?? '—'}</span>
              <span className="gla-change-arrow">→</span>
              <span className="gla-change-to">{to ?? '—'}</span>
            </div>
          ))}
        </div>
      </div>
    </label>
  )
}

function DisabledWorkshopItem({ workshop: w, checked, onChange }: { workshop: GlaDisabledWorkshop; checked: boolean; onChange: () => void }) {
  return (
    <label className="gla-sync-item">
      <input type="checkbox" checked={checked} onChange={onChange} />
      <div className="gla-sync-item-body">
        <div className="gla-sync-item-title">Nro {w.number} — {w.name}</div>
        {w.city && <div className="gla-sync-item-sub">{w.city}</div>}
      </div>
    </label>
  )
}

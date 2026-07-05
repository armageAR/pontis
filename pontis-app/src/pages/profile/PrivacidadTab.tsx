import * as visibilityApi from '@/api/visibility'
import type { VisibilityBlock, VisibilityLevel, VisibilityMap } from '@/api/visibility'

interface PrivacidadTabProps {
  visibility: VisibilityMap
  onEdit: (tabKey: string) => void
}

// Bloque de visibilidad → { etiqueta, pestaña donde se edita }
const ROWS: { block: VisibilityBlock; label: string; tabKey: string; tabLabel: string }[] = [
  { block: 'identity', label: 'Identidad', tabKey: 'identidad', tabLabel: 'Identidad' },
  { block: 'contact', label: 'Contacto', tabKey: 'contacto', tabLabel: 'Contacto' },
  { block: 'profession', label: 'Perfil profesional', tabKey: 'profesional', tabLabel: 'Perfil profesional' },
  { block: 'location', label: 'Ubicación', tabKey: 'ubicacion', tabLabel: 'Ubicación' },
  { block: 'masonic', label: 'Vida masónica', tabKey: 'masonica', tabLabel: 'Vida masónica' },
  { block: 'degrees', label: 'Grados', tabKey: 'masonica', tabLabel: 'Vida masónica' },
  { block: 'positions', label: 'Cargos', tabKey: 'masonica', tabLabel: 'Vida masónica' },
  { block: 'bio', label: 'Bio', tabKey: 'profesional', tabLabel: 'Perfil profesional' },
]

export default function PrivacidadTab({ visibility, onEdit }: PrivacidadTabProps) {
  // Cambia de pestaña y mueve el foco a la pestaña destino para usuarios de
  // teclado / lector de pantalla (la pestaña actual se desmonta al cambiar).
  function goToTab(tabKey: string) {
    onEdit(tabKey)
    requestAnimationFrame(() => document.getElementById(`tab-${tabKey}`)?.focus())
  }

  return (
    <div className="profile-fields">
      <p className="profile-section-desc">
        ¿Quién ve qué? Este resumen muestra la audiencia elegida para cada bloque de tu perfil.
        Editá cada bloque desde su pestaña correspondiente.
      </p>
      <ul className="p-privacy-overview">
        {ROWS.map(row => {
          const setting = visibility[row.block]
          const level: VisibilityLevel = setting?.visibility ?? 'workshop'
          const anon = row.block === 'identity' && setting?.anonymous_search
          return (
            <li key={row.block} className="p-privacy-row">
              <div className="p-privacy-info">
                <span className="p-privacy-label">{row.label}</span>
                <span className="p-audience-badge">{visibilityApi.VISIBILITY_LABELS[level]}</span>
                {anon && (
                  <span className="p-privacy-sub">También aparecés en búsquedas con identidad reservada.</span>
                )}
              </div>
              <button type="button" className="profile-link-btn" onClick={() => goToTab(row.tabKey)}>
                Editar en {row.tabLabel}
              </button>
            </li>
          )
        })}
      </ul>
    </div>
  )
}

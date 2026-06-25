import { ChevronUp, ChevronDown } from 'lucide-react'
import type { Workshop, WorkshopFilters } from '@/api/workshops'
import Badge from '@/components/Badge'
import Button from '@/components/Button'
import './WorkshopTable.css'

interface WorkshopTableProps {
  workshops: Workshop[]
  sortBy: string
  sortDirection: 'asc' | 'desc'
  onSort: (filters: Partial<WorkshopFilters>) => void
  onSelect: (workshop: Workshop) => void
  joiningId: number | null
  onJoin: (workshop: Workshop) => void
  onLeave: (workshop: Workshop) => void
}

interface Column {
  key: string
  label: string
  sortable: boolean
}

const COLUMNS: Column[] = [
  { key: 'number', label: 'Nro', sortable: true },
  { key: 'name', label: 'Nombre', sortable: true },
  { key: 'zone_number', label: 'Zona', sortable: true },
  { key: 'work_day', label: 'Día', sortable: true },
  { key: 'work_frequency', label: 'Frecuencia', sortable: false },
  { key: 'city', label: 'Ciudad', sortable: false },
  { key: 'status', label: 'Estado', sortable: false },
]

function SortIcon({ col, sortBy, sortDirection }: { col: string; sortBy: string; sortDirection: 'asc' | 'desc' }) {
  if (col !== sortBy) return <span className="sort-icon sort-icon-idle"><ChevronUp size={12} /></span>
  return sortDirection === 'asc'
    ? <span className="sort-icon sort-icon-active"><ChevronUp size={12} /></span>
    : <span className="sort-icon sort-icon-active"><ChevronDown size={12} /></span>
}

export default function WorkshopTable({ workshops, sortBy, sortDirection, onSort, onSelect, joiningId, onJoin, onLeave }: WorkshopTableProps) {
  function handleSort(key: string) {
    const newDir = sortBy === key && sortDirection === 'asc' ? 'desc' : 'asc'
    onSort({ sort_by: key, sort_direction: newDir })
  }

  return (
    <div className="workshop-table-wrapper">
      <table className="workshop-table">
        <thead>
          <tr>
            {COLUMNS.map((col) => (
              <th
                key={col.key}
                className={col.sortable ? 'sortable' : ''}
                onClick={col.sortable ? () => handleSort(col.key) : undefined}
              >
                <span className="th-content">
                  {col.label}
                  {col.sortable && <SortIcon col={col.key} sortBy={sortBy} sortDirection={sortDirection} />}
                </span>
              </th>
            ))}
            <th />
          </tr>
        </thead>
        <tbody>
          {workshops.map((w) => (
            <tr key={w.id} onClick={() => onSelect(w)} className="workshop-row">
              <td className="workshop-cell-number">{w.number}</td>
              <td className="workshop-cell-name">{w.name}</td>
              <td>{w.zone_number ?? '—'}</td>
              <td>{w.work_day ?? '—'}</td>
              <td>{w.work_frequency ?? '—'}</td>
              <td>{w.city ?? '—'}</td>
              <td>
                <Badge variant={w.status === 'active' ? 'success' : 'error'}>
                  {w.status === 'active' ? 'Activo' : 'Deshabilitado'}
                </Badge>
              </td>
              <td className="workshop-cell-action" onClick={(e) => e.stopPropagation()}>
                {w.is_member ? (
                  <Button
                    variant="outline"
                    className="workshop-action-btn workshop-action-leave"
                    onClick={() => onLeave(w)}
                  >
                    Salir
                  </Button>
                ) : w.is_pending ? (
                  <span className="workshop-action-pending">Solicitud pendiente</span>
                ) : (
                  <Button
                    variant="outline"
                    className="workshop-action-btn"
                    loading={joiningId === w.id}
                    disabled={w.status !== 'active'}
                    onClick={() => onJoin(w)}
                  >
                    Solicitar ingreso
                  </Button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

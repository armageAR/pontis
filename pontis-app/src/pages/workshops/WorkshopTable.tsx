import type { Workshop, WorkshopFilters } from '@/api/workshops'
import Badge from '@/components/Badge'
import './WorkshopTable.css'

interface WorkshopTableProps {
  workshops: Workshop[]
  sortBy: string
  sortDirection: 'asc' | 'desc'
  onSort: (filters: Partial<WorkshopFilters>) => void
  onSelect: (workshop: Workshop) => void
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

export default function WorkshopTable({ workshops, sortBy, sortDirection, onSort, onSelect }: WorkshopTableProps) {
  function handleSort(key: string) {
    const newDir = sortBy === key && sortDirection === 'asc' ? 'desc' : 'asc'
    onSort({ sort_by: key, sort_direction: newDir })
  }

  function sortIndicator(key: string) {
    if (sortBy !== key) return ''
    return sortDirection === 'asc' ? ' ↑' : ' ↓'
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
                {col.label}{col.sortable ? sortIndicator(col.key) : ''}
              </th>
            ))}
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
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

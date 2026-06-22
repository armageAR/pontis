import { type ChangeEvent } from 'react'
import Input from '@/components/Input'
import Select from '@/components/Select'
import type { WorkshopFilters as Filters } from '@/api/workshops'
import './WorkshopFilters.css'

interface WorkshopFiltersProps {
  filters: Filters
  onChange: (filters: Filters) => void
}

const WORK_DAYS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']

export default function WorkshopFilters({ filters, onChange }: WorkshopFiltersProps) {
  function set(key: keyof Filters, value: string) {
    onChange({ ...filters, [key]: value, page: 1 })
  }

  function handleSearch(e: ChangeEvent<HTMLInputElement>) {
    set('search', e.target.value)
  }

  return (
    <div className="workshop-filters">
      <Input
        placeholder="Buscar por nombre, dirección, ciudad..."
        value={filters.search ?? ''}
        onChange={handleSearch}
        className="workshop-filters-search"
      />
      <Select
        value={filters.status ?? ''}
        onChange={(e) => set('status', e.target.value)}
      >
        <option value="">Estado</option>
        <option value="active">Activo</option>
        <option value="disabled">Deshabilitado</option>
      </Select>
      <Select
        value={filters.work_day ?? ''}
        onChange={(e) => set('work_day', e.target.value)}
      >
        <option value="">Día de trabajo</option>
        {WORK_DAYS.map((d) => (
          <option key={d} value={d}>{d}</option>
        ))}
      </Select>
    </div>
  )
}

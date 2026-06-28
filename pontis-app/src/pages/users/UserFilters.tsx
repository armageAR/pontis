import { useState, type ChangeEvent } from 'react'
import Input from '@/components/Input'
import Select from '@/components/Select'
import WorkshopPicker from '@/components/WorkshopPicker'
import type { UserFilters as Filters, WorkshopOption } from '@/api/users'
import { type WorkshopSearchResult } from '@/api/workshops'
import './UserFilters.css'

interface UserFiltersProps {
  filters: Filters
  onChange: (filters: Filters) => void
  workshops: WorkshopOption[]
}

export default function UserFilters({ filters, onChange }: UserFiltersProps) {
  const [selectedWorkshop, setSelectedWorkshop] = useState<WorkshopSearchResult | null>(null)

  function set(key: keyof Filters, value: string) {
    onChange({ ...filters, [key]: value, page: 1 })
  }

  return (
    <div className="user-filters">
      <Input
        placeholder="Buscar por nombre o email..."
        value={filters.search ?? ''}
        onChange={(e: ChangeEvent<HTMLInputElement>) => set('search', e.target.value)}
        className="user-filters-search"
      />
      <WorkshopPicker
        value={selectedWorkshop}
        onChange={(w) => {
          setSelectedWorkshop(w)
          onChange({ ...filters, workshop_id: w ? String(w.id) : '', page: 1 })
        }}
      />
      <Select
        value={filters.workshop_role ?? ''}
        onChange={(e) => set('workshop_role', e.target.value)}
      >
        <option value="">Rol en taller</option>
        <option value="admin">Admin de taller</option>
        <option value="member">Miembro</option>
      </Select>
      <Select
        value={filters.status ?? ''}
        onChange={(e) => set('status', e.target.value)}
      >
        <option value="">Estado</option>
        <option value="active">Activo</option>
        <option value="pending">Pendiente</option>
        <option value="rejected">Rechazado</option>
        <option value="suspended">Suspendido</option>
        <option value="inactive">Baja</option>
      </Select>
    </div>
  )
}

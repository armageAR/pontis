import { type ChangeEvent } from 'react'
import Input from '@/components/Input'
import Select from '@/components/Select'
import type { UserFilters as Filters, WorkshopOption } from '@/api/users'
import './UserFilters.css'

interface UserFiltersProps {
  filters: Filters
  onChange: (filters: Filters) => void
  workshops: WorkshopOption[]
}

export default function UserFilters({ filters, onChange, workshops }: UserFiltersProps) {
  function set(key: keyof Filters, value: string) {
    onChange({ ...filters, [key]: value, page: 1 })
  }

  return (
    <div className="user-filters">
      <Input
        placeholder="Nombre, email o matrícula"
        value={filters.search ?? ''}
        onChange={(e: ChangeEvent<HTMLInputElement>) => set('search', e.target.value)}
        className="user-filters-search"
      />
      <Select
        value={filters.workshop_id ? String(filters.workshop_id) : ''}
        onChange={(e) => set('workshop_id', e.target.value)}
      >
        <option value="">Taller</option>
        {workshops.map((w) => (
          <option key={w.id} value={String(w.id)}>Nº{w.number} {w.name}</option>
        ))}
      </Select>
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
        <option value="o_eterno">O Eterno</option>
      </Select>
    </div>
  )
}

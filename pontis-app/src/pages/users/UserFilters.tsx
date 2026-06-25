import { type ChangeEvent } from 'react'
import Input from '@/components/Input'
import Select from '@/components/Select'
import type { UserFilters as Filters } from '@/api/users'
import './UserFilters.css'

interface UserFiltersProps {
  filters: Filters
  onChange: (filters: Filters) => void
}

export default function UserFilters({ filters, onChange }: UserFiltersProps) {
  function set(key: keyof Filters, value: string) {
    onChange({ ...filters, [key]: value, page: 1 })
  }

  function handleSearch(e: ChangeEvent<HTMLInputElement>) {
    set('search', e.target.value)
  }

  return (
    <div className="user-filters">
      <Input
        placeholder="Buscar por nombre o email..."
        value={filters.search ?? ''}
        onChange={handleSearch}
        className="user-filters-search"
      />
      <Select
        value={filters.role ?? ''}
        onChange={(e) => set('role', e.target.value)}
      >
        <option value="">Rol</option>
        <option value="superadmin">Super Admin</option>
        <option value="admin">Admin</option>
        <option value="user">Usuario</option>
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

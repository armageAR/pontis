import { useEffect, useState, type ChangeEvent } from 'react'
import Input from '@/components/Input'
import Select from '@/components/Select'
import WorkshopPicker from '@/components/WorkshopPicker'
import type { WorkshopSearchResult } from '@/api/workshops'
import { getProvinces, type Province } from '@/api/provinces'
import type { UserFilters as Filters } from '@/api/users'
import './UserFilters.css'

interface UserFiltersProps {
  filters: Filters
  onChange: (filters: Filters) => void
}

export default function UserFilters({ filters, onChange }: UserFiltersProps) {
  const [provinces, setProvinces] = useState<Province[]>([])
  const [selectedWorkshop, setSelectedWorkshop] = useState<WorkshopSearchResult | null>(null)

  useEffect(() => {
    getProvinces().then(setProvinces).catch(() => {})
  }, [])

  function set(key: keyof Filters, value: string) {
    onChange({ ...filters, [key]: value, page: 1 })
  }

  function handleWorkshop(w: WorkshopSearchResult | null) {
    setSelectedWorkshop(w)
    onChange({ ...filters, workshop_id: w ? w.id : '', page: 1 })
  }

  return (
    <div className="user-filters">
      <Input
        placeholder="Nombre, apellido, email o matrícula"
        value={filters.search ?? ''}
        onChange={(e: ChangeEvent<HTMLInputElement>) => set('search', e.target.value)}
        className="user-filters-search"
      />
      <div className="user-filters-workshop">
        <WorkshopPicker
          value={selectedWorkshop}
          onChange={handleWorkshop}
          placeholder="Taller (nombre o número)"
        />
      </div>
      <Select
        value={filters.province ?? ''}
        onChange={(e) => set('province', e.target.value)}
      >
        <option value="">Provincia</option>
        {provinces.map((p) => (
          <option key={p.id} value={p.name}>{p.name}</option>
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

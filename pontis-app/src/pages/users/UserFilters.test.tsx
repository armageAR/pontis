import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { UserFilters as Filters } from '@/api/users'
import UserFilters from './UserFilters'

const h = vi.hoisted(() => ({
  provinces: [
    { id: 1, name: 'Buenos Aires', code: null },
    { id: 2, name: 'Santa Fe', code: null },
  ],
}))

vi.mock('@/api/provinces', () => ({ getProvinces: () => Promise.resolve(h.provinces) }))
vi.mock('@/api/workshops', () => ({ searchWorkshopsPublic: vi.fn(() => Promise.resolve([])) }))

function setup() {
  const onChange = vi.fn()
  const filters: Filters = { sort_by: 'last_name', sort_direction: 'asc', page: 1 }
  render(<UserFilters filters={filters} onChange={onChange} />)
  return { onChange, filters }
}

describe('UserFilters', () => {
  beforeEach(() => vi.clearAllMocks())

  it('renders a searchable Taller picker instead of a plain select', () => {
    setup()
    expect(screen.getByPlaceholderText('Taller (nombre o número)')).toBeInTheDocument()
  })

  it('loads provinces and applies the province filter resetting to page 1', async () => {
    const { onChange, filters } = setup()
    await waitFor(() => expect(screen.getByRole('option', { name: 'Santa Fe' })).toBeInTheDocument())
    // El primer combobox es el de Provincia.
    const provinceSelect = screen.getAllByRole('combobox')[0]

    const user = userEvent.setup()
    await user.selectOptions(provinceSelect, 'Santa Fe')

    expect(onChange).toHaveBeenCalledWith({ ...filters, province: 'Santa Fe', page: 1 })
  })
})

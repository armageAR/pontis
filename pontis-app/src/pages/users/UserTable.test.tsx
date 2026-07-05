import { describe, it, expect, vi } from 'vitest'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { UserListItem } from '@/api/users'
import UserTable from './UserTable'

function user(overrides: Partial<UserListItem> = {}): UserListItem {
  return {
    id: 1,
    name: 'Juan',
    last_name: 'Gerling',
    email: 'juan@example.com',
    province: 'Santa Fe',
    role: 'user',
    status: 'active',
    email_verified_at: null,
    created_at: '2026-01-01',
    workshops: [{ id: 5, name: 'La Fraternidad', number: 12, workshop_role: 'member' }],
    ...overrides,
  }
}

function renderTable(props: Partial<React.ComponentProps<typeof UserTable>> = {}) {
  const onSort = vi.fn()
  render(
    <UserTable
      users={[user()]}
      sortBy="last_name"
      sortDirection="asc"
      onSort={onSort}
      currentUserId={99}
      currentUserRole="superadmin"
      isCurrentUserWorkshopAdmin={false}
      onStatusChange={vi.fn(() => Promise.resolve())}
      onEdit={vi.fn()}
      onChangePassword={vi.fn()}
      onToggleSuperadmin={vi.fn()}
      onMarkOEterno={vi.fn()}
      {...props}
    />,
  )
  return { onSort }
}

describe('UserTable', () => {
  it('renders columns in the requested order and without Registro', () => {
    renderTable()
    const headers = screen.getAllByRole('columnheader').map((th) => th.textContent?.trim())
    expect(headers).toEqual(['Apellido/s', 'Nombre/s', 'Email', 'Talleres', 'Estado', 'Acciones'])
    expect(screen.queryByText('Registro')).not.toBeInTheDocument()
  })

  it('renders apellido and nombre in separate cells', () => {
    renderTable()
    const row = screen.getByText('juan@example.com').closest('tr')!
    const cells = within(row).getAllByRole('cell')
    expect(cells[0]).toHaveTextContent('Gerling')
    expect(cells[1]).toHaveTextContent('Juan')
  })

  it('sorts when clicking a data header and keeps toggling direction', async () => {
    const u = userEvent.setup()
    const { onSort } = renderTable({ sortBy: 'last_name', sortDirection: 'asc' })
    await u.click(screen.getByText('Apellido/s'))
    expect(onSort).toHaveBeenCalledWith({ sort_by: 'last_name', sort_direction: 'desc' })
  })

  it('allows sorting by the Acciones header', async () => {
    const u = userEvent.setup()
    const { onSort } = renderTable()
    await u.click(screen.getByText('Acciones'))
    expect(onSort).toHaveBeenCalledWith({ sort_by: 'actions', sort_direction: 'asc' })
  })
})

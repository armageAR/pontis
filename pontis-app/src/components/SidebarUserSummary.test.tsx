import { describe, it, expect, vi } from 'vitest'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import type { User } from '@/api/auth'
import SidebarUserSummary from './SidebarUserSummary'

vi.mock('./NotificationBell', () => ({ default: () => <button data-testid="notif-bell">bell</button> }))

function makeUser(overrides: Partial<User> = {}): User {
  return {
    id: 1,
    name: 'Juan',
    last_name: 'Pérez',
    email: 'juan@example.com',
    role: 'user',
    status: 'active',
    masonic_id: 'MAT-123',
    email_verified_at: null,
    principal_workshop: { id: 5, number: 12, name: 'La Fraternidad' },
    admin_workshops: [],
    ...overrides,
  }
}

function renderSummary(user: User) {
  return render(
    <MemoryRouter>
      <SidebarUserSummary user={user} loggingOut={false} onLogout={() => {}} />
    </MemoryRouter>,
  )
}

describe('SidebarUserSummary layout', () => {
  it('renders first and last name and places the bell on the same top row', () => {
    renderSummary(makeUser())
    const nameLink = screen.getByRole('link', { name: 'Juan Pérez' })
    const topRow = nameLink.closest('.sidebar-summary-top')
    expect(topRow).not.toBeNull()
    expect(within(topRow as HTMLElement).getByTestId('notif-bell')).toBeInTheDocument()
  })

  it('renders only the first name when there is no last name', () => {
    renderSummary(makeUser({ last_name: null }))
    const nameLink = screen.getByRole('link', { name: 'Juan' })
    expect(nameLink.textContent).toBe('Juan')
  })

  it('renders the principal Taller as number and name', () => {
    renderSummary(makeUser())
    expect(screen.getByText('Nº12 · La Fraternidad')).toBeInTheDocument()
  })

  it('does not render a principal Taller row when absent', () => {
    renderSummary(makeUser({ principal_workshop: null }))
    expect(screen.queryByText(/Nº12/)).not.toBeInTheDocument()
  })

  it('renders only the masonic id number without a label', () => {
    renderSummary(makeUser())
    const masonic = screen.getByText('MAT-123')
    expect(masonic).toBeInTheDocument()
    expect(masonic.textContent).toBe('MAT-123')
  })

  it('does not render a masonic row when absent', () => {
    renderSummary(makeUser({ masonic_id: null }))
    expect(screen.queryByText('MAT-123')).not.toBeInTheDocument()
  })

  it('shows the Superadmin badge for superadmin users', () => {
    renderSummary(makeUser({ role: 'superadmin' }))
    expect(screen.getByText('Superadmin')).toBeInTheDocument()
  })

  it('shows the Admin badge when the user administers a Taller', () => {
    renderSummary(makeUser({ admin_workshops: [{ id: 5, number: 12, name: 'La Fraternidad' }] }))
    expect(screen.getByText('Admin')).toBeInTheDocument()
  })

  it('shows no administrative badges for a regular user', () => {
    renderSummary(makeUser())
    expect(screen.queryByText('Superadmin')).not.toBeInTheDocument()
    expect(screen.queryByText('Admin')).not.toBeInTheDocument()
  })
})

describe('SidebarUserSummary admin badge disclosure', () => {
  const admin = makeUser({
    admin_workshops: [
      { id: 5, number: 12, name: 'La Fraternidad' },
      { id: 6, number: 7, name: 'La Tolerancia' },
    ],
  })

  it('discloses all administered Talleres on hover', async () => {
    const user = userEvent.setup()
    renderSummary(admin)
    const badge = screen.getByRole('button', { name: /Admin de Talleres/ })

    expect(screen.queryByRole('tooltip')).not.toBeInTheDocument()
    await user.hover(badge)

    const tooltip = screen.getByRole('tooltip')
    expect(within(tooltip).getByText('Nº12 La Fraternidad')).toBeInTheDocument()
    expect(within(tooltip).getByText('Nº7 La Tolerancia')).toBeInTheDocument()
  })

  it('discloses the Talleres on keyboard focus', async () => {
    const user = userEvent.setup()
    renderSummary(admin)
    const badge = screen.getByRole('button', { name: /Admin de Talleres/ })

    // Avanzar con Tab hasta enfocar el badge (dispara el onFocus real de React).
    for (let i = 0; i < 6 && document.activeElement !== badge; i++) {
      await user.tab()
    }
    expect(document.activeElement).toBe(badge)

    expect(screen.getByRole('tooltip')).toBeInTheDocument()
    expect(within(screen.getByRole('tooltip')).getByText('Nº7 La Tolerancia')).toBeInTheDocument()
  })
})

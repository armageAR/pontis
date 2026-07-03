import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import DashboardPage from './DashboardPage'

const h = vi.hoisted(() => ({
  user: { role: 'user', name: 'Test' } as { role: string; name: string },
  dashboard: {
    pending_requests: [],
    membership_notifications: [],
    is_workshop_admin: false,
    pending_validation_count: 0,
  } as {
    pending_requests: unknown[]
    membership_notifications: unknown[]
    is_workshop_admin: boolean
    pending_validation_count: number
  },
}))

vi.mock('@/context/AuthContext', () => ({ useAuth: () => ({ user: h.user }) }))
vi.mock('@/components/AppLayout', () => ({ default: ({ children }: { children: React.ReactNode }) => <div>{children}</div> }))
vi.mock('@/api/dashboard', () => ({
  getDashboard: () => Promise.resolve(h.dashboard),
  approveJoinRequest: vi.fn(),
  rejectJoinRequest: vi.fn(),
  requestCorrection: vi.fn(),
  dismissMembershipNotification: vi.fn(),
}))

function renderPage() {
  return render(<MemoryRouter><DashboardPage /></MemoryRouter>)
}

describe('DashboardPage administration box', () => {
  beforeEach(() => {
    h.user = { role: 'user', name: 'Test' }
    h.dashboard = { pending_requests: [], membership_notifications: [], is_workshop_admin: false, pending_validation_count: 0 }
  })

  it('shows the Administracion box with expected text for a workshop admin', async () => {
    h.dashboard.is_workshop_admin = true
    renderPage()

    const box = await screen.findByText('Administracion')
    expect(box).toBeInTheDocument()
    expect(screen.getByText('tareas de administracion en tu taller')).toBeInTheDocument()
    // Sin pendientes: no hay indicador rojo.
    expect(screen.queryByLabelText('Validaciones pendientes')).not.toBeInTheDocument()
  })

  it('shows the red pending indicator when there are pending validations', async () => {
    h.dashboard.is_workshop_admin = true
    h.dashboard.pending_validation_count = 3
    renderPage()

    await screen.findByText('Administracion')
    expect(screen.getByLabelText('Validaciones pendientes')).toBeInTheDocument()
  })

  it('does not show the Administracion box for a non-admin, non-superadmin user', async () => {
    h.dashboard.is_workshop_admin = false
    renderPage()

    // Esperamos a que el dashboard cargue (aparece una card estable).
    await screen.findByText('Mis Hermanos')
    await waitFor(() => expect(screen.queryByText('Administracion')).not.toBeInTheDocument())
  })

  it('shows the box for a superadmin even without workshop admin flag', async () => {
    h.user = { role: 'superadmin', name: 'Super' }
    h.dashboard.is_workshop_admin = false
    renderPage()

    expect(await screen.findByText('Administracion')).toBeInTheDocument()
  })
})

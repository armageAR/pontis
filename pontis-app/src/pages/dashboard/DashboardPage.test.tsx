import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import DashboardPage from './DashboardPage'

const h = vi.hoisted(() => ({
  user: { role: 'user', name: 'Test' } as { role: string; name: string },
  dashboard: {
    membership_notifications: [],
    is_workshop_admin: false,
    pending_validation_count: 0,
    profile_completion: { percent: 60, completed: 10, total: 16 },
  } as {
    membership_notifications: unknown[]
    is_workshop_admin: boolean
    pending_validation_count: number
    profile_completion: { percent: number; completed: number; total: number }
  },
  getDashboard: vi.fn(),
}))

vi.mock('@/context/AuthContext', () => ({ useAuth: () => ({ user: h.user }) }))
vi.mock('@/components/AppLayout', () => ({ default: ({ children }: { children: React.ReactNode }) => <div>{children}</div> }))
vi.mock('@/api/dashboard', () => ({
  getDashboard: () => h.getDashboard(),
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
    h.dashboard = { membership_notifications: [], is_workshop_admin: false, pending_validation_count: 0, profile_completion: { percent: 60, completed: 10, total: 16 } }
    h.getDashboard.mockReset()
    h.getDashboard.mockResolvedValue(h.dashboard)
  })

  it('does not render dashboard cards until dashboard data is loaded', async () => {
    let resolveDashboard!: (value: typeof h.dashboard) => void
    h.getDashboard.mockReturnValue(new Promise((resolve) => { resolveDashboard = resolve }))

    renderPage()

    expect(screen.getByLabelText('Cargando panel')).toBeInTheDocument()
    expect(screen.queryByText('Mis Hermanos')).not.toBeInTheDocument()
    expect(screen.queryByText('Administracion')).not.toBeInTheDocument()

    h.dashboard.is_workshop_admin = true
    resolveDashboard(h.dashboard)

    expect(await screen.findByText('Administracion')).toBeInTheDocument()
  })

  it('does not render a standalone "Solicitudes de ingreso" section on the Panel', async () => {
    h.dashboard.is_workshop_admin = true
    h.dashboard.pending_validation_count = 2
    renderPage()

    await screen.findByText('Administracion')
    expect(screen.getByLabelText('Validaciones pendientes')).toBeInTheDocument()
    expect(screen.queryByText('Solicitudes de ingreso')).not.toBeInTheDocument()
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

describe('DashboardPage "Mi Perfil" card', () => {
  beforeEach(() => {
    h.user = { role: 'user', name: 'Test' }
    h.dashboard = { membership_notifications: [], is_workshop_admin: false, pending_validation_count: 0, profile_completion: { percent: 60, completed: 10, total: 16 } }
    h.getDashboard.mockReset()
    h.getDashboard.mockResolvedValue(h.dashboard)
  })

  it('renders the Mi Perfil card linking to /profile after data loads', async () => {
    renderPage()
    const heading = await screen.findByText('Mi Perfil')
    const link = heading.closest('a')
    expect(link).toHaveAttribute('href', '/profile')
  })

  it('shows the completion percentage and progress bar', async () => {
    renderPage()
    await screen.findByText('Mi Perfil')
    expect(screen.getByText('60%')).toBeInTheDocument()
    const bar = screen.getByRole('progressbar', { name: 'Completitud del perfil' })
    expect(bar).toHaveAttribute('aria-valuenow', '60')
  })

  it('shows the Mi Perfil card for every role (e.g. superadmin)', async () => {
    h.user = { role: 'superadmin', name: 'Super' }
    renderPage()
    expect(await screen.findByText('Mi Perfil')).toBeInTheDocument()
  })
})

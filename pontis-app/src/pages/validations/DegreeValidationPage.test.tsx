import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render as rtlRender, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import type { ReactElement } from 'react'
import type { UserDegree, UserPosition } from '@/api/profile'
import DegreeValidationPage from './DegreeValidationPage'

// La página embebe ChangeRequestsPage (modo review), que usa el router.
const render = (ui: ReactElement) => rtlRender(<MemoryRouter>{ui}</MemoryRouter>)

const h = vi.hoisted(() => ({
  degrees: [] as UserDegree[],
  positions: [] as UserPosition[],
  user: { id: 1, role: 'user', name: 'Test', admin_workshops: [] as { id: number }[] } as any,
  validateDegree: vi.fn(() => Promise.resolve({} as UserDegree)),
  rejectDegree: vi.fn(() => Promise.resolve({} as UserDegree)),
  validatePosition: vi.fn(() => Promise.resolve({} as UserPosition)),
  rejectPosition: vi.fn(() => Promise.resolve({} as UserPosition)),
}))

vi.mock('@/context/AuthContext', () => ({ useAuth: () => ({ user: h.user }) }))

vi.mock('@/api/profile', () => ({
  getPendingDegreeValidations: () => Promise.resolve({ data: h.degrees }),
  getPendingPositionValidations: () => Promise.resolve({ data: h.positions }),
  validateDegree: h.validateDegree,
  rejectDegree: h.rejectDegree,
  validatePosition: h.validatePosition,
  rejectPosition: h.rejectPosition,
}))

vi.mock('@/api/changeRequests', () => ({
  getChangeRequests: () => Promise.resolve({ data: [], current_page: 1, last_page: 1, total: 0 }),
  createChangeRequest: vi.fn(),
  approveChangeRequest: vi.fn(),
  rejectChangeRequest: vi.fn(),
  requireInfoChangeRequest: vi.fn(),
  cancelChangeRequest: vi.fn(),
}))

vi.mock('@/api/dashboard', () => ({
  getPendingJoinRequests: () => Promise.resolve([]),
  approveJoinRequest: vi.fn(),
  rejectJoinRequest: vi.fn(),
  requestCorrection: vi.fn(),
}))

function degree(overrides: Partial<UserDegree> = {}): UserDegree {
  return {
    id: 1,
    user_id: 10,
    workshop_id: 5,
    degree: 'aprendiz',
    start_date: '2025-01-01',
    end_date: null,
    notes: null,
    validation_status: 'declared',
    validated_at: null,
    validation_notes: null,
    created_at: '2025-02-01',
    workshop: { id: 5, name: 'La Fraternidad', number: 12 },
    user: { id: 10, name: 'Juan', last_name: 'Pérez', email: 'juan@example.com' },
    ...overrides,
  }
}

describe('DegreeValidationPage', () => {
  beforeEach(() => {
    h.degrees = []
    h.positions = []
    h.user = { id: 1, role: 'user', name: 'Test', admin_workshops: [] }
    h.validateDegree.mockClear()
    h.rejectDegree.mockClear()
  })

  it('shows an empty state when there are no pending validations', async () => {
    render(<DegreeValidationPage />)
    expect(await screen.findByText('Sin validaciones pendientes')).toBeInTheDocument()
  })

  it('does not show the sensitive-changes review section to a non-reviewer', async () => {
    render(<DegreeValidationPage />)
    await screen.findByText('Sin validaciones pendientes')
    expect(screen.queryByText('Cambios de datos sensibles')).not.toBeInTheDocument()
  })

  it('shows the sensitive-changes and join-request review sections to a reviewer', async () => {
    h.user = { id: 1, role: 'superadmin', name: 'Super', admin_workshops: [] }
    render(<DegreeValidationPage />)
    expect(await screen.findByText('Cambios de datos sensibles')).toBeInTheDocument()
    expect(await screen.findByText('Solicitudes de ingreso')).toBeInTheDocument()
  })

  it('renders a table row per pending record with the actions', async () => {
    h.degrees = [degree()]
    render(<DegreeValidationPage />)

    expect(await screen.findByText('Pérez, Juan')).toBeInTheDocument()
    expect(screen.getByText(/La Fraternidad/)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Ver' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Aceptar' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Rechazar' })).toBeInTheDocument()
  })

  it('opens the detail modal from Ver and accepts, removing the row', async () => {
    const user = userEvent.setup()
    h.degrees = [degree()]
    render(<DegreeValidationPage />)

    await screen.findByText('Pérez, Juan')
    await user.click(screen.getByRole('button', { name: 'Ver' }))

    // El modal muestra el detalle.
    expect(await screen.findByText('Detalle de validación')).toBeInTheDocument()
    expect(screen.getByText('Tipo de registro')).toBeInTheDocument()

    // Aceptar desde el modal (segundo botón "Aceptar", el del modal).
    const acceptButtons = screen.getAllByRole('button', { name: 'Aceptar' })
    await user.click(acceptButtons[acceptButtons.length - 1])

    expect(h.validateDegree).toHaveBeenCalledWith(1)
    await waitFor(() => expect(screen.queryByText('Pérez, Juan')).not.toBeInTheDocument())
  })

  it('rejects a row from the row action and removes it', async () => {
    const user = userEvent.setup()
    h.degrees = [degree()]
    render(<DegreeValidationPage />)

    await screen.findByText('Pérez, Juan')
    await user.click(screen.getByRole('button', { name: 'Rechazar' }))

    expect(h.rejectDegree).toHaveBeenCalledWith(1)
    await waitFor(() => expect(screen.queryByText('Pérez, Juan')).not.toBeInTheDocument())
  })
})

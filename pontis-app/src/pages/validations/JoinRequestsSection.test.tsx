import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { PendingRequest } from '@/api/dashboard'
import JoinRequestsSection from './JoinRequestsSection'

const h = vi.hoisted(() => ({
  requests: [] as PendingRequest[],
  approveJoinRequest: vi.fn(() => Promise.resolve()),
  rejectJoinRequest: vi.fn(() => Promise.resolve()),
  requestCorrection: vi.fn(() => Promise.resolve()),
}))

vi.mock('@/api/dashboard', () => ({
  getPendingJoinRequests: () => Promise.resolve(h.requests),
  approveJoinRequest: (...a: unknown[]) => h.approveJoinRequest(...a),
  rejectJoinRequest: (...a: unknown[]) => h.rejectJoinRequest(...a),
  requestCorrection: (...a: unknown[]) => h.requestCorrection(...a),
}))

function req(overrides: Partial<PendingRequest> = {}): PendingRequest {
  return {
    user_id: 10,
    user_name: 'Juan',
    user_last_name: 'Pérez',
    user_email: 'juan@example.com',
    user_status: 'pending',
    workshop_id: 5,
    workshop_name: 'La Fraternidad',
    workshop_number: 12,
    membership_status: 'pending',
    correction_notes: null,
    requested_at: '2026-02-01',
    ...overrides,
  }
}

describe('JoinRequestsSection', () => {
  beforeEach(() => {
    h.requests = []
    h.approveJoinRequest.mockClear()
    h.rejectJoinRequest.mockClear()
    h.requestCorrection.mockClear()
  })

  it('shows an empty state when there are no join requests', async () => {
    render(<JoinRequestsSection />)
    expect(await screen.findByText('Sin solicitudes de ingreso')).toBeInTheDocument()
  })

  it('renders a row with Hermano, Taller and the expected actions', async () => {
    h.requests = [req()]
    render(<JoinRequestsSection />)

    expect(await screen.findByText('Pérez, Juan')).toBeInTheDocument()
    expect(screen.getByText(/La Fraternidad/)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Ver' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Pedir corrección' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Rechazar' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Aceptar' })).toBeInTheDocument()
  })

  it('accepts a join request and removes the row', async () => {
    const user = userEvent.setup()
    h.requests = [req()]
    render(<JoinRequestsSection />)

    await screen.findByText('Pérez, Juan')
    await user.click(screen.getByRole('button', { name: 'Aceptar' }))

    expect(h.approveJoinRequest).toHaveBeenCalledWith(5, 10)
    await waitFor(() => expect(screen.queryByText('Pérez, Juan')).not.toBeInTheDocument())
  })

  it('opens the detail modal from Ver', async () => {
    const user = userEvent.setup()
    h.requests = [req()]
    render(<JoinRequestsSection />)

    await screen.findByText('Pérez, Juan')
    await user.click(screen.getByRole('button', { name: 'Ver' }))

    expect(await screen.findByText('Información del usuario')).toBeInTheDocument()
    expect(screen.getByText('juan@example.com')).toBeInTheDocument()
  })

  it('requests correction and keeps the row marked as correction-requested', async () => {
    const user = userEvent.setup()
    h.requests = [req()]
    render(<JoinRequestsSection />)

    await screen.findByText('Pérez, Juan')
    await user.click(screen.getByRole('button', { name: 'Pedir corrección' }))

    const textarea = await screen.findByRole('textbox')
    await user.type(textarea, 'Actualizá tu DNI')
    await user.click(screen.getByRole('button', { name: 'Enviar solicitud de corrección' }))

    expect(h.requestCorrection).toHaveBeenCalledWith(5, 10, 'Actualizá tu DNI')
    await waitFor(() => expect(screen.getByText('Corrección solicitada')).toBeInTheDocument())
  })
})

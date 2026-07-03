import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import type { ChangeRequest } from '@/api/changeRequests'
import ChangeRequestsPage from './ChangeRequestsPage'

const h = vi.hoisted(() => ({
  user: { id: 1, role: 'user', name: 'Test', admin_workshops: [] as { id: number }[] } as any,
  requests: [] as ChangeRequest[],
}))

vi.mock('@/context/AuthContext', () => ({ useAuth: () => ({ user: h.user }) }))

vi.mock('@/api/changeRequests', () => ({
  getChangeRequests: () => Promise.resolve({ data: h.requests, current_page: 1, last_page: 1, total: h.requests.length }),
  createChangeRequest: vi.fn(() => Promise.resolve({} as ChangeRequest)),
  approveChangeRequest: vi.fn(() => Promise.resolve({} as ChangeRequest)),
  rejectChangeRequest: vi.fn(() => Promise.resolve({} as ChangeRequest)),
  requireInfoChangeRequest: vi.fn(() => Promise.resolve({} as ChangeRequest)),
  cancelChangeRequest: vi.fn(() => Promise.resolve({} as ChangeRequest)),
}))

function request(overrides: Partial<ChangeRequest> = {}): ChangeRequest {
  return {
    id: 1,
    user_id: 99,
    field: 'dni',
    current_value: '111',
    new_value: '222',
    reason: null,
    status: 'pending',
    reviewer_notes: null,
    reviewed_at: null,
    created_at: '2026-01-01',
    user: { id: 99, name: 'Owner', last_name: 'Uno', email: 'owner@example.com' },
    ...overrides,
  }
}

describe('ChangeRequestsPage reviewer controls', () => {
  beforeEach(() => {
    h.requests = [request()]
    h.user = { id: 1, role: 'user', name: 'Test', admin_workshops: [] }
  })

  it('shows review action to an Admin de Taller', async () => {
    h.user = { id: 1, role: 'user', name: 'Admin', admin_workshops: [{ id: 5 }] }
    render(<ChangeRequestsPage embedded />)
    expect(await screen.findByRole('button', { name: 'Revisar' })).toBeInTheDocument()
    expect(screen.getByText(/owner@example.com/)).toBeInTheDocument()
  })

  it('shows review action to a Superadmin', async () => {
    h.user = { id: 1, role: 'superadmin', name: 'Super', admin_workshops: [] }
    render(<ChangeRequestsPage embedded />)
    expect(await screen.findByRole('button', { name: 'Revisar' })).toBeInTheDocument()
  })

  it('hides review action from a regular Hermano and keeps cancel on own request', async () => {
    h.user = { id: 99, role: 'user', name: 'Owner', admin_workshops: [] }
    h.requests = [request({ user_id: 99 })]
    render(<ChangeRequestsPage embedded />)
    await waitFor(() => expect(screen.getByText('DNI / Documento')).toBeInTheDocument())
    expect(screen.queryByRole('button', { name: 'Revisar' })).not.toBeInTheDocument()
    expect(screen.getByText('Cancelar solicitud')).toBeInTheDocument()
  })
})

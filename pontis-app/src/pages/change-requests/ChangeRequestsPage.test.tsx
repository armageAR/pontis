import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import type { ChangeRequest } from '@/api/changeRequests'
import ChangeRequestsPage from './ChangeRequestsPage'

const h = vi.hoisted(() => ({
  user: { id: 1, role: 'user', name: 'Test', admin_workshops: [] as { id: number }[] } as any,
  requests: [] as ChangeRequest[],
  getChangeRequests: vi.fn((_params?: { status?: string; mine?: boolean }) =>
    Promise.resolve({ data: h.requests, current_page: 1, last_page: 1, total: h.requests.length })),
}))

vi.mock('@/context/AuthContext', () => ({ useAuth: () => ({ user: h.user }) }))

vi.mock('@/api/changeRequests', () => ({
  getChangeRequests: (p?: { status?: string; mine?: boolean }) => h.getChangeRequests(p),
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

describe('ChangeRequestsPage', () => {
  beforeEach(() => {
    h.requests = [request()]
    h.user = { id: 1, role: 'user', name: 'Test', admin_workshops: [] }
    h.getChangeRequests.mockClear()
  })

  it('review mode shows reviewer controls and requester identity', async () => {
    render(<ChangeRequestsPage embedded mode="review" />)
    expect(await screen.findByRole('button', { name: 'Revisar' })).toBeInTheDocument()
    expect(screen.getByText(/owner@example.com/)).toBeInTheDocument()
    // review mode never scopes to the caller's own requests
    expect(h.getChangeRequests).toHaveBeenCalledWith(expect.objectContaining({ mine: undefined }))
    expect(screen.queryByRole('button', { name: '+ Solicitar cambio' })).not.toBeInTheDocument()
  })

  it('self mode hides reviewer controls and requests only own trámites', async () => {
    h.requests = [request({ user_id: 1 })]
    render(<ChangeRequestsPage embedded mode="self" />)
    await waitFor(() => expect(screen.getByText('DNI / Documento')).toBeInTheDocument())
    expect(screen.queryByRole('button', { name: 'Revisar' })).not.toBeInTheDocument()
    expect(screen.getByText('Cancelar solicitud')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: '+ Solicitar cambio' })).toBeInTheDocument()
    expect(h.getChangeRequests).toHaveBeenCalledWith(expect.objectContaining({ mine: true }))
    // the requester identity line is not shown in self mode
    expect(screen.queryByText(/owner@example.com/)).not.toBeInTheDocument()
  })
})

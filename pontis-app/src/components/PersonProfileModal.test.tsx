import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import PersonProfileModal from './PersonProfileModal'

const h = vi.hoisted(() => ({
  me: { id: 1, role: 'user', name: 'Yo' } as { id: number; role: string; name: string },
  profile: {} as Record<string, unknown>,
  get: vi.fn(),
  createContactRequest: vi.fn(() => Promise.resolve({})),
}))

vi.mock('@/context/AuthContext', () => ({ useAuth: () => ({ user: h.me }) }))
vi.mock('@/api/client', () => ({ default: { get: (url: string) => h.get(url) } }))
vi.mock('@/api/contactRequests', () => ({ createContactRequest: (p: unknown) => h.createContactRequest(p) }))
vi.mock('@/api/profile', () => ({ getContactConsent: () => Promise.resolve({ default_shared_fields: ['identity'], preferred_channels: [], allowed_sources: ['search'] }) }))

function profile(overrides: Record<string, unknown> = {}) {
  return { id: 99, name: 'Juan', last_name: 'Pérez', anonymous: false, can_request_contact: true, workshops: [], degrees: [], positions: [], ...overrides }
}

function renderModal() {
  render(<PersonProfileModal personId={99} open onClose={vi.fn()} />)
}

describe('PersonProfileModal contact action', () => {
  beforeEach(() => {
    h.me = { id: 1, role: 'user', name: 'Yo' }
    h.get.mockReset()
    h.createContactRequest.mockClear()
    h.get.mockResolvedValue({ data: profile() })
  })

  it('shows "Solicitar contacto" when allowed and not self', async () => {
    renderModal()
    expect(await screen.findByRole('button', { name: 'Solicitar contacto' })).toBeEnabled()
  })

  it('disables the action with an explanation when can_request_contact is false', async () => {
    h.get.mockResolvedValue({ data: profile({ can_request_contact: false }) })
    renderModal()
    const btn = await screen.findByRole('button', { name: 'Solicitar contacto' })
    expect(btn).toBeDisabled()
    expect(btn).toHaveAttribute('title', 'Este Hermano no acepta solicitudes de contacto desde búsquedas.')
  })

  it('hides the action on the viewer\'s own profile', async () => {
    h.get.mockResolvedValue({ data: profile({ id: 1 }) })
    renderModal()
    await screen.findByText(/Pérez/)
    expect(screen.queryByRole('button', { name: 'Solicitar contacto' })).not.toBeInTheDocument()
  })

  it('opens the contact form and, on send, keeps the modal open showing confirmation with source=search', async () => {
    const user = userEvent.setup()
    renderModal()

    await user.click(await screen.findByRole('button', { name: 'Solicitar contacto' }))
    // Contact form is now shown in-place.
    await user.type(await screen.findByRole('textbox'), 'Hola')
    await user.selectOptions(screen.getByRole('combobox'), 'profession_search')
    await user.click(screen.getByRole('button', { name: 'Enviar solicitud' }))

    await waitFor(() => expect(h.createContactRequest).toHaveBeenCalledWith(expect.objectContaining({ requestee_id: 99, source: 'search' })))
    // Modal stays open with confirmation.
    expect(await screen.findByText(/Solicitud enviada/)).toBeInTheDocument()
  })

  it('lets an anonymous profile be contacted via its real id', async () => {
    h.get.mockResolvedValue({ data: profile({ anonymous: true, name: 'Hermano registrado', last_name: null }) })
    renderModal()
    expect(await screen.findByRole('button', { name: 'Solicitar contacto' })).toBeEnabled()
  })
})

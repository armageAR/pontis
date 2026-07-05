import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import ContactRequestForm from './ContactRequestForm'

const h = vi.hoisted(() => ({
  createContactRequest: vi.fn(() => Promise.resolve({})),
  getContactConsent: vi.fn(() => Promise.resolve({ default_shared_fields: ['identity'], preferred_channels: [], allowed_sources: ['search'] })),
}))

vi.mock('@/api/contactRequests', () => ({ createContactRequest: (p: unknown) => h.createContactRequest(p) }))
vi.mock('@/api/profile', () => ({ getContactConsent: () => h.getContactConsent() }))

function renderForm() {
  const onSent = vi.fn()
  const onCancel = vi.fn()
  render(<ContactRequestForm requesteeId={42} requesteeName="Juan" onSent={onSent} onCancel={onCancel} />)
  return { onSent, onCancel }
}

describe('ContactRequestForm', () => {
  beforeEach(() => { h.createContactRequest.mockClear() })

  it('has no effective default reason (placeholder selected)', async () => {
    renderForm()
    const select = await screen.findByRole('combobox')
    expect((select as HTMLSelectElement).value).toBe('')
    expect(screen.getByRole('option', { name: 'Seleccioná un motivo' })).toBeInTheDocument()
  })

  it('submits with source=search and the chosen reason', async () => {
    const user = userEvent.setup()
    const { onSent } = renderForm()

    await user.type(screen.getByRole('textbox'), 'Hola, quiero contactarte.')
    await user.selectOptions(screen.getByRole('combobox'), 'profession_search')
    await user.click(screen.getByRole('button', { name: 'Enviar solicitud' }))

    await waitFor(() => expect(h.createContactRequest).toHaveBeenCalledTimes(1))
    expect(h.createContactRequest).toHaveBeenCalledWith(expect.objectContaining({
      requestee_id: 42,
      source: 'search',
      reason_type: 'profession_search',
      message: 'Hola, quiero contactarte.',
    }))
    expect(onSent).toHaveBeenCalled()
  })
})

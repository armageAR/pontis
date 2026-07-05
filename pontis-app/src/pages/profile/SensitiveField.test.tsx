import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import SensitiveField from './SensitiveField'

const h = vi.hoisted(() => ({ navigate: vi.fn() }))

vi.mock('react-router-dom', async (orig) => {
  const actual = await orig<typeof import('react-router-dom')>()
  return { ...actual, useNavigate: () => h.navigate }
})

function renderField(props: Partial<React.ComponentProps<typeof SensitiveField>> = {}) {
  const onCommit = vi.fn(() => Promise.resolve())
  render(
    <MemoryRouter>
      <SensitiveField label="DNI / Documento" fieldKey="dni" value="12345678" isSuperAdmin={false} onCommit={onCommit} {...props} />
    </MemoryRouter>,
  )
  return { onCommit }
}

describe('SensitiveField (non-superadmin)', () => {
  beforeEach(() => h.navigate.mockClear())

  it('renders as static text, not an editable input', () => {
    renderField()
    expect(screen.queryByRole('textbox')).not.toBeInTheDocument()
    expect(screen.getByText('12345678')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Solicitar cambio de DNI / Documento' })).toBeInTheDocument()
  })

  it('opens the confirm dialog and navigates to the trámite with the field preselected', async () => {
    const user = userEvent.setup()
    const { onCommit } = renderField()
    await user.click(screen.getByRole('button', { name: 'Solicitar cambio de DNI / Documento' }))
    // ConfirmDialog visible
    const confirm = await screen.findByRole('button', { name: 'Solicitar cambio' })
    await user.click(confirm)
    expect(h.navigate).toHaveBeenCalledWith('/bandeja?tramite=dni')
    // never edits the value directly
    expect(onCommit).not.toHaveBeenCalled()
  })
})

describe('SensitiveField (superadmin)', () => {
  it('renders an editable inline field for superadmin', () => {
    const onCommit = vi.fn(() => Promise.resolve())
    render(
      <MemoryRouter>
        <SensitiveField label="DNI / Documento" fieldKey="dni" value="12345678" isSuperAdmin onCommit={onCommit} />
      </MemoryRouter>,
    )
    expect(screen.getByRole('textbox')).toBeInTheDocument()
  })
})

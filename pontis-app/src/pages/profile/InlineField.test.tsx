import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import InlineField from './InlineField'

describe('InlineField', () => {
  it('commits the value on blur', async () => {
    const user = userEvent.setup()
    const onCommit = vi.fn(() => Promise.resolve())
    render(<InlineField label="Teléfono" value="" onCommit={onCommit} />)
    const input = screen.getByRole('textbox')
    await user.type(input, '1122334455')
    await user.tab()
    expect(onCommit).toHaveBeenCalledWith('1122334455')
  })

  it('commits on Enter for single-line inputs', async () => {
    const user = userEvent.setup()
    const onCommit = vi.fn(() => Promise.resolve())
    render(<InlineField label="Teléfono" value="" onCommit={onCommit} />)
    await user.type(screen.getByRole('textbox'), '999{Enter}')
    expect(onCommit).toHaveBeenCalledWith('999')
  })

  it('does not commit and shows an inline error for an invalid email', async () => {
    const user = userEvent.setup()
    const onCommit = vi.fn(() => Promise.resolve())
    render(<InlineField label="Email alternativo" type="email" validate="email" value="" onCommit={onCommit} />)
    await user.type(screen.getByRole('textbox'), 'no-es-email')
    await user.tab()
    expect(onCommit).not.toHaveBeenCalled()
    expect(screen.getByText('Ingresá un email válido.')).toBeInTheDocument()
  })

  it('shows the saving status from the status prop', () => {
    render(<InlineField label="Bio" value="hola" status="saving" onCommit={vi.fn()} />)
    expect(screen.getByText('Guardando…')).toBeInTheDocument()
  })
})

import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import ContactoTab from './ContactoTab'
import type { Profile, ContactConsentSettings } from '@/api/profile'

const profile = { phone: '', phone_fixed: '', whatsapp: '', alternative_email: '', linkedin: '', website: '', instagram: '', facebook: '', availability_notes: '' } as unknown as Profile

const consent: ContactConsentSettings = {
  default_shared_fields: ['identity', 'email'],
  preferred_channels: ['in_flow'],
  allowed_sources: ['search'],
}

function setup() {
  const toggleContactSource = vi.fn()
  render(
    <ContactoTab
      profile={profile}
      statuses={{}}
      commitField={vi.fn(() => Promise.resolve())}
      sectionPrivacy={() => null}
      contactConsent={consent}
      savingContactConsent={false}
      toggleContactSource={toggleContactSource}
    />,
  )
  return { toggleContactSource }
}

describe('ContactoTab consent block', () => {
  it('shows only the simplified "Solicitudes de contacto" block with two sources', () => {
    setup()
    expect(screen.getByText('Solicitudes de contacto')).toBeInTheDocument()
    expect(screen.getByText('Desde búsquedas de Hermanos')).toBeInTheDocument()
    expect(screen.getByText('Desde publicaciones')).toBeInTheDocument()
    // Removed sub-sections must be gone
    expect(screen.queryByText('Datos que comparto por defecto')).not.toBeInTheDocument()
    expect(screen.queryByText('Canales preferidos')).not.toBeInTheDocument()
  })

  it('reflects current allowed_sources and toggles a source', async () => {
    const user = userEvent.setup()
    const { toggleContactSource } = setup()
    const checks = screen.getAllByRole('checkbox')
    expect(checks[0]).toBeChecked()     // search
    expect(checks[1]).not.toBeChecked() // publications
    await user.click(checks[1])
    expect(toggleContactSource).toHaveBeenCalledWith('publications')
  })
})

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import AdministracionPage from './AdministracionPage'

const h = vi.hoisted(() => ({ user: { role: 'user', name: 'Test' } as { role: string; name: string } }))

vi.mock('@/context/AuthContext', () => ({ useAuth: () => ({ user: h.user }) }))
vi.mock('@/components/AppLayout', () => ({ default: ({ children }: { children: React.ReactNode }) => <div>{children}</div> }))
vi.mock('@/pages/users/UsersPage', () => ({ default: () => <div>UsersPageStub</div> }))
vi.mock('@/pages/audit/AuditLogPage', () => ({ default: () => <div>AuditStub</div> }))
vi.mock('@/pages/validations/DegreeValidationPage', () => ({ default: () => <div>ValidacionesStub</div> }))

describe('AdministracionPage tabs', () => {
  beforeEach(() => { h.user = { role: 'user', name: 'Test' } })

  it('hides the Hermanos tab for a non-superadmin (Admin de Taller)', () => {
    render(<AdministracionPage />)
    expect(screen.queryByRole('button', { name: 'Hermanos' })).not.toBeInTheDocument()
    // Su vista por defecto es Validaciones.
    expect(screen.getByText('ValidacionesStub')).toBeInTheDocument()
  })

  it('shows the Hermanos tab for a superadmin', () => {
    h.user = { role: 'superadmin', name: 'Super' }
    render(<AdministracionPage />)
    expect(screen.getByRole('button', { name: 'Hermanos' })).toBeInTheDocument()
    expect(screen.getByText('UsersPageStub')).toBeInTheDocument()
  })
})

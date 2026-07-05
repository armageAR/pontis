import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { vi } from 'vitest'
import SearchPage from './SearchPage'

vi.mock('@/components/AppLayout', () => ({ default: ({ children }: { children: React.ReactNode }) => <div>{children}</div> }))
vi.mock('@/pages/people/PeoplePage', () => ({ default: () => <div>PeopleStub</div> }))
vi.mock('./WorkshopDirectoryTab', () => ({ default: () => <div>WorkshopsStub</div> }))

describe('SearchPage (publications deferred to V2)', () => {
  it('shows only Hermanos and Talleres tabs, not publication exploration', () => {
    render(<SearchPage />)
    expect(screen.getByRole('button', { name: 'Hermanos' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Talleres' })).toBeInTheDocument()
    // El tab de exploración de publicaciones queda diferido a V2.
    expect(screen.queryByRole('button', { name: 'Servicios y necesidades' })).not.toBeInTheDocument()
  })
})

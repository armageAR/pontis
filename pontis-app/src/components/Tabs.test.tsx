import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import Tabs from './Tabs'

const TABS = [
  { key: 'a', label: 'Uno' },
  { key: 'b', label: 'Dos' },
  { key: 'c', label: 'Tres' },
]

describe('Tabs', () => {
  it('renders a tablist with the active tab selected', () => {
    render(<Tabs tabs={TABS} active="a" onChange={vi.fn()} />)
    expect(screen.getByRole('tablist')).toBeInTheDocument()
    expect(screen.getByRole('tab', { name: 'Uno' })).toHaveAttribute('aria-selected', 'true')
    expect(screen.getByRole('tab', { name: 'Dos' })).toHaveAttribute('aria-selected', 'false')
  })

  it('calls onChange when a tab is clicked', async () => {
    const user = userEvent.setup()
    const onChange = vi.fn()
    render(<Tabs tabs={TABS} active="a" onChange={onChange} />)
    await user.click(screen.getByRole('tab', { name: 'Dos' }))
    expect(onChange).toHaveBeenCalledWith('b')
  })

  it('moves selection with the ArrowRight key', async () => {
    const user = userEvent.setup()
    const onChange = vi.fn()
    render(<Tabs tabs={TABS} active="a" onChange={onChange} />)
    screen.getByRole('tab', { name: 'Uno' }).focus()
    await user.keyboard('{ArrowRight}')
    expect(onChange).toHaveBeenCalledWith('b')
  })

  it('wraps to the first tab with ArrowRight from the last', async () => {
    const user = userEvent.setup()
    const onChange = vi.fn()
    render(<Tabs tabs={TABS} active="c" onChange={onChange} />)
    screen.getByRole('tab', { name: 'Tres' }).focus()
    await user.keyboard('{ArrowRight}')
    expect(onChange).toHaveBeenCalledWith('a')
  })
})

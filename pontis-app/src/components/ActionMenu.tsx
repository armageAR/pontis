import type { ReactNode } from 'react'
import './ActionMenu.css'

export interface Action {
  icon: ReactNode
  label: string
  onClick: () => void
  disabled?: boolean
  danger?: boolean
}

interface ActionMenuProps {
  actions: Action[]
}

export default function ActionMenu({ actions }: ActionMenuProps) {
  return (
    <div className="action-menu">
      {actions.map((action, i) => (
        <button
          key={i}
          type="button"
          className={`action-btn ${action.danger ? 'action-btn-danger' : ''}`}
          onClick={action.onClick}
          disabled={action.disabled}
          aria-label={action.label}
          title={action.label}
        >
          {action.icon}
        </button>
      ))}
    </div>
  )
}

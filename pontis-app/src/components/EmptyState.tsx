import { Hexagon } from 'lucide-react'
import type { ReactNode } from 'react'
import './EmptyState.css'

interface EmptyStateProps {
  icon?: ReactNode
  title: string
  description?: string
  children?: ReactNode
}

export default function EmptyState({ icon, title, description, children }: EmptyStateProps) {
  return (
    <div className="empty-state">
      <span className="empty-state-icon">{icon ?? <Hexagon size={36} />}</span>
      <h3 className="empty-state-title">{title}</h3>
      {description && <p className="empty-state-desc">{description}</p>}
      {children}
    </div>
  )
}

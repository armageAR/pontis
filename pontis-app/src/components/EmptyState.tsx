import './EmptyState.css'

interface EmptyStateProps {
  icon?: string
  title: string
  description?: string
  children?: React.ReactNode
}

export default function EmptyState({ icon = '⬡', title, description, children }: EmptyStateProps) {
  return (
    <div className="empty-state">
      <span className="empty-state-icon">{icon}</span>
      <h3 className="empty-state-title">{title}</h3>
      {description && <p className="empty-state-desc">{description}</p>}
      {children}
    </div>
  )
}

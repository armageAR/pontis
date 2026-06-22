import './Badge.css'

interface BadgeProps {
  variant?: 'default' | 'success' | 'error' | 'warning'
  children: React.ReactNode
}

export default function Badge({ variant = 'default', children }: BadgeProps) {
  return <span className={`badge badge-${variant}`}>{children}</span>
}

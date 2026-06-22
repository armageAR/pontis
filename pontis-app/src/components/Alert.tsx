import './Alert.css'

interface AlertProps {
  variant?: 'error' | 'success'
  children: React.ReactNode
}

export default function Alert({ variant = 'error', children }: AlertProps) {
  return <div className={`alert alert-${variant}`}>{children}</div>
}

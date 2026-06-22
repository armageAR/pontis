import './Spinner.css'

interface SpinnerProps {
  size?: number
  className?: string
}

export default function Spinner({ size = 20, className = '' }: SpinnerProps) {
  return (
    <span
      className={`spinner ${className}`}
      style={{ width: size, height: size }}
      role="status"
      aria-label="Cargando"
    />
  )
}

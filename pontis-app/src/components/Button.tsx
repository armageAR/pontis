import type { ButtonHTMLAttributes } from 'react'
import Spinner from './Spinner'
import './Button.css'

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'outline' | 'ghost'
  loading?: boolean
}

export default function Button({
  variant = 'primary',
  loading = false,
  disabled,
  children,
  className = '',
  ...rest
}: ButtonProps) {
  return (
    <button
      className={`btn btn-${variant} ${loading ? 'btn-loading' : ''} ${className}`}
      disabled={disabled || loading}
      {...rest}
    >
      {loading && <Spinner size={16} />}
      <span className={loading ? 'btn-label-hidden' : ''}>{children}</span>
    </button>
  )
}

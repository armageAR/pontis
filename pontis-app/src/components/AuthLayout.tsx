import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import './AuthLayout.css'

interface AuthLayoutProps {
  title: string
  subtitle?: string
  children: ReactNode
  footer?: ReactNode
}

export default function AuthLayout({ title, subtitle, children, footer }: AuthLayoutProps) {
  return (
    <div className="auth-layout">
      <div className="auth-card">
        <Link to="/" className="auth-logo">
          Pontis
        </Link>
        <h1 className="auth-title">{title}</h1>
        {subtitle && <p className="auth-subtitle">{subtitle}</p>}
        {children}
        {footer && <div className="auth-footer">{footer}</div>}
      </div>
    </div>
  )
}

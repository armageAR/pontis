import type { ReactNode } from 'react'
import './FormField.css'

interface FormFieldProps {
  label: string
  error?: string
  children: ReactNode
}

export default function FormField({ label, error, children }: FormFieldProps) {
  return (
    <div className="form-field">
      <label className="form-label">{label}</label>
      {children}
      {error && <span className="form-error">{error}</span>}
    </div>
  )
}

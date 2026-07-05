import type { ReactNode } from 'react'
import { Check, AlertCircle, Lock } from 'lucide-react'
import Spinner from './Spinner'
import './FormField.css'

export type FormFieldStatus = 'saving' | 'saved' | 'error' | 'locked'

interface FormFieldProps {
  label: string
  error?: string
  status?: FormFieldStatus
  htmlFor?: string
  children: ReactNode
}

export default function FormField({ label, error, status, htmlFor, children }: FormFieldProps) {
  return (
    <div className="form-field">
      <div className="form-field-head">
        <label className="form-label" htmlFor={htmlFor}>{label}</label>
        <span className="form-field-status" aria-live="polite" aria-atomic="true">
          {status === 'saving' && (
            <>
              <Spinner size={12} />
              <span className="form-field-status-text">Guardando…</span>
            </>
          )}
          {status === 'saved' && (
            <>
              <Check size={13} className="form-field-status-ok" aria-hidden="true" />
              <span className="sr-only">Guardado</span>
            </>
          )}
          {status === 'error' && (
            <>
              <AlertCircle size={13} className="form-field-status-err" aria-hidden="true" />
              <span className="sr-only">Error al guardar</span>
            </>
          )}
          {status === 'locked' && (
            <>
              <Lock size={13} className="form-field-status-lock" aria-hidden="true" />
              <span className="sr-only">Campo bloqueado</span>
            </>
          )}
        </span>
      </div>
      {children}
      {error && <span className="form-error">{error}</span>}
    </div>
  )
}

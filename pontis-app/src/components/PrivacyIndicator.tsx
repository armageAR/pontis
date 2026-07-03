import { useState } from 'react'
import './PrivacyIndicator.css'

interface PrivacyIndicatorProps {
  summary: string
  details?: string[]
}

export default function PrivacyIndicator({ summary, details = [] }: PrivacyIndicatorProps) {
  const [open, setOpen] = useState(false)
  const hasDetails = details.length > 0

  return (
    <div className="privacy-indicator">
      <div className="privacy-indicator-main">
        <span className="privacy-indicator-dot" aria-hidden="true" />
        <span>{summary}</span>
        {hasDetails && (
          <button type="button" className="privacy-indicator-toggle" onClick={() => setOpen(v => !v)}>
            {open ? 'Ocultar detalle' : 'Ver detalle'}
          </button>
        )}
      </div>
      {open && hasDetails && (
        <ul className="privacy-indicator-details">
          {details.map(item => <li key={item}>{item}</li>)}
        </ul>
      )}
    </div>
  )
}

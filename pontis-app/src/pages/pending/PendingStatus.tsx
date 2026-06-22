import { useEffect, useState } from 'react'
import { useAuth } from '@/context/AuthContext'
import * as authApi from '@/api/auth'
import type { AccountStatus } from '@/api/auth'
import Button from '@/components/Button'
import Alert from '@/components/Alert'
import Spinner from '@/components/Spinner'
import './PendingStatus.css'

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function hoursAgo(dateStr: string): number {
  return (Date.now() - new Date(dateStr).getTime()) / (1000 * 60 * 60)
}

export default function PendingStatus() {
  const { refreshUser } = useAuth()
  const [status, setStatus] = useState<AccountStatus | null>(null)
  const [loading, setLoading] = useState(true)
  const [resending, setResending] = useState(false)
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')

  useEffect(() => {
    authApi
      .getAccountStatus()
      .then(setStatus)
      .catch(() => setError('No se pudo obtener el estado de la cuenta.'))
      .finally(() => setLoading(false))
  }, [])

  async function handleResend() {
    setResending(true)
    setMessage('')
    setError('')
    try {
      const { message } = await authApi.resendVerification()
      setMessage(message)
      const updated = await authApi.getAccountStatus()
      setStatus(updated)
    } catch {
      setError('No se pudo reenviar el email. Intentá de nuevo.')
    } finally {
      setResending(false)
    }
  }

  async function handleRefreshStatus() {
    setLoading(true)
    try {
      await refreshUser()
      const updated = await authApi.getAccountStatus()
      setStatus(updated)
    } catch {
      setError('No se pudo actualizar el estado.')
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return (
      <div className="pending-loading">
        <Spinner size={28} />
      </div>
    )
  }

  const canResend = status && !status.email_verified && hoursAgo(status.verification_sent_at) >= 24

  return (
    <div className="pending-status">
      {error && <Alert variant="error">{error}</Alert>}
      {message && <Alert variant="success">{message}</Alert>}

      <div className="pending-icon">⏳</div>

      <h2 className="pending-title">Cuenta pendiente de aprobación</h2>

      <p className="pending-text">
        Tu cuenta fue creada pero aún no fue aprobada por un administrador.
      </p>

      {status && !status.email_verified && (
        <div className="pending-verification">
          <div className="pending-verification-badge">Email no verificado</div>
          <p className="pending-text">
            Tu email debe estar verificado para que se inicie el proceso de aprobación.
            Revisá tu bandeja de entrada y la carpeta de spam.
          </p>
          <p className="pending-detail">
            Email de verificación enviado el{' '}
            <strong>{formatDate(status.verification_sent_at)}</strong>
          </p>

          {canResend ? (
            <Button onClick={handleResend} loading={resending} variant="primary">
              Reenviar email de verificación
            </Button>
          ) : (
            <p className="pending-detail pending-muted">
              Podrás reenviar el email de verificación 24 horas después del último envío.
            </p>
          )}
        </div>
      )}

      {status?.email_verified && (
        <div className="pending-verification">
          <div className="pending-verification-badge pending-badge-ok">Email verificado</div>
          <p className="pending-text">
            Tu email fue verificado el <strong>{formatDate(status.email_verified_at!)}</strong>.
            Un administrador revisará tu cuenta a la brevedad.
          </p>
        </div>
      )}

      <Button variant="ghost" onClick={handleRefreshStatus}>
        Actualizar estado
      </Button>
    </div>
  )
}

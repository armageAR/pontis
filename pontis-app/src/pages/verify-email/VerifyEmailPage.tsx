import { useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useAuth } from '@/context/AuthContext'
import * as authApi from '@/api/auth'
import AuthLayout from '@/components/AuthLayout'
import Spinner from '@/components/Spinner'
import Alert from '@/components/Alert'
import Button from '@/components/Button'
import './VerifyEmailPage.css'

export default function VerifyEmailPage() {
  const { refreshUser } = useAuth()
  const [params] = useSearchParams()
  const verifyUrl = params.get('verify_url')

  const [loading, setLoading] = useState(true)
  const [success, setSuccess] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    if (!verifyUrl) {
      setError('Link de verificación inválido.')
      setLoading(false)
      return
    }

    authApi
      .verifyEmail(verifyUrl)
      .then(() => {
        setSuccess(true)
        refreshUser()
      })
      .catch((err) => {
        const msg = err?.response?.data?.message ?? 'No se pudo verificar el email.'
        setError(msg)
      })
      .finally(() => setLoading(false))
  }, [verifyUrl, refreshUser])

  return (
    <AuthLayout title="Verificación de email">
      <div className="verify-email-content">
        {loading && (
          <div className="verify-email-loading">
            <Spinner size={28} />
            <p>Verificando tu email...</p>
          </div>
        )}

        {!loading && success && (
          <>
            <div className="verify-email-icon">✓</div>
            <Alert variant="success">Tu email fue verificado correctamente.</Alert>
            <p className="verify-email-text">
              Un administrador revisará tu cuenta. Te notificaremos cuando esté aprobada.
            </p>
            <Link to="/pending">
              <Button>Ver estado de mi cuenta</Button>
            </Link>
          </>
        )}

        {!loading && error && (
          <>
            <Alert variant="error">{error}</Alert>
            <p className="verify-email-text">
              El enlace puede haber expirado. Iniciá sesión y solicitá un nuevo email de verificación.
            </p>
            <Link to="/login">
              <Button variant="outline">Ir a iniciar sesión</Button>
            </Link>
          </>
        )}
      </div>
    </AuthLayout>
  )
}

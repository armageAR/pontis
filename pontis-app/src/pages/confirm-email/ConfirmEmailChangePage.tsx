import { useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { CheckCircle } from 'lucide-react'
import { useAuth } from '@/context/AuthContext'
import * as authApi from '@/api/auth'
import AuthLayout from '@/components/AuthLayout'
import Spinner from '@/components/Spinner'
import Alert from '@/components/Alert'
import Button from '@/components/Button'
import '@/pages/verify-email/VerifyEmailPage.css'

export default function ConfirmEmailChangePage() {
  const { user, refreshUser } = useAuth()
  const [params] = useSearchParams()
  const confirmUrl = params.get('confirm_url')

  const [loading, setLoading] = useState(true)
  const [success, setSuccess] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    if (!confirmUrl) {
      setError('Link de confirmación inválido.')
      setLoading(false)
      return
    }

    authApi
      .confirmEmailChange(confirmUrl)
      .then(() => {
        setSuccess(true)
        refreshUser()
      })
      .catch((err) => {
        const msg = err?.response?.data?.message ?? 'No se pudo confirmar el nuevo email.'
        setError(msg)
      })
      .finally(() => setLoading(false))
  }, [confirmUrl, refreshUser])

  return (
    <AuthLayout title="Confirmación de email">
      <div className="verify-email-content">
        {loading && (
          <div className="verify-email-loading">
            <Spinner size={28} />
            <p>Confirmando tu nuevo email...</p>
          </div>
        )}

        {!loading && success && (
          <>
            <div className="verify-email-icon">
              <CheckCircle size={48} strokeWidth={1.5} />
            </div>
            <Alert variant="success">Tu nuevo email fue confirmado correctamente.</Alert>
            <p className="verify-email-text">
              Ya podés usar tu nueva dirección para iniciar sesión.
            </p>
            <Link to={user ? '/profile' : '/login'}>
              <Button>{user ? 'Ir a mi perfil' : 'Ir a iniciar sesión'}</Button>
            </Link>
          </>
        )}

        {!loading && error && (
          <>
            <Alert variant="error">{error}</Alert>
            <p className="verify-email-text">
              El enlace puede haber expirado. Volvé a tu perfil y solicitá el cambio nuevamente.
            </p>
            <Link to={user ? '/profile' : '/login'}>
              <Button variant="outline">{user ? 'Ir a mi perfil' : 'Ir a iniciar sesión'}</Button>
            </Link>
          </>
        )}
      </div>
    </AuthLayout>
  )
}

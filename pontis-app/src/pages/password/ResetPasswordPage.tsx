import { type FormEvent, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import type { AxiosError } from 'axios'
import AuthLayout from '@/components/AuthLayout'
import Alert from '@/components/Alert'
import Button from '@/components/Button'
import FormField from '@/components/FormField'
import Input from '@/components/Input'
import { resetPassword } from '@/api/auth'

interface FieldErrors {
  email?: string[]
  token?: string[]
  password?: string[]
}

export default function ResetPasswordPage() {
  const [params] = useSearchParams()
  const [email, setEmail] = useState(params.get('email') ?? '')
  const [token] = useState(params.get('token') ?? '')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({})
  const [loading, setLoading] = useState(false)

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')
    setMessage('')
    setFieldErrors({})
    setLoading(true)
    try {
      const res = await resetPassword({ email, token, password, password_confirmation: passwordConfirmation })
      setMessage(res.message)
      setPassword('')
      setPasswordConfirmation('')
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string; errors?: FieldErrors }>
      setFieldErrors(axiosErr.response?.data.errors ?? {})
      setError(axiosErr.response?.data.message ?? 'No se pudo restablecer la contraseña.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <AuthLayout
      title="Restablecer contraseña"
      subtitle="Definí una nueva contraseña para tu cuenta"
      footer={<><Link to="/login">Volver a iniciar sesión</Link></>}
    >
      <form className="login-form" onSubmit={handleSubmit}>
        {message && <Alert variant="success">{message}</Alert>}
        {error && <Alert>{error}</Alert>}
        {!token && <Alert>El enlace no incluye un token válido. Solicitá un nuevo correo de recuperación.</Alert>}
        <FormField label="Email" error={fieldErrors.email?.[0]}>
          <Input type="email" value={email} onChange={e => setEmail(e.target.value)} error={!!fieldErrors.email} required autoComplete="email" />
        </FormField>
        <FormField label="Nueva contraseña" error={fieldErrors.password?.[0]}>
          <Input type="password" value={password} onChange={e => setPassword(e.target.value)} error={!!fieldErrors.password} required minLength={8} autoComplete="new-password" />
        </FormField>
        <FormField label="Confirmar contraseña">
          <Input type="password" value={passwordConfirmation} onChange={e => setPasswordConfirmation(e.target.value)} required minLength={8} autoComplete="new-password" />
        </FormField>
        <Button type="submit" loading={loading} disabled={!token}>Actualizar contraseña</Button>
      </form>
    </AuthLayout>
  )
}

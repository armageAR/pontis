import { type FormEvent, useState } from 'react'
import { useAuth } from '@/context/AuthContext'
import Button from '@/components/Button'
import Input from '@/components/Input'
import FormField from '@/components/FormField'
import Alert from '@/components/Alert'
import type { AxiosError } from 'axios'
import './LoginForm.css'

interface FieldErrors {
  email?: string[]
  password?: string[]
}

export default function LoginForm() {
  const { login } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({})

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')
    setFieldErrors({})
    setLoading(true)

    try {
      await login(email, password)
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string; errors?: FieldErrors }>
      if (axiosErr.response?.status === 422) {
        setFieldErrors(axiosErr.response.data.errors ?? {})
        setError(axiosErr.response.data.message ?? 'Datos inválidos.')
      } else {
        setError('Error al iniciar sesión. Intentá de nuevo.')
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <form className="login-form" onSubmit={handleSubmit}>
      {error && <Alert>{error}</Alert>}

      <FormField label="Email" error={fieldErrors.email?.[0]}>
        <Input
          type="email"
          placeholder="tu@email.com"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          error={!!fieldErrors.email}
          required
          autoComplete="email"
          autoFocus
        />
      </FormField>

      <FormField label="Contraseña" error={fieldErrors.password?.[0]}>
        <Input
          type="password"
          placeholder="Tu contraseña"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          error={!!fieldErrors.password}
          required
          autoComplete="current-password"
        />
      </FormField>

      <Button type="submit" loading={loading}>
        Ingresar
      </Button>
    </form>
  )
}

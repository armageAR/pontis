import { type FormEvent, useState } from 'react'
import { Link } from 'react-router-dom'
import AuthLayout from '@/components/AuthLayout'
import Alert from '@/components/Alert'
import Button from '@/components/Button'
import FormField from '@/components/FormField'
import Input from '@/components/Input'
import { forgotPassword } from '@/api/auth'

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState('')
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(false)

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setLoading(true)
    try {
      const res = await forgotPassword(email)
      setMessage(res.message)
    } catch {
      setMessage('Si el email corresponde a una cuenta registrada, enviaremos instrucciones para restablecer la contraseña.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <AuthLayout
      title="Recuperar contraseña"
      subtitle="Ingresá tu email para recibir instrucciones"
      footer={<><Link to="/login">Volver a iniciar sesión</Link></>}
    >
      <form className="login-form" onSubmit={handleSubmit}>
        {message && <Alert variant="success">{message}</Alert>}
        <FormField label="Email">
          <Input
            type="email"
            value={email}
            onChange={e => setEmail(e.target.value)}
            placeholder="tu@email.com"
            autoComplete="email"
            required
            autoFocus
          />
        </FormField>
        <Button type="submit" loading={loading}>Enviar instrucciones</Button>
      </form>
    </AuthLayout>
  )
}

import { Navigate, Link } from 'react-router-dom'
import { useAuth } from '@/context/useAuth'
import AuthLayout from '@/components/AuthLayout'
import Spinner from '@/components/Spinner'
import LoginForm from './LoginForm'

export default function LoginPage() {
  const { user, loading } = useAuth()

  if (loading) {
    return (
      <div style={{ minHeight: '100svh', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
        <Spinner size={32} />
      </div>
    )
  }

  if (user) {
    if (user.status === 'active') {
      return <Navigate to="/dashboard" replace />
    }
    return <Navigate to="/pending" replace />
  }

  return (
    <AuthLayout
      title="Iniciar sesión"
      subtitle="Ingresá tus credenciales para continuar"
      footer={
        <>
          ¿No tenés cuenta? <Link to="/register">Crear cuenta</Link>
        </>
      }
    >
      <LoginForm />
    </AuthLayout>
  )
}

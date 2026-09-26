import { Navigate, Link } from 'react-router-dom'
import { useAuth } from '@/context/useAuth'
import AuthLayout from '@/components/AuthLayout'
import Spinner from '@/components/Spinner'
import RegisterForm from './RegisterForm'

export default function RegisterPage() {
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
      title="Crear cuenta"
      subtitle="Completá tus datos para registrarte"
      footer={
        <>
          ¿Ya tenés cuenta? <Link to="/login">Iniciar sesión</Link>
        </>
      }
    >
      <RegisterForm />
    </AuthLayout>
  )
}

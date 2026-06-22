import { useState } from 'react'
import { Navigate } from 'react-router-dom'
import { useAuth } from '@/context/AuthContext'
import AuthLayout from '@/components/AuthLayout'
import Spinner from '@/components/Spinner'
import Button from '@/components/Button'
import PendingStatus from './PendingStatus'

export default function PendingPage() {
  const { user, loading, logout } = useAuth()
  const [loggingOut, setLoggingOut] = useState(false)

  if (loading) {
    return (
      <div style={{ minHeight: '100svh', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
        <Spinner size={32} />
      </div>
    )
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  if (user.status === 'active') {
    return <Navigate to="/dashboard" replace />
  }

  async function handleLogout() {
    setLoggingOut(true)
    try {
      await logout()
    } finally {
      setLoggingOut(false)
    }
  }

  return (
    <AuthLayout
      title=""
      footer={
        <Button variant="ghost" onClick={handleLogout} loading={loggingOut}>
          Cerrar sesión
        </Button>
      }
    >
      <PendingStatus />
    </AuthLayout>
  )
}

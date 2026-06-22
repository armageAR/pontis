import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '@/context/AuthContext'
import Spinner from './Spinner'
import './ProtectedRoute.css'

export default function ProtectedRoute() {
  const { user, loading } = useAuth()

  if (loading) {
    return (
      <div className="protected-loading">
        <Spinner size={32} />
      </div>
    )
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  if (user.status !== 'active') {
    return <Navigate to="/pending" replace />
  }

  return <Outlet />
}

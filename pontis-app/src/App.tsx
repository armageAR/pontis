import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider } from '@/context/AuthContext'
import ProtectedRoute from '@/components/ProtectedRoute'
import HomePage from '@/pages/home/HomePage'
import LoginPage from '@/pages/login/LoginPage'
import RegisterPage from '@/pages/register/RegisterPage'
import PendingPage from '@/pages/pending/PendingPage'
import VerifyEmailPage from '@/pages/verify-email/VerifyEmailPage'
import DashboardPage from '@/pages/dashboard/DashboardPage'
import WorkshopsPage from '@/pages/workshops/WorkshopsPage'
import ProfilePage from '@/pages/profile/ProfilePage'
import PersonPage from '@/pages/person/PersonPage'
import SearchPage from '@/pages/search/SearchPage'
import MisPublicacionesPage from '@/pages/publications/MisPublicacionesPage'
import BandejaPage from '@/pages/inbox/BandejaPage'
import AdministracionPage from '@/pages/admin/AdministracionPage'

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route path="/pending" element={<PendingPage />} />
          <Route path="/verify-email" element={<VerifyEmailPage />} />
          <Route element={<ProtectedRoute />}>
            <Route path="/dashboard" element={<DashboardPage />} />
            <Route path="/workshops" element={<WorkshopsPage />} />
            <Route path="/profile" element={<ProfilePage />} />
            <Route path="/people/:id" element={<PersonPage />} />
            <Route path="/buscar" element={<SearchPage />} />
            <Route path="/mis-publicaciones" element={<MisPublicacionesPage />} />
            <Route path="/bandeja" element={<BandejaPage />} />
            <Route path="/administracion" element={<AdministracionPage />} />
            {/* Redirects para no romper links viejos */}
            <Route path="/people" element={<Navigate to="/buscar" replace />} />
            <Route path="/explore" element={<Navigate to="/buscar" replace />} />
            <Route path="/services" element={<Navigate to="/mis-publicaciones" replace />} />
            <Route path="/needs" element={<Navigate to="/mis-publicaciones" replace />} />
            <Route path="/contact-requests" element={<Navigate to="/bandeja" replace />} />
            <Route path="/change-requests" element={<Navigate to="/bandeja" replace />} />
            <Route path="/users" element={<Navigate to="/administracion" replace />} />
          </Route>
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  )
}

import { BrowserRouter, Routes, Route } from 'react-router-dom'
import { AuthProvider } from '@/context/AuthContext'
import ProtectedRoute from '@/components/ProtectedRoute'
import HomePage from '@/pages/home/HomePage'
import LoginPage from '@/pages/login/LoginPage'
import RegisterPage from '@/pages/register/RegisterPage'
import PendingPage from '@/pages/pending/PendingPage'
import VerifyEmailPage from '@/pages/verify-email/VerifyEmailPage'
import DashboardPage from '@/pages/dashboard/DashboardPage'
import WorkshopsPage from '@/pages/workshops/WorkshopsPage'
import UsersPage from '@/pages/users/UsersPage'
import ProfilePage from '@/pages/profile/ProfilePage'
import ServicesPage from '@/pages/services/ServicesPage'
import NeedsPage from '@/pages/needs/NeedsPage'
import PeoplePage from '@/pages/people/PeoplePage'
import ContactRequestsPage from '@/pages/contact-requests/ContactRequestsPage'
import ChangeRequestsPage from '@/pages/change-requests/ChangeRequestsPage'
import PersonPage from '@/pages/person/PersonPage'

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
            <Route path="/users" element={<UsersPage />} />
            <Route path="/profile" element={<ProfilePage />} />
            <Route path="/services" element={<ServicesPage />} />
            <Route path="/needs" element={<NeedsPage />} />
            <Route path="/people" element={<PeoplePage />} />
            <Route path="/contact-requests" element={<ContactRequestsPage />} />
            <Route path="/change-requests" element={<ChangeRequestsPage />} />
            <Route path="/people/:id" element={<PersonPage />} />
          </Route>
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  )
}

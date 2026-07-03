import { useState } from 'react'
import { Link } from 'react-router-dom'
import AppLayout from '@/components/AppLayout'
import UsersPage from '@/pages/users/UsersPage'
import AuditLogPage from '@/pages/audit/AuditLogPage'
import DegreeValidationPage from '@/pages/validations/DegreeValidationPage'
import { useAuth } from '@/context/AuthContext'
import './AdministracionPage.css'

type Tab = 'hermanos' | 'talleres' | 'validaciones' | 'auditoria'

export default function AdministracionPage() {
  const { user } = useAuth()
  const isSuperAdmin = user?.role === 'superadmin'
  const [tab, setTab] = useState<Tab>('hermanos')

  return (
    <AppLayout>
      <div className="adm-tabs">
        <button
          className={`adm-tab ${tab === 'hermanos' ? 'adm-tab-active' : ''}`}
          onClick={() => setTab('hermanos')}
        >
          Hermanos
        </button>
        <button
          className={`adm-tab ${tab === 'validaciones' ? 'adm-tab-active' : ''}`}
          onClick={() => setTab('validaciones')}
        >
          Validaciones
        </button>
        {isSuperAdmin && (
          <button
            className={`adm-tab ${tab === 'auditoria' ? 'adm-tab-active' : ''}`}
            onClick={() => setTab('auditoria')}
          >
            Auditoría
          </button>
        )}
      </div>

      <div className="adm-tab-content">
        {tab === 'hermanos' && <UsersPage embedded />}
        {tab === 'validaciones' && <DegreeValidationPage />}
        {tab === 'auditoria' && isSuperAdmin && <AuditLogPage />}
      </div>
    </AppLayout>
  )
}

import { useState } from 'react'
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
  // El Admin de Taller entra desde el Panel principalmente para resolver
  // validaciones pendientes, así que su vista por defecto es Validaciones.
  const [tab, setTab] = useState<Tab>(isSuperAdmin ? 'hermanos' : 'validaciones')

  return (
    <AppLayout>
      <div className="adm-tabs">
        {isSuperAdmin && (
          <button
            className={`adm-tab ${tab === 'hermanos' ? 'adm-tab-active' : ''}`}
            onClick={() => setTab('hermanos')}
          >
            Hermanos
          </button>
        )}
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
        {tab === 'hermanos' && isSuperAdmin && <UsersPage embedded />}
        {tab === 'validaciones' && <DegreeValidationPage />}
        {tab === 'auditoria' && isSuperAdmin && <AuditLogPage />}
      </div>
    </AppLayout>
  )
}

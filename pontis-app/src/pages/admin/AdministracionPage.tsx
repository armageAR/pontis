import { useState } from 'react'
import { Link } from 'react-router-dom'
import AppLayout from '@/components/AppLayout'
import UsersPage from '@/pages/users/UsersPage'
import './AdministracionPage.css'

type Tab = 'hermanos' | 'talleres'

export default function AdministracionPage() {
  const [tab, setTab] = useState<Tab>('hermanos')

  return (
    <AppLayout>
      <h1 className="adm-title">Administración</h1>

      <div className="adm-tabs">
        <button
          className={`adm-tab ${tab === 'hermanos' ? 'adm-tab-active' : ''}`}
          onClick={() => setTab('hermanos')}
        >
          Hermanos
        </button>
        <button
          className={`adm-tab ${tab === 'talleres' ? 'adm-tab-active' : ''}`}
          onClick={() => setTab('talleres')}
        >
          Talleres
        </button>
      </div>

      <div className="adm-tab-content">
        {tab === 'hermanos' && <UsersPage embedded />}
        {tab === 'talleres' && (
          <div className="adm-talleres">
            <p className="adm-talleres-text">
              Gestión de talleres:{' '}
              <Link to="/workshops" className="adm-link">Ir a Talleres</Link>
            </p>
            <p className="adm-proximamente">Sincronización y catálogos: próximamente.</p>
          </div>
        )}
      </div>
    </AppLayout>
  )
}

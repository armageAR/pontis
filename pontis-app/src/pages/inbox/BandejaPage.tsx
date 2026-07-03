import { useState } from 'react'
import AppLayout from '@/components/AppLayout'
import ContactRequestsPage from '@/pages/contact-requests/ContactRequestsPage'
import ChangeRequestsPage from '@/pages/change-requests/ChangeRequestsPage'
import NotificationsList from './NotificationsList'
import './BandejaPage.css'

type Tab = 'contactos' | 'tramites' | 'notificaciones'

export default function BandejaPage() {
  const [activeTab, setActiveTab] = useState<Tab>('contactos')

  return (
    <AppLayout>
      <div className="bdj-header">
        <h1 className="bdj-title">Bandeja</h1>
      </div>
      <div className="bdj-tabs">
        <button
          className={`bdj-tab${activeTab === 'contactos' ? ' bdj-tab-active' : ''}`}
          onClick={() => setActiveTab('contactos')}
        >
          Contactos
        </button>
        <button
          className={`bdj-tab${activeTab === 'tramites' ? ' bdj-tab-active' : ''}`}
          onClick={() => setActiveTab('tramites')}
        >
          Trámites
        </button>
        <button
          className={`bdj-tab${activeTab === 'notificaciones' ? ' bdj-tab-active' : ''}`}
          onClick={() => setActiveTab('notificaciones')}
        >
          Notificaciones
        </button>
      </div>
      <div className="bdj-content">
        {activeTab === 'contactos' && <ContactRequestsPage embedded />}
        {activeTab === 'tramites' && <ChangeRequestsPage embedded mode="self" />}
        {activeTab === 'notificaciones' && <NotificationsList />}
      </div>
    </AppLayout>
  )
}

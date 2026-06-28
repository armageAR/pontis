import { useState } from 'react'
import AppLayout from '@/components/AppLayout'
import ServicesPage from '@/pages/services/ServicesPage'
import NeedsPage from '@/pages/needs/NeedsPage'
import './MisPublicacionesPage.css'

type Tab = 'services' | 'needs'

export default function MisPublicacionesPage() {
  const [tab, setTab] = useState<Tab>('services')

  return (
    <AppLayout>
      <h1 className="mispub-title">Mis publicaciones</h1>

      <div className="mispub-tabs">
        <button
          className={`mispub-tab ${tab === 'services' ? 'mispub-tab-active' : ''}`}
          onClick={() => setTab('services')}
        >
          Ofrezco
        </button>
        <button
          className={`mispub-tab ${tab === 'needs' ? 'mispub-tab-active' : ''}`}
          onClick={() => setTab('needs')}
        >
          Necesito
        </button>
      </div>

      <div className="mispub-tab-content">
        {tab === 'services' && <ServicesPage embedded />}
        {tab === 'needs' && <NeedsPage embedded />}
      </div>
    </AppLayout>
  )
}

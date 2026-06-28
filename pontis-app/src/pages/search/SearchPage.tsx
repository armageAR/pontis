import { useState } from 'react'
import AppLayout from '@/components/AppLayout'
import PeoplePage from '@/pages/people/PeoplePage'
import ExplorePage from '@/pages/explore/ExplorePage'
import './SearchPage.css'

type Tab = 'people' | 'explore'

export default function SearchPage() {
  const [tab, setTab] = useState<Tab>('people')

  return (
    <AppLayout>
      <h1 className="search-title">Buscar</h1>

      <div className="search-tabs">
        <button
          className={`search-tab ${tab === 'people' ? 'search-tab-active' : ''}`}
          onClick={() => setTab('people')}
        >
          Personas
        </button>
        <button
          className={`search-tab ${tab === 'explore' ? 'search-tab-active' : ''}`}
          onClick={() => setTab('explore')}
        >
          Servicios y necesidades
        </button>
      </div>

      <div className="search-tab-content">
        {tab === 'people' && <PeoplePage embedded />}
        {tab === 'explore' && <ExplorePage embedded />}
      </div>
    </AppLayout>
  )
}

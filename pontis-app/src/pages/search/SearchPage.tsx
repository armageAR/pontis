import { useState } from 'react'
import AppLayout from '@/components/AppLayout'
import PeoplePage from '@/pages/people/PeoplePage'
import WorkshopDirectoryTab from './WorkshopDirectoryTab'
import './SearchPage.css'

// El tab "Servicios y necesidades" (exploración de publicaciones) queda diferido
// a V2: en V1 la búsqueda expone sólo Hermanos y Talleres (defer-publications-to-v2).
type Tab = 'people' | 'workshops'

export default function SearchPage() {
  const [tab, setTab] = useState<Tab>('people')

  return (
    <AppLayout>
      <h1 className="search-title">Buscar</h1>
      <p className="search-description">
        Explorá el directorio de Hermanos de la Orden y los Talleres de tu región.
        Encontrá quién contactar antes de viajar, buscá por ubicación o localizá
        un Taller cercano.
      </p>

      <div className="search-tabs">
        <button
          className={`search-tab${tab === 'people' ? ' search-tab-active' : ''}`}
          onClick={() => setTab('people')}
        >
          Hermanos
        </button>
        <button
          className={`search-tab${tab === 'workshops' ? ' search-tab-active' : ''}`}
          onClick={() => setTab('workshops')}
        >
          Talleres
        </button>
      </div>

      <div className="search-tab-content">
        {tab === 'people'     && <PeoplePage embedded />}
        {tab === 'workshops'  && <WorkshopDirectoryTab />}
      </div>
    </AppLayout>
  )
}

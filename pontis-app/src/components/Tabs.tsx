import { useRef, type KeyboardEvent } from 'react'
import './Tabs.css'

export interface TabItem {
  key: string
  label: string
}

interface TabsProps {
  tabs: TabItem[]
  active: string
  onChange: (key: string) => void
}

export default function Tabs({ tabs, active, onChange }: TabsProps) {
  const btnRefs = useRef<Record<string, HTMLButtonElement | null>>({})

  function handleKeyDown(e: KeyboardEvent<HTMLButtonElement>, idx: number) {
    let nextIdx: number | null = null
    if (e.key === 'ArrowRight') nextIdx = (idx + 1) % tabs.length
    else if (e.key === 'ArrowLeft') nextIdx = (idx - 1 + tabs.length) % tabs.length
    else if (e.key === 'Home') nextIdx = 0
    else if (e.key === 'End') nextIdx = tabs.length - 1
    if (nextIdx !== null) {
      e.preventDefault()
      const key = tabs[nextIdx].key
      onChange(key)
      btnRefs.current[key]?.focus()
    }
  }

  return (
    <div className="tabs" role="tablist">
      {tabs.map((tab, idx) => {
        const selected = tab.key === active
        return (
          <button
            key={tab.key}
            ref={el => { btnRefs.current[tab.key] = el }}
            role="tab"
            id={`tab-${tab.key}`}
            aria-selected={selected}
            aria-controls={`panel-${tab.key}`}
            tabIndex={selected ? 0 : -1}
            className={`tabs-tab${selected ? ' tabs-tab-active' : ''}`}
            onClick={() => onChange(tab.key)}
            onKeyDown={e => handleKeyDown(e, idx)}
          >
            {tab.label}
          </button>
        )
      })}
    </div>
  )
}

import { useEffect, useRef, useState } from 'react'
import { X } from 'lucide-react'
import { searchWorkshopsPublic, type WorkshopSearchResult } from '@/api/workshops'
import Input from './Input'
import Spinner from './Spinner'
import './WorkshopPicker.css'

interface WorkshopPickerProps {
  value: WorkshopSearchResult | null
  onChange: (workshop: WorkshopSearchResult | null) => void
  error?: boolean
  placeholder?: string
}

export default function WorkshopPicker({ value, onChange, error, placeholder = 'Escribí el nombre o número del taller...' }: WorkshopPickerProps) {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState<WorkshopSearchResult[]>([])
  const [loading, setLoading] = useState(false)
  const [open, setOpen] = useState(false)
  const containerRef = useRef<HTMLDivElement>(null)
  const debounceRef = useRef<ReturnType<typeof setTimeout>>(undefined)

  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  function handleInput(val: string) {
    setQuery(val)

    if (value) {
      onChange(null)
    }

    if (debounceRef.current) clearTimeout(debounceRef.current)

    if (val.length < 2) {
      setResults([])
      setOpen(false)
      return
    }

    debounceRef.current = setTimeout(async () => {
      setLoading(true)
      try {
        const data = await searchWorkshopsPublic(val)
        setResults(data)
        setOpen(true)
      } catch {
        setResults([])
      } finally {
        setLoading(false)
      }
    }, 300)
  }

  function handleSelect(workshop: WorkshopSearchResult) {
    onChange(workshop)
    setQuery(`${workshop.name} Nro ${workshop.number}`)
    setOpen(false)
    setResults([])
  }

  function handleClear() {
    onChange(null)
    setQuery('')
    setResults([])
    setOpen(false)
  }

  return (
    <div className="workshop-picker" ref={containerRef}>
      <div className="workshop-picker-input-wrap">
        <Input
          value={query}
          onChange={(e) => handleInput(e.target.value)}
          placeholder={placeholder}
          error={error}
          autoComplete="off"
        />
        {loading && <Spinner size={16} className="workshop-picker-spinner" />}
        {value && (
          <button type="button" className="workshop-picker-clear" onClick={handleClear} aria-label="Quitar selección">
            <X size={16} />
          </button>
        )}
      </div>

      {value && (
        <div className="workshop-picker-selected">
          <span className="workshop-picker-selected-number">Nro {value.number}</span>
          <span className="workshop-picker-selected-name">{value.name}</span>
          {value.city && <span className="workshop-picker-selected-meta">{value.city}</span>}
        </div>
      )}

      {open && !value && results.length > 0 && (
        <ul className="workshop-picker-dropdown">
          {results.map((w) => (
            <li key={w.id} className="workshop-picker-option" onClick={() => handleSelect(w)}>
              <span className="workshop-picker-option-number">Nro {w.number}</span>
              <span className="workshop-picker-option-name">{w.name}</span>
              <span className="workshop-picker-option-meta">
                {[w.zone_name, w.city].filter(Boolean).join(' · ') || '—'}
              </span>
            </li>
          ))}
        </ul>
      )}

      {open && !value && query.length >= 2 && !loading && results.length === 0 && (
        <div className="workshop-picker-empty">No se encontraron talleres</div>
      )}
    </div>
  )
}

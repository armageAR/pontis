import { useEffect, useId, useRef, useState, type ReactNode, type KeyboardEvent } from 'react'
import FormField from '@/components/FormField'
import Input from '@/components/Input'
import { apiError } from '@/utils/errors'
import type { FieldStatus } from './useProfileAutosave'

interface InlineFieldProps {
  label: string
  value: string
  status?: FieldStatus
  onCommit: (value: string) => Promise<void> | void
  as?: 'input' | 'textarea' | 'select'
  type?: string
  placeholder?: string
  rows?: number
  validate?: 'email' | 'url'
  disabled?: boolean
  children?: ReactNode
}

// Campo con autosave inline. Texto/textarea confirman en blur (y Enter en input
// de una línea); select/date confirman en change. Muestra el estado por campo.
export default function InlineField({
  label,
  value,
  status,
  onCommit,
  as = 'input',
  type = 'text',
  placeholder,
  rows = 3,
  validate,
  disabled,
  children,
}: InlineFieldProps) {
  const [draft, setDraft] = useState(value)
  const [localError, setLocalError] = useState('')
  const focused = useRef(false)
  const controlId = useId()

  // Re-sincronizar el draft con el valor reconciliado, pero nunca mientras el
  // usuario está editando el campo (evita pisar lo que está tipeando si una
  // respuesta de guardado llega tarde).
  useEffect(() => { if (!focused.current) setDraft(value) }, [value])

  const immediate = as === 'select' || type === 'date'

  function validateValue(v: string): string {
    if (!v) return ''
    if (validate === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'Ingresá un email válido.'
    if (validate === 'url' && !/^https?:\/\/.+/.test(v)) return 'Ingresá una URL válida (https://...).'
    return ''
  }

  async function commit(v: string) {
    const err = validateValue(v)
    if (err) { setLocalError(err); return }
    setLocalError('')
    try {
      await onCommit(v)
    } catch (e) {
      setLocalError(apiError(e, 'No se pudo guardar el cambio.'))
    }
  }

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) {
    setDraft(e.target.value)
    if (immediate) commit(e.target.value)
  }

  function handleFocus() {
    focused.current = true
  }

  function handleBlur() {
    focused.current = false
    if (!immediate) commit(draft)
  }

  function handleKeyDown(e: KeyboardEvent<HTMLInputElement>) {
    if (as === 'input' && !immediate && e.key === 'Enter') {
      e.preventDefault()
      commit(draft)
    }
  }

  const fieldStatus = localError ? 'error' : status === 'saving' ? 'saving' : status === 'saved' ? 'saved' : status === 'error' ? 'error' : undefined

  return (
    <FormField label={label} htmlFor={controlId} error={localError || undefined} status={fieldStatus}>
      {as === 'textarea' ? (
        <textarea
          id={controlId}
          className="profile-textarea"
          value={draft}
          rows={rows}
          placeholder={placeholder}
          disabled={disabled}
          onFocus={handleFocus}
          onChange={handleChange}
          onBlur={handleBlur}
        />
      ) : as === 'select' ? (
        <select
          id={controlId}
          className="profile-select"
          value={draft}
          disabled={disabled}
          onFocus={handleFocus}
          onBlur={() => { focused.current = false }}
          onChange={handleChange}
        >
          {children}
        </select>
      ) : (
        <Input
          id={controlId}
          type={type}
          value={draft}
          placeholder={placeholder}
          disabled={disabled}
          error={!!localError}
          onFocus={handleFocus}
          onChange={handleChange}
          onBlur={handleBlur}
          onKeyDown={handleKeyDown}
        />
      )}
    </FormField>
  )
}

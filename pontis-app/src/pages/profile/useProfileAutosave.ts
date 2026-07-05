import { useCallback, useRef, useState } from 'react'
import * as profileApi from '@/api/profile'
import type { Profile } from '@/api/profile'
import { toDateInputValue } from '@/utils/date'

export type FieldStatus = 'idle' | 'saving' | 'saved' | 'error'

// Los campos de fecha se guardan como YYYY-MM-DD pero el backend los devuelve
// como ISO (con hora); se normalizan ambos lados para comparar sin falsos cambios.
const DATE_KEYS = new Set<keyof Profile>(['birth_date', 'initiation_date'])

// Hook de autosave del perfil: guarda un campo con PATCH de una sola clave,
// reconcilia el valor devuelto y expone el estado por campo (idle/saving/saved/error).
export function useProfileAutosave(
  profile: Profile | null,
  setProfile: React.Dispatch<React.SetStateAction<Profile | null>>,
) {
  const [statuses, setStatuses] = useState<Record<string, FieldStatus>>({})
  const timers = useRef<Record<string, ReturnType<typeof setTimeout>>>({})

  const setStatus = useCallback((key: string, status: FieldStatus) => {
    setStatuses(s => ({ ...s, [key]: status }))
  }, [])

  const commitField = useCallback(async (key: keyof Profile, value: string | null) => {
    const normalized = value === '' ? null : value
    // Evitar PATCH redundante cuando el valor no cambió respecto al guardado.
    const current = (profile?.[key] ?? null) as unknown as string | null
    const isDate = DATE_KEYS.has(key)
    const same = isDate
      ? toDateInputValue(current) === toDateInputValue(normalized)
      : current === normalized
    if (same) {
      setStatus(key as string, 'idle')
      return
    }
    setStatus(key as string, 'saving')
    try {
      const updated = await profileApi.updateProfile({ [key]: normalized } as Partial<Profile>)
      setProfile(p => (p ? { ...p, [key]: updated[key] } : p))
      setStatus(key as string, 'saved')
      clearTimeout(timers.current[key as string])
      timers.current[key as string] = setTimeout(() => setStatus(key as string, 'idle'), 1200)
    } catch (err) {
      setStatus(key as string, 'error')
      throw err
    }
  }, [profile, setProfile, setStatus])

  return { statuses, commitField }
}

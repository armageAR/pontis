import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react'
import { AuthContext } from './authContext'
import * as authApi from '@/api/auth'
import type { User } from '@/api/auth'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  // Derived at mount instead of inside the effect: with no token there is
  // nothing to wait for, so the app must not start in a loading state.
  const [loading, setLoading] = useState(() => localStorage.getItem('token') !== null)

  const fetchUser = useCallback(async () => {
    try {
      const u = await authApi.getMe()
      setUser(u)
    } catch {
      localStorage.removeItem('token')
      setUser(null)
    }
  }, [])

  useEffect(() => {
    if (localStorage.getItem('token') === null) {
      return
    }
    // Restoring the session on mount is a genuine external-system read, and the
    // flag has to be lowered once the request settles. Clearing this rule would
    // mean Suspense or a data-fetching library; see issue #3.
    // eslint-disable-next-line react-hooks/set-state-in-effect
    fetchUser().finally(() => setLoading(false))
  }, [fetchUser])

  const login = useCallback(async (email: string, password: string) => {
    const { token, user } = await authApi.login(email, password)
    localStorage.setItem('token', token)
    setUser(user)
  }, [])

  const register = useCallback(
    async (name: string, email: string, password: string, passwordConfirmation: string, workshopId: number) => {
      const { token, user } = await authApi.register(name, email, password, passwordConfirmation, workshopId)
      localStorage.setItem('token', token)
      setUser(user)
    },
    [],
  )

  const logout = useCallback(async () => {
    try {
      await authApi.logout()
    } finally {
      localStorage.removeItem('token')
      setUser(null)
    }
  }, [])

  const refreshUser = useCallback(async () => {
    await fetchUser()
  }, [fetchUser])

  const value = useMemo(
    () => ({ user, loading, login, register, logout, refreshUser }),
    [user, loading, login, register, logout, refreshUser],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

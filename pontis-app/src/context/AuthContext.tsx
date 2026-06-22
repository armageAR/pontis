import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react'
import type { User } from '@/api/auth'
import * as authApi from '@/api/auth'

interface AuthState {
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  register: (name: string, email: string, password: string, passwordConfirmation: string, workshopId: number) => Promise<void>
  logout: () => Promise<void>
  refreshUser: () => Promise<void>
}

const AuthContext = createContext<AuthState | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)

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
    const token = localStorage.getItem('token')
    if (!token) {
      setLoading(false)
      return
    }
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

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}

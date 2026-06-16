import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { api, getToken, setToken } from '../api/client'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [setupRequired, setSetupRequired] = useState(null)
  const [loading, setLoading] = useState(true)

  const refreshSetupStatus = useCallback(async () => {
    const data = await api('/setup/status')
    setSetupRequired(data.setup_required)
    return data.setup_required
  }, [])

  const refreshUser = useCallback(async () => {
    if (!getToken()) {
      setUser(null)
      return null
    }

    try {
      const data = await api('/auth/me')
      setUser(data.user)
      return data.user
    } catch {
      setToken(null)
      setUser(null)
      return null
    }
  }, [])

  useEffect(() => {
    async function init() {
      try {
        await refreshSetupStatus()
        await refreshUser()
      } finally {
        setLoading(false)
      }
    }

    init()
  }, [refreshSetupStatus, refreshUser])

  const login = useCallback(async (email, password) => {
    const data = await api('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    })
    setToken(data.token)
    setUser(data.user)
    setSetupRequired(false)
    return data.user
  }, [])

  const setup = useCallback(async (payload) => {
    const data = await api('/setup', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
    setToken(data.token)
    setUser(data.user)
    setSetupRequired(false)
    return data.user
  }, [])

  const logout = useCallback(() => {
    setToken(null)
    setUser(null)
  }, [])

  const value = useMemo(
    () => ({
      user,
      loading,
      setupRequired,
      login,
      setup,
      logout,
      refreshUser,
      refreshSetupStatus,
      isSuperadmin: user?.role === 'superadmin',
      canWrite: user?.role === 'superadmin' || user?.role === 'admin',
    }),
    [user, loading, setupRequired, login, setup, logout, refreshUser, refreshSetupStatus],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider')
  }
  return context
}

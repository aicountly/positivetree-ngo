import { useCallback, useEffect, useMemo, useState } from 'react'
import { api, getToken, setToken, setUnauthorizedHandler } from '../api/client'
import { AuthContext } from './useAuth'

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [setupRequired, setSetupRequired] = useState(undefined)
  const [loading, setLoading] = useState(true)
  const [initError, setInitError] = useState('')

  const logout = useCallback(() => {
    setToken(null)
    setUser(null)
  }, [])

  useEffect(() => {
    setUnauthorizedHandler(() => {
      setUser(null)
    })

    return () => setUnauthorizedHandler(null)
  }, [])

  const refreshSetupStatus = useCallback(async () => {
    const data = await api('/setup/status')
    if (typeof data?.setup_required !== 'boolean') {
      throw new Error('Invalid setup status response from API')
    }
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
      setUser(null)
      return null
    }
  }, [])

  useEffect(() => {
    async function init() {
      setInitError('')

      try {
        await refreshSetupStatus()
      } catch (err) {
        setInitError(err.message || 'Unable to reach the API')
      }

      try {
        await refreshUser()
      } catch {
        setUser(null)
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
    setInitError('')
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
    setInitError('')
    return data.user
  }, [])

  const value = useMemo(
    () => ({
      user,
      loading,
      setupRequired,
      initError,
      login,
      setup,
      logout,
      refreshUser,
      refreshSetupStatus,
      isSuperadmin: user?.role === 'superadmin',
      canWrite: user?.role === 'superadmin' || user?.role === 'admin',
    }),
    [user, loading, setupRequired, initError, login, setup, logout, refreshUser, refreshSetupStatus],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

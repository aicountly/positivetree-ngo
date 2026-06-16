import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'

export function ProtectedRoute({ roles }) {
  const { user, loading, setupRequired } = useAuth()

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-50 text-slate-600">
        Loading...
      </div>
    )
  }

  if (setupRequired) {
    return <Navigate to="/setup" replace />
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  if (roles && !roles.includes(user.role)) {
    return <Navigate to="/" replace />
  }

  return <Outlet />
}

export function WriteRoute() {
  const { canWrite } = useAuth()
  if (!canWrite) {
    return <Navigate to="/" replace />
  }
  return <Outlet />
}

export function PublicOnlyRoute() {
  const { user, loading, setupRequired } = useAuth()

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-50 text-slate-600">
        Loading...
      </div>
    )
  }

  if (setupRequired) {
    return <Navigate to="/setup" replace />
  }

  if (user) {
    return <Navigate to="/" replace />
  }

  return <Outlet />
}

export function SetupRoute() {
  const { loading, setupRequired, user } = useAuth()

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-50 text-slate-600">
        Loading...
      </div>
    )
  }

  if (!setupRequired && user) {
    return <Navigate to="/" replace />
  }

  if (!setupRequired && !user) {
    return <Navigate to="/login" replace />
  }

  return <Outlet />
}

import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { Alert } from './ui'

function LoadingScreen() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 text-slate-600">
      Loading...
    </div>
  )
}

function InitErrorScreen({ message }) {
  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-6">
      <div className="max-w-md space-y-3">
        <Alert tone="error">{message || 'Unable to connect to the API.'}</Alert>
        <p className="text-sm text-slate-600">
          The server returned an error for <code className="rounded bg-slate-200 px-1">/api/setup/status</code>.
          On cPanel, run <code className="rounded bg-slate-200 px-1">composer install --no-dev</code> inside{' '}
          <code className="rounded bg-slate-200 px-1">public_html/api</code> and ensure{' '}
          <code className="rounded bg-slate-200 px-1">api/data/</code> is writable.
        </p>
      </div>
    </div>
  )
}

export function ProtectedRoute({ roles }) {
  const { user, loading, setupRequired, initError } = useAuth()
  const location = useLocation()

  if (loading) {
    return <LoadingScreen />
  }

  if (initError && setupRequired === undefined) {
    return <InitErrorScreen message={initError} />
  }

  if (setupRequired === true && !user) {
    return <Navigate to="/setup" replace />
  }

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />
  }

  if (roles && !roles.includes(user.role)) {
    return <Navigate to="/" replace state={{ forbidden: true }} />
  }

  return <Outlet />
}

export function SuperadminRoute() {
  const { user, loading, setupRequired, initError } = useAuth()

  if (loading) {
    return <LoadingScreen />
  }

  if (initError && setupRequired === undefined) {
    return <InitErrorScreen message={initError} />
  }

  if (setupRequired === true && !user) {
    return <Navigate to="/setup" replace />
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  if (user.role !== 'superadmin') {
    return <Navigate to="/" replace state={{ forbidden: true }} />
  }

  return <Outlet />
}

export function WriteRoute() {
  const { canWrite } = useAuth()
  if (!canWrite) {
    return <Navigate to="/" replace state={{ forbidden: true }} />
  }
  return <Outlet />
}

export function PublicOnlyRoute() {
  const { user, loading, setupRequired, initError } = useAuth()

  if (loading) {
    return <LoadingScreen />
  }

  if (initError && setupRequired === undefined) {
    return <InitErrorScreen message={initError} />
  }

  if (setupRequired === true && !user) {
    return <Navigate to="/setup" replace />
  }

  if (user) {
    return <Navigate to="/" replace />
  }

  return <Outlet />
}

export function SetupRoute() {
  const { loading, setupRequired, user, initError } = useAuth()

  if (loading) {
    return <LoadingScreen />
  }

  if (initError && setupRequired === undefined) {
    return <InitErrorScreen message={initError} />
  }

  if (setupRequired === true && user) {
    return <Navigate to="/" replace />
  }

  if (setupRequired === false && user) {
    return <Navigate to="/" replace />
  }

  if (setupRequired === false && !user) {
    return <Navigate to="/login" replace />
  }

  if (setupRequired === undefined) {
    return <InitErrorScreen message={initError} />
  }

  return <Outlet />
}

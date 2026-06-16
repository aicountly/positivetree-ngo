import { useEffect, useState } from 'react'
import { Navigate } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { Alert, Button, Card, Input } from '../components/ui'

export default function Setup() {
  const { setup, refreshSetupStatus } = useAuth()
  const [form, setForm] = useState({ name: '', email: '', password: '', confirm: '' })
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [checking, setChecking] = useState(true)
  const [allowed, setAllowed] = useState(false)

  useEffect(() => {
    let cancelled = false

    async function verifySetupAllowed() {
      setChecking(true)
      setError('')

      try {
        const required = await refreshSetupStatus()
        if (cancelled) return

        if (!required) {
          setAllowed(false)
          return
        }

        setAllowed(true)
      } catch (err) {
        if (!cancelled) {
          setError(err.message || 'Unable to verify setup status')
          setAllowed(false)
        }
      } finally {
        if (!cancelled) setChecking(false)
      }
    }

    verifySetupAllowed()
    return () => {
      cancelled = true
    }
  }, [refreshSetupStatus])

  function updateField(field) {
    return (event) => setForm((current) => ({ ...current, [field]: event.target.value }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setError('')

    if (form.password !== form.confirm) {
      setError('Passwords do not match')
      return
    }

    setSubmitting(true)
    try {
      await setup({
        name: form.name,
        email: form.email,
        password: form.password,
      })
    } catch (err) {
      if (err.status === 409) {
        setError('Setup has already been completed. Sign in with your existing superadmin account.')
        setAllowed(false)
      } else {
        setError(err.message || 'Setup failed')
      }
    } finally {
      setSubmitting(false)
    }
  }

  if (checking) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-50 text-slate-600">
        Checking setup status...
      </div>
    )
  }

  if (!allowed) {
    if (error) {
      return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50 px-6">
          <Card className="w-full max-w-md space-y-4">
            <Alert tone="error">{error}</Alert>
            <p className="text-sm text-slate-600">
              If a superadmin already exists, sign in instead of creating a new account.
            </p>
            <a href="/app/login" className="inline-block text-sm font-medium text-green-700 hover:underline">
              Go to sign in
            </a>
          </Card>
        </div>
      )
    }

    return <Navigate to="/login" replace />
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-6">
      <Card className="w-full max-w-md">
        <div className="mb-6 text-center">
          <p className="text-sm font-semibold uppercase tracking-wide text-green-600">First-time setup</p>
          <h1 className="mt-2 text-2xl font-bold">Create superadmin</h1>
          <p className="mt-1 text-sm text-slate-600">
            This account will manage users and donation receipts.
          </p>
        </div>

        {error && (
          <Alert className="mb-4" tone="error">
            {error}
          </Alert>
        )}

        <form className="space-y-4" onSubmit={handleSubmit}>
          <Input label="Full name" value={form.name} onChange={updateField('name')} required />
          <Input label="Email" type="email" value={form.email} onChange={updateField('email')} required />
          <Input
            label="Password"
            type="password"
            value={form.password}
            onChange={updateField('password')}
            minLength={8}
            required
          />
          <Input
            label="Confirm password"
            type="password"
            value={form.confirm}
            onChange={updateField('confirm')}
            minLength={8}
            required
          />
          <Button type="submit" className="w-full" disabled={submitting}>
            {submitting ? 'Creating account...' : 'Create superadmin'}
          </Button>
        </form>
      </Card>
    </div>
  )
}

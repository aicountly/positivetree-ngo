import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { Alert, Button, Card, Input } from '../components/ui'

export default function Login() {
  const { login } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(event) {
    event.preventDefault()
    setError('')
    setSubmitting(true)

    try {
      await login(email, password)
    } catch (err) {
      setError(err.message || 'Login failed')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-6">
      <Card className="w-full max-w-md">
        <div className="mb-6 text-center">
          <Link to="/" className="inline-flex flex-col items-center gap-3">
            <img
              src={`${import.meta.env.BASE_URL}favicon.png`}
              alt="Positive Tree NGO"
              className="h-24 w-24 object-contain"
            />
            <span className="text-lg font-semibold text-green-700">Positive Tree NGO</span>
          </Link>
          <h1 className="mt-4 text-2xl font-bold">Sign in</h1>
          <p className="mt-1 text-sm text-slate-600">Access the donation management portal</p>
        </div>

        {error && (
          <Alert className="mb-4" tone="error">
            {error}
          </Alert>
        )}

        <form className="space-y-4" onSubmit={handleSubmit}>
          <Input
            label="Email"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
          />
          <Input
            label="Password"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
          />
          <Button type="submit" className="w-full" disabled={submitting}>
            {submitting ? 'Signing in...' : 'Sign in'}
          </Button>
        </form>
      </Card>
    </div>
  )
}

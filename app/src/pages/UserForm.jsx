import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { api } from '../api/client'
import { Alert, Button, Card, Input, Select } from '../components/ui'

const CREATE_ROLES = [
  { value: 'admin', label: 'Admin' },
  { value: 'viewer', label: 'Viewer' },
]

const EDIT_ROLES = [
  { value: 'superadmin', label: 'Superadmin' },
  ...CREATE_ROLES,
]

export default function UserForm() {
  const { id } = useParams()
  const navigate = useNavigate()
  const isEdit = Boolean(id)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(isEdit)
  const [submitting, setSubmitting] = useState(false)
  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    role: 'admin',
    is_active: true,
  })

  useEffect(() => {
    if (!isEdit) return

    let cancelled = false

    async function loadUser() {
      setLoading(true)
      setError('')
      try {
        const data = await api(`/users/${id}`)
        if (cancelled) return
        const user = data.user
        setForm({
          name: user.name,
          email: user.email,
          password: '',
          role: user.role,
          is_active: user.is_active,
        })
      } catch (err) {
        if (!cancelled) setError(err.message)
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    loadUser()
    return () => {
      cancelled = true
    }
  }, [id, isEdit])

  function updateField(field) {
    return (event) => {
      const value = event.target.type === 'checkbox' ? event.target.checked : event.target.value
      setForm((current) => ({ ...current, [field]: value }))
    }
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setError('')
    setSubmitting(true)

    try {
      if (isEdit) {
        const payload = {
          name: form.name,
          email: form.email,
          role: form.role,
          is_active: form.is_active,
        }
        if (form.password) {
          payload.password = form.password
        }
        await api(`/users/${id}`, {
          method: 'PUT',
          body: JSON.stringify(payload),
        })
      } else {
        await api('/users', {
          method: 'POST',
          body: JSON.stringify({
            name: form.name,
            email: form.email,
            password: form.password,
            role: form.role,
            is_active: form.is_active,
          }),
        })
      }
      navigate('/users')
    } catch (err) {
      setError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  if (loading) {
    return <p className="text-slate-600">Loading user...</p>
  }

  return (
    <div className="mx-auto max-w-xl space-y-6">
      <div>
        <Link to="/users" className="text-sm text-green-700 hover:underline">
          Back to users
        </Link>
        <h1 className="mt-2 text-2xl font-bold">{isEdit ? 'Edit user' : 'Add user'}</h1>
      </div>

      <Card>
        {error && (
          <Alert className="mb-4" tone="error">
            {error}
          </Alert>
        )}

        <form className="space-y-4" onSubmit={handleSubmit}>
          <Input label="Full name" value={form.name} onChange={updateField('name')} required />
          <Input label="Email" type="email" value={form.email} onChange={updateField('email')} required />
          <Select label="Role" value={form.role} onChange={updateField('role')}>
            {(isEdit ? EDIT_ROLES : CREATE_ROLES).map((role) => (
              <option key={role.value} value={role.value}>
                {role.label}
              </option>
            ))}
          </Select>
          <Input
            label={isEdit ? 'New password (optional)' : 'Password'}
            type="password"
            value={form.password}
            onChange={updateField('password')}
            minLength={isEdit ? undefined : 8}
            required={!isEdit}
          />
          <label className="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" checked={form.is_active} onChange={updateField('is_active')} />
            Active account
          </label>
          <Button type="submit" disabled={submitting}>
            {submitting ? 'Saving...' : isEdit ? 'Save changes' : 'Create user'}
          </Button>
        </form>
      </Card>
    </div>
  )
}

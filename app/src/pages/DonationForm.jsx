import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { api } from '../api/client'
import { Alert, Button, Card, Input, Select, Textarea } from '../components/ui'
import { toDateInput } from '../utils/format'

const PAYMENT_METHODS = [
  { value: 'cash', label: 'Cash' },
  { value: 'cheque', label: 'Cheque' },
  { value: 'bank_transfer', label: 'Bank transfer' },
  { value: 'upi', label: 'UPI' },
]

export default function DonationForm() {
  const navigate = useNavigate()
  const [causes, setCauses] = useState([])
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [form, setForm] = useState({
    donor_name: '',
    donor_email: '',
    donor_phone: '',
    donor_pan: '',
    amount_inr: '',
    cause: '',
    payment_method: 'cash',
    transaction_ref: '',
    donated_at: toDateInput(new Date().toISOString()),
    notes: '',
  })

  useEffect(() => {
    api('/donations/causes')
      .then((result) => {
        setCauses(result.causes)
        setForm((current) => ({ ...current, cause: result.causes[0] || '' }))
      })
      .catch((err) => setError(err.message))
  }, [])

  function updateField(field) {
    return (event) => setForm((current) => ({ ...current, [field]: event.target.value }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setError('')
    setSubmitting(true)

    try {
      const result = await api('/donations', {
        method: 'POST',
        body: JSON.stringify(form),
      })
      navigate(`/donations/${result.donation.id}`)
    } catch (err) {
      setError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div>
        <Link to="/donations" className="text-sm text-green-700 hover:underline">
          Back to donations
        </Link>
        <h1 className="mt-2 text-2xl font-bold">Record offline donation</h1>
      </div>

      <Card>
        {error && (
          <Alert className="mb-4" tone="error">
            {error}
          </Alert>
        )}

        <form className="space-y-4" onSubmit={handleSubmit}>
          <Input label="Donor name" value={form.donor_name} onChange={updateField('donor_name')} required />
          <div className="grid gap-4 md:grid-cols-2">
            <Input label="Email" type="email" value={form.donor_email} onChange={updateField('donor_email')} />
            <Input label="Phone" value={form.donor_phone} onChange={updateField('donor_phone')} />
          </div>
          <Input
            label="PAN (optional)"
            value={form.donor_pan}
            onChange={(event) =>
              setForm((current) => ({ ...current, donor_pan: event.target.value.toUpperCase() }))
            }
            placeholder="ABCDE1234F"
            maxLength={10}
          />
          <div className="grid gap-4 md:grid-cols-2">
            <Input
              label="Amount (INR)"
              type="number"
              min="1"
              step="0.01"
              value={form.amount_inr}
              onChange={updateField('amount_inr')}
              required
            />
            <Input
              label="Donation date"
              type="date"
              value={form.donated_at}
              onChange={updateField('donated_at')}
              required
            />
          </div>
          <Select label="Cause" value={form.cause} onChange={updateField('cause')} required>
            {causes.map((cause) => (
              <option key={cause} value={cause}>
                {cause}
              </option>
            ))}
          </Select>
          <Select
            label="Payment method"
            value={form.payment_method}
            onChange={updateField('payment_method')}
          >
            {PAYMENT_METHODS.map((method) => (
              <option key={method.value} value={method.value}>
                {method.label}
              </option>
            ))}
          </Select>
          <Input
            label="Transaction reference"
            value={form.transaction_ref}
            onChange={updateField('transaction_ref')}
            placeholder="Cheque number, UPI ref, etc."
          />
          <Textarea label="Notes" value={form.notes} onChange={updateField('notes')} />
          <Button type="submit" disabled={submitting}>
            {submitting ? 'Saving...' : 'Create receipt'}
          </Button>
        </form>
      </Card>
    </div>
  )
}

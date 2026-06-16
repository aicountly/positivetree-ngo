import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { api, downloadReceipt } from '../api/client'
import { useAuth } from '../auth/useAuth'
import { Alert, Badge, Button, Card, Input, Select, Textarea } from '../components/ui'
import { formatDateTime, formatInr, toDateInput } from '../utils/format'

const PAYMENT_METHODS = [
  { value: 'cash', label: 'Cash' },
  { value: 'cheque', label: 'Cheque' },
  { value: 'bank_transfer', label: 'Bank transfer' },
  { value: 'upi', label: 'UPI' },
]

export default function DonationDetail() {
  const { id } = useParams()
  const { canWrite } = useAuth()
  const [donation, setDonation] = useState(null)
  const [causes, setCauses] = useState([])
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [form, setForm] = useState(null)

  useEffect(() => {
    let cancelled = false

    async function load() {
      setError('')
      setMessage('')
      setDonation(null)
      setForm(null)

      try {
        const [detail, causeData] = await Promise.all([
          api(`/donations/${id}`),
          api('/donations/causes'),
        ])
        if (cancelled) return

        setDonation(detail.donation)
        setCauses(causeData.causes)
        setForm({
          donor_name: detail.donation.donor_name,
          donor_email: detail.donation.donor_email || '',
          donor_phone: detail.donation.donor_phone || '',
          amount_inr: detail.donation.amount_inr,
          cause: detail.donation.cause,
          payment_method: detail.donation.payment_method || 'cash',
          transaction_ref: detail.donation.transaction_ref || '',
          donated_at: toDateInput(detail.donation.donated_at),
          notes: detail.donation.notes || '',
        })
      } catch (err) {
        if (!cancelled) setError(err.message)
      }
    }

    load()
    return () => {
      cancelled = true
    }
  }, [id])

  const editable = canWrite && donation?.channel === 'offline'

  function updateField(field) {
    return (event) => setForm((current) => ({ ...current, [field]: event.target.value }))
  }

  async function handleSave(event) {
    event.preventDefault()
    setError('')
    setMessage('')
    setSubmitting(true)

    try {
      const result = await api(`/donations/${id}`, {
        method: 'PUT',
        body: JSON.stringify(form),
      })
      setDonation(result.donation)
      setMessage('Donation updated successfully.')
    } catch (err) {
      setError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  async function handleReceipt() {
    try {
      await downloadReceipt(id)
    } catch (err) {
      setError(err.message)
    }
  }

  if (error && !donation) {
    return <p className="text-red-600">{error}</p>
  }

  if (!donation || !form) {
    return <p className="text-slate-600">Loading donation...</p>
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <Link to="/donations" className="text-sm text-green-700 hover:underline">
            Back to donations
          </Link>
          <h1 className="mt-2 text-2xl font-bold">
            {donation.receipt_number || `Donation #${donation.id}`}
          </h1>
          <div className="mt-2 flex flex-wrap gap-2">
            <Badge tone={donation.channel === 'online' ? 'blue' : 'green'}>{donation.channel}</Badge>
            <Badge tone={donation.status === 'completed' ? 'green' : 'amber'}>{donation.status}</Badge>
          </div>
        </div>
        {donation.status === 'completed' && donation.receipt_number && (
          <Button variant="secondary" onClick={handleReceipt}>
            Download receipt
          </Button>
        )}
      </div>

      <Card>
        <dl className="grid gap-4 md:grid-cols-2">
          <div>
            <dt className="text-sm text-slate-500">Amount</dt>
            <dd className="text-lg font-semibold text-green-700">{formatInr(donation.amount_paise)}</dd>
          </div>
          <div>
            <dt className="text-sm text-slate-500">Donated at</dt>
            <dd>{formatDateTime(donation.donated_at)}</dd>
          </div>
          {donation.razorpay_payment_id && (
            <div>
              <dt className="text-sm text-slate-500">Razorpay payment ID</dt>
              <dd>{donation.razorpay_payment_id}</dd>
            </div>
          )}
        </dl>
      </Card>

      {message && <Alert tone="success">{message}</Alert>}
      {error && <Alert tone="error">{error}</Alert>}

      {editable ? (
        <Card>
          <form className="space-y-4" onSubmit={handleSave}>
            <Input label="Donor name" value={form.donor_name} onChange={updateField('donor_name')} required />
            <div className="grid gap-4 md:grid-cols-2">
              <Input label="Email" type="email" value={form.donor_email} onChange={updateField('donor_email')} />
              <Input label="Phone" value={form.donor_phone} onChange={updateField('donor_phone')} />
            </div>
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
            <Select label="Cause" value={form.cause} onChange={updateField('cause')}>
              {causes.map((cause) => (
                <option key={cause} value={cause}>
                  {cause}
                </option>
              ))}
            </Select>
            <Select label="Payment method" value={form.payment_method} onChange={updateField('payment_method')}>
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
            />
            <Textarea label="Notes" value={form.notes} onChange={updateField('notes')} />
            <Button type="submit" disabled={submitting}>
              {submitting ? 'Saving...' : 'Save changes'}
            </Button>
          </form>
        </Card>
      ) : (
        <Card>
          <p className="text-sm text-slate-600">
            {donation.channel === 'online'
              ? 'Online donations are read-only in the admin portal.'
              : 'You have read-only access to this donation.'}
          </p>
        </Card>
      )}
    </div>
  )
}

import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { api, downloadCertificate, downloadReceipt, openDonationDocument } from '../api/client'
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
  const [approving, setApproving] = useState(false)
  const [savingPan, setSavingPan] = useState(false)
  const [form, setForm] = useState(null)
  const [panForm, setPanForm] = useState('')

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
          donor_pan: detail.donation.donor_pan || '',
          amount_inr: detail.donation.amount_inr,
          cause: detail.donation.cause,
          payment_method: detail.donation.payment_method || 'cash',
          transaction_ref: detail.donation.transaction_ref || '',
          donated_at: toDateInput(detail.donation.donated_at),
          notes: detail.donation.notes || '',
        })
        setPanForm(detail.donation.donor_pan || '')
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
  const canEditPan = canWrite && donation?.status === 'completed'
  const panMissing = donation?.status === 'completed' && !donation?.has_donor_pan

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
      setPanForm(result.donation.donor_pan || '')
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

  async function handleViewReceipt() {
    try {
      await openDonationDocument(id, 'receipt', 'html')
    } catch (err) {
      setError(err.message)
    }
  }

  async function handleCertificate() {
    try {
      await downloadCertificate(id)
    } catch (err) {
      setError(err.message)
    }
  }

  async function handleViewCertificate() {
    try {
      await openDonationDocument(id, 'certificate', 'html')
    } catch (err) {
      setError(err.message)
    }
  }

  async function handleSavePan(event) {
    event.preventDefault()
    setError('')
    setMessage('')
    setSavingPan(true)

    try {
      const result = await api(`/donations/${id}`, {
        method: 'PUT',
        body: JSON.stringify({ donor_pan: panForm }),
      })
      setDonation(result.donation)
      setPanForm(result.donation.donor_pan || '')
      setMessage('Donor PAN saved.')
    } catch (err) {
      setError(err.message)
    } finally {
      setSavingPan(false)
    }
  }

  async function handleApproveCertificate() {
    setError('')
    setMessage('')
    setApproving(true)

    try {
      const result = await api(`/donations/${id}/approve-certificate`, { method: 'POST' })
      setDonation(result.donation)
      setMessage('Donation certificate approved.')
    } catch (err) {
      setError(err.message)
    } finally {
      setApproving(false)
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
          <div className="flex flex-wrap gap-2">
            <Button variant="secondary" onClick={handleViewReceipt}>
              View receipt
            </Button>
            <Button variant="secondary" onClick={handleReceipt}>
              Download receipt
            </Button>
          </div>
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

      {donation.status === 'completed' && (
        <Card>
          <h2 className="mb-3 text-lg font-semibold">Donation certificate</h2>
          <div className="mb-4 flex flex-wrap items-center gap-2">
            <Badge tone={donation.certificate_status === 'approved' ? 'green' : 'amber'}>
              {donation.certificate_status || 'pending'}
            </Badge>
            {donation.certificate_status !== 'approved' && (
              <Badge tone={donation.has_donor_pan ? 'green' : 'red'}>
                {donation.has_donor_pan ? 'PAN present' : 'PAN missing'}
              </Badge>
            )}
            {donation.certificate_number && (
              <span className="text-sm text-slate-600">{donation.certificate_number}</span>
            )}
          </div>

          {donation.certificate_status === 'approved' ? (
            <div className="space-y-3">
              {donation.certificate_approved_at && (
                <p className="text-sm text-slate-600">
                  Approved on {formatDateTime(donation.certificate_approved_at)}
                </p>
              )}
              <div className="flex flex-wrap gap-2">
                <Button variant="secondary" onClick={handleViewCertificate}>
                  View certificate
                </Button>
                <Button variant="secondary" onClick={handleCertificate}>
                  Download certificate
                </Button>
              </div>
            </div>
          ) : (
            <div className="space-y-3">
              <p className="text-sm text-slate-600">
                Certificate will be available after Accounts Team approval.
              </p>
              {panMissing && (
                <Alert tone="error">
                  Donor PAN is required before issuing a certificate. Add PAN below, then approve.
                </Alert>
              )}
              {canWrite && (
                <Button
                  onClick={handleApproveCertificate}
                  disabled={approving || panMissing}
                  title={panMissing ? 'Add donor PAN before approving' : 'Approve certificate'}
                >
                  {approving ? 'Approving...' : 'Approve for certificate'}
                </Button>
              )}
            </div>
          )}
        </Card>
      )}

      {canEditPan && donation.channel === 'online' && (
        <Card>
          <h2 className="mb-3 text-lg font-semibold">Donor PAN</h2>
          <p className="mb-4 text-sm text-slate-600">
            Online donations are read-only except for donor PAN, which Accounts can add before certificate approval.
          </p>
          <form className="space-y-4" onSubmit={handleSavePan}>
            <Input
              label="PAN (optional until certificate approval)"
              value={panForm}
              onChange={(event) => setPanForm(event.target.value.toUpperCase())}
              placeholder="ABCDE1234F"
              maxLength={10}
            />
            <Button type="submit" disabled={savingPan}>
              {savingPan ? 'Saving...' : 'Save PAN'}
            </Button>
          </form>
        </Card>
      )}

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
              ? canWrite
                ? 'Online donation details are read-only. Use the PAN section above to add donor PAN for certificate approval.'
                : 'Online donation details are read-only in the admin portal.'
              : 'You have read-only access to this donation.'}
          </p>
          {donation.donor_pan && (
            <p className="mt-3 text-sm">
              <span className="text-slate-500">Donor PAN:</span> {donation.donor_pan}
            </p>
          )}
        </Card>
      )}
    </div>
  )
}

import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api/client'
import { useAuth } from '../auth/useAuth'
import { Alert, Badge, Button, Card, Select } from '../components/ui'
import { formatDateTime, formatInr } from '../utils/format'

const PAN_FILTERS = [
  { value: '', label: 'All PAN status' },
  { value: 'present', label: 'PAN present' },
  { value: 'missing', label: 'PAN missing' },
]

export default function PendingCertificatesList() {
  const { canWrite } = useAuth()
  const [panStatus, setPanStatus] = useState('')
  const [data, setData] = useState(null)
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(true)
  const [approvingId, setApprovingId] = useState(null)

  async function loadDonations(nextPanStatus = panStatus) {
    setError('')
    setLoading(true)
    try {
      const params = new URLSearchParams({ certificate_pending: '1' })
      if (nextPanStatus) params.set('pan_status', nextPanStatus)
      const result = await api(`/donations?${params.toString()}`)
      setData(result)
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadDonations()
  }, [])

  const summary = useMemo(() => {
    const items = data?.items || []
    const ready = items.filter((item) => item.has_donor_pan).length
    return {
      total: items.length,
      ready,
      blocked: items.length - ready,
    }
  }, [data])

  async function handleApprove(donation) {
    if (!donation.has_donor_pan) return

    setError('')
    setMessage('')
    setApprovingId(donation.id)

    try {
      await api(`/donations/${donation.id}/approve-certificate`, { method: 'POST' })
      setMessage(`Certificate approved for ${donation.receipt_number || `#${donation.id}`}.`)
      await loadDonations()
    } catch (err) {
      setError(err.message)
    } finally {
      setApprovingId(null)
    }
  }

  function handleFilterSubmit(event) {
    event.preventDefault()
    loadDonations()
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Pending certificates</h1>
        <p className="text-slate-600">
          Completed donations awaiting Accounts approval. Donor PAN is required before issuing a certificate.
        </p>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <Card>
          <p className="text-sm text-slate-500">Total pending</p>
          <p className="text-2xl font-bold">{summary.total}</p>
        </Card>
        <Card>
          <p className="text-sm text-slate-500">Ready to approve</p>
          <p className="text-2xl font-bold text-green-700">{summary.ready}</p>
        </Card>
        <Card>
          <p className="text-sm text-slate-500">Blocked (PAN missing)</p>
          <p className="text-2xl font-bold text-amber-700">{summary.blocked}</p>
        </Card>
      </div>

      <Card>
        <form className="grid gap-4 md:grid-cols-3" onSubmit={handleFilterSubmit}>
          <Select
            label="PAN status"
            value={panStatus}
            onChange={(e) => setPanStatus(e.target.value)}
          >
            {PAN_FILTERS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </Select>
          <div className="flex items-end">
            <Button type="submit" className="w-full md:w-auto">
              Apply filter
            </Button>
          </div>
        </form>
      </Card>

      {message && <Alert tone="success">{message}</Alert>}
      {error && <Alert tone="error">{error}</Alert>}

      <Card className="overflow-x-auto">
        {loading ? (
          <p className="text-slate-600">Loading pending certificates...</p>
        ) : !data || data.items.length === 0 ? (
          <p className="text-slate-600">No pending certificates found.</p>
        ) : (
          <table className="min-w-full text-left text-sm">
            <thead className="border-b border-slate-200 text-slate-500">
              <tr>
                <th className="py-2 pr-4">Receipt</th>
                <th className="py-2 pr-4">Donor</th>
                <th className="py-2 pr-4">Amount</th>
                <th className="py-2 pr-4">PAN</th>
                <th className="py-2 pr-4">Date</th>
                <th className="py-2 pr-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              {data.items.map((donation) => (
                <tr key={donation.id} className="border-b border-slate-100">
                  <td className="py-3 pr-4">
                    <Link
                      to={`/donations/${donation.id}`}
                      className="font-medium text-green-700 hover:underline"
                    >
                      {donation.receipt_number || `#${donation.id}`}
                    </Link>
                  </td>
                  <td className="py-3 pr-4">{donation.donor_name}</td>
                  <td className="py-3 pr-4">{formatInr(donation.amount_paise)}</td>
                  <td className="py-3 pr-4">
                    <Badge tone={donation.has_donor_pan ? 'green' : 'red'}>
                      {donation.has_donor_pan ? 'Present' : 'Missing'}
                    </Badge>
                  </td>
                  <td className="py-3 pr-4">{formatDateTime(donation.donated_at)}</td>
                  <td className="py-3 pr-4">
                    <div className="flex flex-wrap gap-2">
                      <Link to={`/donations/${donation.id}`}>
                        <Button variant="secondary">View</Button>
                      </Link>
                      {canWrite && (
                        <Button
                          disabled={!donation.has_donor_pan || approvingId === donation.id}
                          title={
                            donation.has_donor_pan
                              ? 'Approve certificate'
                              : 'Add donor PAN before approving'
                          }
                          onClick={() => handleApprove(donation)}
                        >
                          {approvingId === donation.id ? 'Approving...' : 'Approve'}
                        </Button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>
    </div>
  )
}

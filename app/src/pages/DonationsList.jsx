import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api/client'
import { useAuth } from '../auth/useAuth'
import { Badge, Button, Card, Input, Select } from '../components/ui'
import { formatDateTime, formatInr } from '../utils/format'

const CHANNELS = [
  { value: '', label: 'All channels' },
  { value: 'online', label: 'Online' },
  { value: 'offline', label: 'Offline' },
]

export default function DonationsList() {
  const { canWrite } = useAuth()
  const [filters, setFilters] = useState({ search: '', channel: '', cause: '' })
  const [causes, setCauses] = useState([])
  const [data, setData] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [filtering, setFiltering] = useState(false)

  async function loadDonations(nextFilters = filters) {
    setError('')
    setFiltering(true)
    try {
      const params = new URLSearchParams()
      if (nextFilters.search) params.set('search', nextFilters.search)
      if (nextFilters.channel) params.set('channel', nextFilters.channel)
      if (nextFilters.cause) params.set('cause', nextFilters.cause)
      const result = await api(`/donations?${params.toString()}`)
      setData(result)
    } catch (err) {
      setError(err.message)
    } finally {
      setFiltering(false)
      setLoading(false)
    }
  }

  useEffect(() => {
    let cancelled = false

    async function loadInitial() {
      setError('')
      try {
        const [causesResult, donationsResult] = await Promise.all([
          api('/donations/causes'),
          api('/donations'),
        ])
        if (cancelled) return
        setCauses(causesResult.causes)
        setData(donationsResult)
      } catch (err) {
        if (!cancelled) setError(err.message)
      }
    }

    loadInitial()
    return () => {
      cancelled = true
    }
  }, [])

  function handleFilterSubmit(event) {
    event.preventDefault()
    loadDonations()
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">Donations</h1>
          <p className="text-slate-600">Manage online and offline donation receipts</p>
        </div>
        {canWrite && (
          <Link to="/donations/new">
            <Button>Record offline donation</Button>
          </Link>
        )}
      </div>

      <Card>
        <form className="grid gap-4 md:grid-cols-4" onSubmit={handleFilterSubmit}>
          <Input
            label="Search"
            placeholder="Name, email, receipt..."
            value={filters.search}
            onChange={(e) => setFilters((current) => ({ ...current, search: e.target.value }))}
          />
          <Select
            label="Channel"
            value={filters.channel}
            onChange={(e) => setFilters((current) => ({ ...current, channel: e.target.value }))}
          >
            {CHANNELS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </Select>
          <Select
            label="Cause"
            value={filters.cause}
            onChange={(e) => setFilters((current) => ({ ...current, cause: e.target.value }))}
          >
            <option value="">All causes</option>
            {causes.map((cause) => (
              <option key={cause} value={cause}>
                {cause}
              </option>
            ))}
          </Select>
          <div className="flex items-end">
            <Button type="submit" className="w-full">
              Apply filters
            </Button>
          </div>
        </form>
      </Card>

      {error && <p className="text-red-600">{error}</p>}

      <Card className="overflow-x-auto">
        {loading || filtering ? (
          <p className="text-slate-600">{loading ? 'Loading donations...' : 'Updating results...'}</p>
        ) : !data || data.items.length === 0 ? (
          <p className="text-slate-600">No donations found.</p>
        ) : (
          <table className="min-w-full text-left text-sm">
            <thead className="border-b border-slate-200 text-slate-500">
              <tr>
                <th className="py-2 pr-4">Receipt</th>
                <th className="py-2 pr-4">Donor</th>
                <th className="py-2 pr-4">Cause</th>
                <th className="py-2 pr-4">Amount</th>
                <th className="py-2 pr-4">Channel</th>
                <th className="py-2 pr-4">Status</th>
                <th className="py-2 pr-4">Date</th>
              </tr>
            </thead>
            <tbody>
              {data.items.map((donation) => (
                <tr key={donation.id} className="border-b border-slate-100">
                  <td className="py-3 pr-4">
                    <Link to={`/donations/${donation.id}`} className="font-medium text-green-700 hover:underline">
                      {donation.receipt_number || `#${donation.id}`}
                    </Link>
                  </td>
                  <td className="py-3 pr-4">{donation.donor_name}</td>
                  <td className="py-3 pr-4">{donation.cause}</td>
                  <td className="py-3 pr-4">{formatInr(donation.amount_paise)}</td>
                  <td className="py-3 pr-4">
                    <Badge tone={donation.channel === 'online' ? 'blue' : 'green'}>
                      {donation.channel}
                    </Badge>
                  </td>
                  <td className="py-3 pr-4">
                    <Badge tone={donation.status === 'completed' ? 'green' : 'amber'}>
                      {donation.status}
                    </Badge>
                  </td>
                  <td className="py-3 pr-4">{formatDateTime(donation.donated_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>
    </div>
  )
}

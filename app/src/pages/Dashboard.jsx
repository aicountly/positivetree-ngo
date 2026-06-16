import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api/client'
import { Badge, Card } from '../components/ui'
import { formatDateTime, formatInr } from '../utils/format'

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    api('/dashboard')
      .then(setStats)
      .catch((err) => setError(err.message))
  }, [])

  if (error) {
    return <p className="text-red-600">{error}</p>
  }

  if (!stats) {
    return <p className="text-slate-600">Loading dashboard...</p>
  }

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-bold">Dashboard</h1>
        <p className="text-slate-600">Overview of completed donations</p>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <p className="text-sm text-slate-500">Total received</p>
          <p className="mt-2 text-3xl font-bold text-green-700">{formatInr(stats.total_amount_paise)}</p>
        </Card>
        <Card>
          <p className="text-sm text-slate-500">Online donations</p>
          <p className="mt-2 text-3xl font-bold">{stats.online_count}</p>
        </Card>
        <Card>
          <p className="text-sm text-slate-500">Offline donations</p>
          <p className="mt-2 text-3xl font-bold">{stats.offline_count}</p>
        </Card>
      </div>

      <Card>
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-lg font-semibold">Recent donations</h2>
          <Link to="/donations" className="text-sm font-medium text-green-700 hover:underline">
            View all
          </Link>
        </div>

        {stats.recent.length === 0 ? (
          <p className="text-sm text-slate-500">No completed donations yet.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full text-left text-sm">
              <thead className="border-b border-slate-200 text-slate-500">
                <tr>
                  <th className="py-2 pr-4">Receipt</th>
                  <th className="py-2 pr-4">Donor</th>
                  <th className="py-2 pr-4">Amount</th>
                  <th className="py-2 pr-4">Channel</th>
                  <th className="py-2 pr-4">Date</th>
                </tr>
              </thead>
              <tbody>
                {stats.recent.map((donation) => (
                  <tr key={donation.id} className="border-b border-slate-100">
                    <td className="py-3 pr-4">
                      <Link to={`/donations/${donation.id}`} className="font-medium text-green-700 hover:underline">
                        {donation.receipt_number || `#${donation.id}`}
                      </Link>
                    </td>
                    <td className="py-3 pr-4">{donation.donor_name}</td>
                    <td className="py-3 pr-4">{formatInr(donation.amount_paise)}</td>
                    <td className="py-3 pr-4">
                      <Badge tone={donation.channel === 'online' ? 'blue' : 'green'}>
                        {donation.channel}
                      </Badge>
                    </td>
                    <td className="py-3 pr-4">{formatDateTime(donation.donated_at)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>
    </div>
  )
}

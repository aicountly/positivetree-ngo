import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api/client'
import { Badge, Button, Card } from '../components/ui'

const ROLE_LABELS = {
  superadmin: 'Superadmin',
  admin: 'Admin',
  viewer: 'Viewer',
}

export default function UsersList() {
  const [users, setUsers] = useState([])
  const [error, setError] = useState('')

  useEffect(() => {
    api('/users')
      .then((result) => setUsers(result.items))
      .catch((err) => setError(err.message))
  }, [])

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">Users</h1>
          <p className="text-slate-600">Manage portal access for staff</p>
        </div>
        <Link to="/users/new">
          <Button>Add user</Button>
        </Link>
      </div>

      {error && <p className="text-red-600">{error}</p>}

      <Card className="overflow-x-auto">
        <table className="min-w-full text-left text-sm">
          <thead className="border-b border-slate-200 text-slate-500">
            <tr>
              <th className="py-2 pr-4">Name</th>
              <th className="py-2 pr-4">Email</th>
              <th className="py-2 pr-4">Role</th>
              <th className="py-2 pr-4">Status</th>
              <th className="py-2 pr-4"></th>
            </tr>
          </thead>
          <tbody>
            {users.map((user) => (
              <tr key={user.id} className="border-b border-slate-100">
                <td className="py-3 pr-4">{user.name}</td>
                <td className="py-3 pr-4">{user.email}</td>
                <td className="py-3 pr-4">{ROLE_LABELS[user.role] || user.role}</td>
                <td className="py-3 pr-4">
                  <Badge tone={user.is_active ? 'green' : 'red'}>
                    {user.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </td>
                <td className="py-3 pr-4">
                  <Link to={`/users/${user.id}`} className="font-medium text-green-700 hover:underline">
                    Edit
                  </Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>
    </div>
  )
}

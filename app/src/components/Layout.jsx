import { NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'
import { Button } from './ui'

const navLinkClass = ({ isActive }) =>
  `block rounded-lg px-3 py-2 text-sm font-medium ${
    isActive ? 'bg-green-100 text-green-800' : 'text-slate-700 hover:bg-slate-100'
  }`

export default function Layout() {
  const { user, logout, isSuperadmin } = useAuth()

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <div className="mx-auto flex min-h-screen max-w-7xl">
        <aside className="hidden w-64 shrink-0 border-r border-slate-200 bg-white p-6 md:block">
          <a href="/" className="mb-8 block text-lg font-semibold text-green-700">
            Positive Tree NGO
          </a>
          <nav className="space-y-1">
            <NavLink to="/" end className={navLinkClass}>
              Dashboard
            </NavLink>
            <NavLink to="/donations" className={navLinkClass}>
              Donations
            </NavLink>
            {isSuperadmin && (
              <NavLink to="/users" className={navLinkClass}>
                Users
              </NavLink>
            )}
          </nav>
        </aside>

        <div className="flex min-w-0 flex-1 flex-col">
          <header className="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
            <div>
              <p className="text-sm text-slate-500">Donation Management</p>
              <p className="font-medium">{user?.name}</p>
            </div>
            <Button variant="secondary" onClick={logout}>
              Log out
            </Button>
          </header>

          <main className="flex-1 px-6 py-8">
            <Outlet />
          </main>
        </div>
      </div>
    </div>
  )
}

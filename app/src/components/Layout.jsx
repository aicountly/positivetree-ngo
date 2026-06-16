import { useState } from 'react'
import { Link, NavLink, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { Alert, Button } from './ui'

const navLinkClass = ({ isActive }) =>
  `block rounded-lg px-3 py-2 text-sm font-medium ${
    isActive ? 'bg-green-100 text-green-800' : 'text-slate-700 hover:bg-slate-100'
  }`

function NavItems({ onNavigate }) {
  const { canWrite, isSuperadmin } = useAuth()

  return (
    <>
      <NavLink to="/" end className={navLinkClass} onClick={onNavigate}>
        Dashboard
      </NavLink>
      <NavLink to="/donations" className={navLinkClass} onClick={onNavigate}>
        Donations
      </NavLink>
      {canWrite && (
        <NavLink to="/certificates/pending" className={navLinkClass} onClick={onNavigate}>
          Pending certificates
        </NavLink>
      )}
      {canWrite && (
        <NavLink to="/settings/documents" className={navLinkClass} onClick={onNavigate}>
          Documents
        </NavLink>
      )}
      {isSuperadmin && (
        <>
          <NavLink to="/users" className={navLinkClass} onClick={onNavigate}>
            Users
          </NavLink>
        </>
      )}
    </>
  )
}

export default function Layout() {
  const { user, logout } = useAuth()
  const location = useLocation()
  const [mobileOpen, setMobileOpen] = useState(false)
  const forbidden = location.state?.forbidden

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <div className="mx-auto flex min-h-screen max-w-7xl">
        <aside className="hidden w-64 shrink-0 border-r border-slate-200 bg-white p-6 md:block">
          <Link to="/" className="mb-8 block text-lg font-semibold text-green-700">
            Positive Tree NGO
          </Link>
          <nav className="space-y-1">
            <NavItems />
          </nav>
        </aside>

        <div className="flex min-w-0 flex-1 flex-col">
          <header className="border-b border-slate-200 bg-white px-6 py-4">
            <div className="flex items-center justify-between gap-4">
              <div className="flex items-center gap-3">
                <button
                  type="button"
                  className="rounded-lg border border-slate-300 px-3 py-2 text-sm md:hidden"
                  onClick={() => setMobileOpen((open) => !open)}
                  aria-expanded={mobileOpen}
                  aria-label="Toggle navigation"
                >
                  Menu
                </button>
                <div>
                  <p className="text-sm text-slate-500">Donation Management</p>
                  <p className="font-medium">{user?.name}</p>
                </div>
              </div>
              <Button variant="secondary" onClick={logout}>
                Log out
              </Button>
            </div>

            {mobileOpen && (
              <nav className="mt-4 space-y-1 border-t border-slate-200 pt-4 md:hidden">
                <NavItems onNavigate={() => setMobileOpen(false)} />
              </nav>
            )}
          </header>

          <main className="flex-1 px-6 py-8">
            {forbidden && (
              <Alert className="mb-6" tone="error">
                You do not have permission to access that page.
              </Alert>
            )}
            <Outlet />
          </main>
        </div>
      </div>
    </div>
  )
}

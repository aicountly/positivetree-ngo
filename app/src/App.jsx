import { Navigate, Route, Routes } from 'react-router-dom'
import Layout from './components/Layout'
import { ProtectedRoute, PublicOnlyRoute, SetupRoute, SuperadminRoute, WriteRoute } from './components/ProtectedRoute'
import Dashboard from './pages/Dashboard'
import DocumentSettings from './pages/DocumentSettings'
import DonationDetail from './pages/DonationDetail'
import DonationForm from './pages/DonationForm'
import DonationsList from './pages/DonationsList'
import Login from './pages/Login'
import PendingCertificatesList from './pages/PendingCertificatesList'
import Setup from './pages/Setup'
import UserForm from './pages/UserForm'
import UsersList from './pages/UsersList'

export default function App() {
  return (
    <Routes>
      <Route element={<SetupRoute />}>
        <Route path="/setup" element={<Setup />} />
      </Route>

      <Route element={<PublicOnlyRoute />}>
        <Route path="/login" element={<Login />} />
      </Route>

      <Route element={<ProtectedRoute roles={['superadmin', 'admin', 'viewer']} />}>
        <Route element={<Layout />}>
          <Route index element={<Dashboard />} />
          <Route path="donations" element={<DonationsList />} />
          <Route path="certificates/pending" element={<PendingCertificatesList />} />
          <Route element={<WriteRoute />}>
            <Route path="donations/new" element={<DonationForm />} />
          </Route>
          <Route path="donations/:id" element={<DonationDetail />} />
          <Route element={<WriteRoute />}>
            <Route path="settings/documents" element={<DocumentSettings />} />
          </Route>
          <Route element={<SuperadminRoute />}>
            <Route path="users" element={<UsersList />} />
            <Route path="users/new" element={<UserForm />} />
            <Route path="users/:id" element={<UserForm />} />
          </Route>
        </Route>
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}

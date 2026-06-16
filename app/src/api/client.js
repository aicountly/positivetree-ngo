const API_BASE = '/api'

let onUnauthorized = null

export function setUnauthorizedHandler(handler) {
  onUnauthorized = handler
}

export function setToken(token) {
  if (token) {
    localStorage.setItem('pt_token', token)
  } else {
    localStorage.removeItem('pt_token')
  }
}

export function getToken() {
  return localStorage.getItem('pt_token') || null
}

const PUBLIC_AUTH_PATHS = new Set([
  '/auth/login',
  '/setup',
  '/setup/status',
  '/payments/razorpay/config',
  '/payments/razorpay/order',
  '/payments/razorpay/verify',
  '/webhooks/razorpay',
])

export async function api(path, options = {}) {
  const token = getToken()
  const isPublicAuthPath = PUBLIC_AUTH_PATHS.has(path)
  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers || {}),
  }

  if (token && !isPublicAuthPath) {
    headers.Authorization = `Bearer ${token}`
  }

  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
  })

  const contentType = response.headers.get('content-type') || ''
  const isJson = contentType.includes('application/json')
  let data = null

  if (isJson) {
    data = await response.json()
  } else {
    const text = await response.text()
    if (text) {
      data = { error: text.slice(0, 200) }
    }
  }

  if (response.status === 401 && token && !isPublicAuthPath) {
    setToken(null)
    onUnauthorized?.()
  }

  if (!response.ok) {
    const fallback =
      response.status === 401
        ? 'Session expired or not authorized. Please sign in again.'
        : response.status === 500
          ? 'Server error (500). The API may be missing Composer dependencies or PHP SQLite support.'
          : 'Request failed'
    const error = new Error(data?.error || fallback)
    error.status = response.status
    error.data = data
    throw error
  }

  return data
}

export async function downloadReceipt(id) {
  await downloadDonationDocument(id, 'receipt')
}

export async function downloadCertificate(id) {
  await downloadDonationDocument(id, 'certificate')
}

export async function openDonationDocument(id, type, format = 'html') {
  const token = getToken()
  const response = await fetch(`${API_BASE}/donations/${id}/${type}?format=${format}`, {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  })

  if (response.status === 401 && token) {
    setToken(null)
    onUnauthorized?.()
    throw new Error('Session expired. Please sign in again.')
  }

  if (!response.ok) {
    throw new Error(`Unable to open ${type}`)
  }

  if (format === 'html') {
    const html = await response.text()
    const blob = new Blob([html], { type: 'text/html' })
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank')
    window.setTimeout(() => URL.revokeObjectURL(url), 60_000)
    return
  }

  const blob = await response.blob()
  const url = URL.createObjectURL(blob)
  window.open(url, '_blank')
  window.setTimeout(() => URL.revokeObjectURL(url), 60_000)
}

export async function previewDocument(type) {
  const token = getToken()
  const response = await fetch(`${API_BASE}/settings/documents/preview/${type}?format=pdf`, {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  })

  if (response.status === 401 && token) {
    setToken(null)
    onUnauthorized?.()
    throw new Error('Session expired. Please sign in again.')
  }

  if (!response.ok) {
    throw new Error('Unable to preview document')
  }

  const blob = await response.blob()
  const url = URL.createObjectURL(blob)
  window.open(url, '_blank')
  window.setTimeout(() => URL.revokeObjectURL(url), 60_000)
}

export async function uploadDocumentSignature(file) {
  const token = getToken()
  const formData = new FormData()
  formData.append('signature', file)

  const response = await fetch(`${API_BASE}/settings/documents/signature`, {
    method: 'POST',
    headers: token ? { Authorization: `Bearer ${token}` } : {},
    body: formData,
  })

  const data = await response.json()

  if (response.status === 401 && token) {
    setToken(null)
    onUnauthorized?.()
    throw new Error('Session expired. Please sign in again.')
  }

  if (!response.ok) {
    throw new Error(data?.error || 'Unable to upload signature')
  }

  return data
}

export async function fetchSignaturePreviewBlob() {
  const token = getToken()
  const response = await fetch(`${API_BASE}/settings/documents/signature`, {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  })

  if (response.status === 401 && token) {
    setToken(null)
    onUnauthorized?.()
    throw new Error('Session expired. Please sign in again.')
  }

  if (!response.ok) {
    throw new Error('Unable to load signature preview')
  }

  return response.blob()
}

export async function uploadDocumentLogo(file) {
  const token = getToken()
  const formData = new FormData()
  formData.append('logo', file)

  const response = await fetch(`${API_BASE}/settings/documents/logo`, {
    method: 'POST',
    headers: token ? { Authorization: `Bearer ${token}` } : {},
    body: formData,
  })

  const data = await response.json()

  if (response.status === 401 && token) {
    setToken(null)
    onUnauthorized?.()
    throw new Error('Session expired. Please sign in again.')
  }

  if (!response.ok) {
    throw new Error(data?.error || 'Unable to upload logo')
  }

  return data
}

async function downloadDonationDocument(id, type) {
  const token = getToken()
  const response = await fetch(`${API_BASE}/donations/${id}/${type}?format=pdf`, {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  })

  if (response.status === 401 && token) {
    setToken(null)
    onUnauthorized?.()
    throw new Error('Session expired. Please sign in again.')
  }

  if (!response.ok) {
    throw new Error(`Unable to download ${type}`)
  }

  const blob = await response.blob()
  const url = URL.createObjectURL(blob)
  const opened = window.open(url, '_blank')
  if (!opened) {
    const link = document.createElement('a')
    link.href = url
    link.target = '_blank'
    link.rel = 'noopener'
    link.click()
  }
  window.setTimeout(() => URL.revokeObjectURL(url), 60_000)
}

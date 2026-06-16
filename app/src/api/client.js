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

export async function api(path, options = {}) {
  const token = getToken()
  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers || {}),
  }

  if (token) {
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

  if (response.status === 401 && token) {
    setToken(null)
    onUnauthorized?.()
  }

  if (!response.ok) {
    const fallback =
      response.status === 500
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
  const token = getToken()
  const response = await fetch(`${API_BASE}/donations/${id}/receipt?format=pdf`, {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  })

  if (response.status === 401 && token) {
    setToken(null)
    onUnauthorized?.()
    throw new Error('Session expired. Please sign in again.')
  }

  if (!response.ok) {
    throw new Error('Unable to download receipt')
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

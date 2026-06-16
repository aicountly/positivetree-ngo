const API_BASE = '/api'

let authToken = localStorage.getItem('pt_token') || null

export function setToken(token) {
  authToken = token
  if (token) {
    localStorage.setItem('pt_token', token)
  } else {
    localStorage.removeItem('pt_token')
  }
}

export function getToken() {
  return authToken
}

export async function api(path, options = {}) {
  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers || {}),
  }

  if (authToken) {
    headers.Authorization = `Bearer ${authToken}`
  }

  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
  })

  const contentType = response.headers.get('content-type') || ''
  const isJson = contentType.includes('application/json')
  const data = isJson ? await response.json() : null

  if (!response.ok) {
    const error = new Error(data?.error || 'Request failed')
    error.status = response.status
    error.data = data
    throw error
  }

  return data
}

export function receiptUrl(id, format = 'pdf') {
  return `${API_BASE}/donations/${id}/receipt?format=${format}&token=${encodeURIComponent(authToken || '')}`
}

export async function downloadReceipt(id) {
  const response = await fetch(`${API_BASE}/donations/${id}/receipt?format=pdf`, {
    headers: authToken ? { Authorization: `Bearer ${authToken}` } : {},
  })

  if (!response.ok) {
    throw new Error('Unable to download receipt')
  }

  const blob = await response.blob()
  const url = URL.createObjectURL(blob)
  window.open(url, '_blank')
}

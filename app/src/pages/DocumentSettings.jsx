import { useEffect, useState } from 'react'
import { api, fetchSignaturePreviewBlob, previewDocument, uploadDocumentSignature } from '../api/client'
import { Alert, Button, Card, Input, Select, Textarea } from '../components/ui'

const DOCUMENT_LOGO_SRC = '/images/2023/07/logo-tree.svg'

const PAPER_OPTIONS = [
  { value: 'A4', label: 'A4' },
  { value: 'letter', label: 'Letter' },
]

const ORIENTATION_OPTIONS = [
  { value: 'portrait', label: 'Portrait' },
  { value: 'landscape', label: 'Landscape' },
]

const RECEIPT_FIELD_LABELS = {
  email: 'Donor email',
  phone: 'Donor phone',
  cause: 'Cause',
  channel: 'Channel',
  payment_method: 'Payment method',
  transaction_ref: 'Transaction reference',
  amount_words: 'Amount in words',
  payment_status: 'Payment status',
  donated_at: 'Receipt date',
  notes: 'Notes',
}

const CERTIFICATE_FIELD_LABELS = {
  receipt_number: 'Receipt number',
  certificate_number: 'Certificate number',
  cause: 'Cause',
  donated_at: 'Donation date',
  channel: 'Donation mode',
  payment_method: 'Payment method',
  donor_pan: 'Donor PAN',
  amount_words: 'Amount in words',
}

function emptySettings() {
  return {
    organization: {
      organization_name: '',
      tagline: '',
      address_lines: [''],
      phone: '',
      email: '',
      website: '',
      logo_filename: null,
    },
    receipt: {
      title: '',
      footer_text: '',
      banner_text: '',
      signature_name: '',
      signature_title: '',
      signature_filename: null,
      accent_color: '#20994D',
      brand_brown: '#986326',
      show_fields: {},
      print: {
        paper: 'A4',
        orientation: 'portrait',
        margin_top_mm: 15,
        margin_right_mm: 15,
        margin_bottom_mm: 15,
        margin_left_mm: 15,
      },
    },
    certificate: {
      title: '',
      opening_text: '',
      body_text: '',
      closing_text: '',
      eighty_g_registration_number: '',
      eighty_g_notes: [],
      signatory_name: '',
      signatory_title: '',
      signatory_label: '',
      accent_color: '#15803d',
      brand_brown: '#986326',
      show_fields: {},
      print: {
        paper: 'A4',
        orientation: 'portrait',
        margin_top_mm: 20,
        margin_right_mm: 20,
        margin_bottom_mm: 20,
        margin_left_mm: 20,
      },
    },
  }
}

function FieldCheckboxes({ labels, values, onChange }) {
  return (
    <div className="grid gap-2 sm:grid-cols-2">
      {Object.entries(labels).map(([key, label]) => (
        <label key={key} className="flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={Boolean(values?.[key])}
            onChange={(event) => onChange(key, event.target.checked)}
          />
          {label}
        </label>
      ))}
    </div>
  )
}

function PrintSettings({ value, onChange }) {
  function updatePrint(field, nextValue) {
    onChange({ ...value, [field]: nextValue })
  }

  return (
    <div className="grid gap-4 md:grid-cols-3">
      <Select label="Paper" value={value.paper} onChange={(e) => updatePrint('paper', e.target.value)}>
        {PAPER_OPTIONS.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </Select>
      <Select
        label="Orientation"
        value={value.orientation}
        onChange={(e) => updatePrint('orientation', e.target.value)}
      >
        {ORIENTATION_OPTIONS.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </Select>
      {['margin_top_mm', 'margin_right_mm', 'margin_bottom_mm', 'margin_left_mm'].map((field) => (
        <Input
          key={field}
          label={field.replace(/_/g, ' ')}
          type="number"
          min="0"
          value={value[field]}
          onChange={(e) => updatePrint(field, Number(e.target.value))}
        />
      ))}
    </div>
  )
}

export default function DocumentSettings() {
  const [tab, setTab] = useState('receipt')
  const [settings, setSettings] = useState(emptySettings())
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [uploadingSignature, setUploadingSignature] = useState(false)
  const [signaturePreview, setSignaturePreview] = useState('')

  useEffect(() => {
    let cancelled = false

    async function load() {
      setError('')
      try {
        const data = await api('/settings/documents')
        if (!cancelled) setSettings(data.settings)
      } catch (err) {
        if (!cancelled) setError(err.message)
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    load()
    return () => {
      cancelled = true
    }
  }, [])

  useEffect(() => {
    let cancelled = false
    let objectUrl = ''

    async function loadSignaturePreview() {
      if (!settings.receipt?.signature_filename) {
        setSignaturePreview('')
        return
      }

      try {
        const blob = await fetchSignaturePreviewBlob()
        if (cancelled) return
        objectUrl = URL.createObjectURL(blob)
        setSignaturePreview(objectUrl)
      } catch {
        if (!cancelled) setSignaturePreview('')
      }
    }

    loadSignaturePreview()
    return () => {
      cancelled = true
      if (objectUrl) URL.revokeObjectURL(objectUrl)
    }
  }, [settings.receipt?.signature_filename])

  function updateOrganization(field, value) {
    setSettings((current) => ({
      ...current,
      organization: { ...current.organization, [field]: value },
    }))
  }

  function updateAddressLine(index, value) {
    setSettings((current) => {
      const lines = [...(current.organization.address_lines || [''])]
      lines[index] = value
      return { ...current, organization: { ...current.organization, address_lines: lines } }
    })
  }

  function updateDocumentSection(section, field, value) {
    setSettings((current) => ({
      ...current,
      [section]: { ...current[section], [field]: value },
    }))
  }

  function updateShowField(section, field, checked) {
    setSettings((current) => ({
      ...current,
      [section]: {
        ...current[section],
        show_fields: { ...current[section].show_fields, [field]: checked },
      },
    }))
  }

  function updateEightyGNotes(value) {
    const notes = value
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean)
    updateDocumentSection('certificate', 'eighty_g_notes', notes)
  }

  async function handleSave(event) {
    event.preventDefault()
    setError('')
    setMessage('')
    setSubmitting(true)

    try {
      const result = await api('/settings/documents', {
        method: 'PUT',
        body: JSON.stringify({
          organization: settings.organization,
          receipt: settings.receipt,
          certificate: settings.certificate,
        }),
      })
      setSettings(result.settings)
      setMessage('Document settings saved.')
    } catch (err) {
      setError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  async function handleSignatureUpload(event) {
    const file = event.target.files?.[0]
    if (!file) return

    setError('')
    setMessage('')
    setUploadingSignature(true)

    try {
      const result = await uploadDocumentSignature(file)
      setSettings(result.settings)
      setMessage('Signature image uploaded.')
    } catch (err) {
      setError(err.message)
    } finally {
      setUploadingSignature(false)
      event.target.value = ''
    }
  }

  if (loading) {
    return <p className="text-slate-600">Loading document settings...</p>
  }

  const activeDoc = tab === 'receipt' ? settings.receipt : settings.certificate

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Document settings</h1>
        <p className="text-slate-600">Configure receipt and donation certificate PDFs</p>
      </div>

      <div className="flex gap-2">
        <Button
          type="button"
          variant={tab === 'receipt' ? 'primary' : 'secondary'}
          onClick={() => setTab('receipt')}
        >
          Receipt
        </Button>
        <Button
          type="button"
          variant={tab === 'certificate' ? 'primary' : 'secondary'}
          onClick={() => setTab('certificate')}
        >
          Certificate
        </Button>
      </div>

      {message && <Alert tone="success">{message}</Alert>}
      {error && <Alert tone="error">{error}</Alert>}

      <form className="space-y-6" onSubmit={handleSave}>
        <Card>
          <h2 className="mb-4 text-lg font-semibold">Organization</h2>
          <div className="space-y-4">
            <Input
              label="Organization name"
              value={settings.organization.organization_name}
              onChange={(e) => updateOrganization('organization_name', e.target.value)}
            />
            <Input
              label="Tagline"
              value={settings.organization.tagline || ''}
              onChange={(e) => updateOrganization('tagline', e.target.value)}
            />
            {(settings.organization.address_lines || ['']).map((line, index) => (
              <Input
                key={index}
                label={index === 0 ? 'Address line' : `Address line ${index + 1}`}
                value={line}
                onChange={(e) => updateAddressLine(index, e.target.value)}
              />
            ))}
            <div className="grid gap-4 md:grid-cols-3">
              <Input
                label="Phone"
                value={settings.organization.phone}
                onChange={(e) => updateOrganization('phone', e.target.value)}
              />
              <Input
                label="Email"
                value={settings.organization.email}
                onChange={(e) => updateOrganization('email', e.target.value)}
              />
              <Input
                label="Website"
                value={settings.organization.website}
                onChange={(e) => updateOrganization('website', e.target.value)}
              />
            </div>
            <div>
              <span className="mb-1 block text-sm font-medium text-slate-700">Logo</span>
              <img
                src={DOCUMENT_LOGO_SRC}
                alt="Positive Tree logo"
                className="mb-2 h-20 w-auto object-contain"
              />
              <p className="text-sm text-slate-600">
                Fixed organization logo used on all receipt and certificate PDFs.
              </p>
            </div>
          </div>
        </Card>

        <Card>
          <h2 className="mb-4 text-lg font-semibold">{tab === 'receipt' ? 'Receipt content' : 'Certificate content'}</h2>
          <div className="space-y-4">
            <Input
              label="Title"
              value={activeDoc.title}
              onChange={(e) => updateDocumentSection(tab, 'title', e.target.value)}
            />
            {tab === 'receipt' ? (
              <>
                <Textarea
                  label="Acknowledgement text"
                  value={settings.receipt.footer_text}
                  onChange={(e) => updateDocumentSection('receipt', 'footer_text', e.target.value)}
                />
                <Textarea
                  label="Bottom banner text"
                  value={settings.receipt.banner_text || ''}
                  onChange={(e) => updateDocumentSection('receipt', 'banner_text', e.target.value)}
                />
                <div className="grid gap-4 md:grid-cols-2">
                  <Input
                    label="Signature label"
                    value={settings.receipt.signature_name}
                    onChange={(e) => updateDocumentSection('receipt', 'signature_name', e.target.value)}
                  />
                  <Input
                    label="Signature organization line"
                    value={settings.receipt.signature_title}
                    onChange={(e) => updateDocumentSection('receipt', 'signature_title', e.target.value)}
                  />
                </div>
                <div>
                  <span className="mb-1 block text-sm font-medium text-slate-700">Signature image</span>
                  {signaturePreview && (
                    <img
                      src={signaturePreview}
                      alt="Uploaded signature"
                      className="mb-2 max-h-20 object-contain"
                    />
                  )}
                  <input
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    onChange={handleSignatureUpload}
                  />
                  {uploadingSignature && <p className="mt-2 text-sm text-slate-600">Uploading signature...</p>}
                  <p className="mt-2 text-sm text-slate-600">
                    Upload a PNG/JPG/WebP signature image used on receipt and certificate PDFs.
                  </p>
                </div>
                <div className="grid gap-4 md:grid-cols-2">
                  <Input
                    label="Green accent color"
                    type="color"
                    value={settings.receipt.accent_color}
                    onChange={(e) => updateDocumentSection('receipt', 'accent_color', e.target.value)}
                  />
                  <Input
                    label="Brown brand color"
                    type="color"
                    value={settings.receipt.brand_brown || '#986326'}
                    onChange={(e) => updateDocumentSection('receipt', 'brand_brown', e.target.value)}
                  />
                </div>
              </>
            ) : (
              <>
                <Textarea
                  label="Opening text"
                  value={settings.certificate.opening_text}
                  onChange={(e) => updateDocumentSection('certificate', 'opening_text', e.target.value)}
                />
                <Textarea
                  label="Body text"
                  value={settings.certificate.body_text}
                  onChange={(e) => updateDocumentSection('certificate', 'body_text', e.target.value)}
                />
                <Textarea
                  label="Closing text"
                  value={settings.certificate.closing_text}
                  onChange={(e) => updateDocumentSection('certificate', 'closing_text', e.target.value)}
                />
                <div className="grid gap-4 md:grid-cols-3">
                  <Input
                    label="Signatory label"
                    value={settings.certificate.signatory_label}
                    onChange={(e) => updateDocumentSection('certificate', 'signatory_label', e.target.value)}
                  />
                  <Input
                    label="Signatory name"
                    value={settings.certificate.signatory_name}
                    onChange={(e) => updateDocumentSection('certificate', 'signatory_name', e.target.value)}
                  />
                  <Input
                    label="Signatory title"
                    value={settings.certificate.signatory_title}
                    onChange={(e) => updateDocumentSection('certificate', 'signatory_title', e.target.value)}
                  />
                </div>
                <Input
                  label="80G registration number"
                  value={settings.certificate.eighty_g_registration_number || ''}
                  onChange={(e) =>
                    updateDocumentSection('certificate', 'eighty_g_registration_number', e.target.value)
                  }
                />
                <Textarea
                  label="80G bullet notes (one per line)"
                  value={(settings.certificate.eighty_g_notes || []).join('\n')}
                  onChange={(e) => updateEightyGNotes(e.target.value)}
                />
                <div className="grid gap-4 md:grid-cols-2">
                  <Input
                    label="Green accent color"
                    type="color"
                    value={settings.certificate.accent_color}
                    onChange={(e) => updateDocumentSection('certificate', 'accent_color', e.target.value)}
                  />
                  <Input
                    label="Brown brand color"
                    type="color"
                    value={settings.certificate.brand_brown || '#986326'}
                    onChange={(e) => updateDocumentSection('certificate', 'brand_brown', e.target.value)}
                  />
                </div>
                <p className="text-sm text-slate-600">
                  Certificate PDFs use the signature image uploaded on the Receipt tab.
                </p>
              </>
            )}
          </div>
        </Card>

        <Card>
          <h2 className="mb-4 text-lg font-semibold">Visible fields</h2>
          <FieldCheckboxes
            labels={tab === 'receipt' ? RECEIPT_FIELD_LABELS : CERTIFICATE_FIELD_LABELS}
            values={activeDoc.show_fields}
            onChange={(field, checked) => updateShowField(tab, field, checked)}
          />
        </Card>

        <Card>
          <h2 className="mb-4 text-lg font-semibold">Print settings</h2>
          <PrintSettings
            value={activeDoc.print}
            onChange={(print) => updateDocumentSection(tab, 'print', print)}
          />
        </Card>

        <div className="flex flex-wrap gap-3">
          <Button type="submit" disabled={submitting}>
            {submitting ? 'Saving...' : 'Save settings'}
          </Button>
          <Button type="button" variant="secondary" onClick={async () => {
            try {
              await previewDocument(tab)
            } catch (err) {
              setError(err.message)
            }
          }}>
            Preview PDF
          </Button>
        </div>
      </form>
    </div>
  )
}

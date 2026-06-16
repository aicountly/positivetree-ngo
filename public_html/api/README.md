# Positive Tree Donation API

PHP + SQLite REST API for donation receipt management and Razorpay online payments.

## Requirements

- PHP 8.1+
- Composer
- SQLite extension enabled
- GD extension with PNG support (recommended for receipt/certificate logo transparency in PDFs)
- Writable `data/` directory

## Local development

From the repository root:

```bash
npm run copy:api
npm run dev:api
```

In another terminal:

```bash
npm run dev:app
```

The API is served at `http://localhost:8080/api/` and the admin app at `http://localhost:5173/app/`.

Create `public_html/api/.env` from `api/.env.example`:

```env
JWT_SECRET=your-long-random-secret
RAZORPAY_KEY_ID=rzp_test_...
RAZORPAY_KEY_SECRET=...
RAZORPAY_WEBHOOK_SECRET=...
APP_ENV=development
CORS_ORIGIN=http://localhost:5173
```

## First-time setup

1. Visit `http://localhost:5173/app/setup`
2. Create the superadmin account
3. Sign in and start recording donations

## Production (cPanel)

1. Deploy via rsync — CI runs `npm run copy:api` so `public_html/api/vendor/` is included
2. On the server, create `public_html/api/.env` from `api/.env.example`
3. Set `CORS_ORIGIN` to your live site origin (e.g. `https://aicountly.co.in`)
4. Ensure `public_html/api/data/` is writable by PHP:
   ```bash
   mkdir -p public_html/api/data
   chmod 775 public_html/api/data
   ```
5. Visit `https://your-domain/app/setup` once to create the superadmin

**Important:** Deploy rsync must exclude `api/data/` and `api/.env` so production SQLite and secrets are never deleted. If `donations.sqlite` is missing, the API returns `setup_required: true` and `/app/setup` becomes reachable again.

Setup is allowed only when no superadmin exists. `POST /api/setup` returns `409 Setup already completed` once a superadmin row is present.

### Troubleshooting HTTP 500 on `/api/setup/status`

| Symptom | Cause | Fix |
|---------|-------|-----|
| Empty 500 response | Missing `vendor/` (Composer deps) | Run `npm run copy:api` locally, or on server: `cd public_html/api && composer install --no-dev` |
| JSON: data directory not writable | SQLite permissions | `chmod 775 public_html/api/data` |
| JSON: PDO SQLite not enabled | PHP extension missing | Enable `pdo_sqlite` in cPanel → Select PHP Version → Extensions |
| Logo shows black box on PDF | GD missing or PNG matte | Enable `gd` in PHP; bundled logo is normalized when GD is available |

Quick check after deploy:

```bash
curl -s https://your-domain/api/setup/status
# Expected: {"setup_required":true} or {"setup_required":false}
```

## API endpoints

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/setup/status` | Public | Check if setup is required |
| POST | `/api/setup` | Public (once) | Create initial superadmin |
| POST | `/api/auth/login` | Public | Login, returns JWT |
| GET | `/api/auth/me` | JWT | Current user |
| GET | `/api/dashboard` | JWT | Dashboard stats |
| GET | `/api/donations/causes` | JWT | Valid donation causes |
| GET/POST | `/api/donations` | JWT | List / create offline donation |
| GET/PUT | `/api/donations/{id}` | JWT | View / edit donation (online: PAN only) |
| GET | `/api/donations/{id}/receipt` | JWT | PDF/HTML receipt (configured template) |
| GET | `/api/donations/{id}/certificate` | JWT | PDF/HTML certificate (approved only) |
| POST | `/api/donations/{id}/approve-certificate` | admin+ | Approve donation for certificate |
| POST | `/api/donations/{id}/revoke-certificate` | superadmin | Revoke certificate approval |
| GET | `/api/public/receipt/{token}` | Public | PDF/HTML receipt via public token |
| GET | `/api/settings/documents` | admin+ | Document settings (receipt + certificate) |
| PUT | `/api/settings/documents` | admin+ | Save document settings |
| POST | `/api/settings/documents/logo` | admin+ | Upload organization logo |
| GET | `/api/settings/documents/preview/receipt` | admin+ | Preview receipt PDF |
| GET | `/api/settings/documents/preview/certificate` | admin+ | Preview certificate PDF |
| GET | `/api/users` | superadmin | List users |
| GET | `/api/users/{id}` | superadmin | Get user |
| POST | `/api/users` | superadmin | Create admin/viewer user |
| PUT/PATCH | `/api/users/{id}` | superadmin | Update user |
| GET | `/api/payments/razorpay/config` | Public | Razorpay public key |
| POST | `/api/payments/razorpay/order` | Public | Create payment order |
| POST | `/api/payments/razorpay/verify` | Public | Verify payment |
| POST | `/api/webhooks/razorpay` | Webhook | Payment capture/failure handler |

## Roles

- **superadmin** — manage users and document settings (`/app/settings/documents`)
- **admin** — manage donations, donor PAN, certificate approval, pending certificates queue, and document PDF settings (`/app/settings/documents`)
- **viewer** — read-only access to donations, receipts, and approved certificates

## Donor PAN and certificates

- Donor PAN is optional when recording or accepting a donation (online donate form, offline admin form).
- PAN must be present and valid before Accounts can approve a certificate (`POST /api/donations/{id}/approve-certificate` returns `422` if missing).
- Admins can add or edit PAN on any completed donation (online donations allow PAN-only updates via `PUT /api/donations/{id}`).
- Pending certificate queue: `/app/certificates/pending` — filter with `GET /api/donations?certificate_pending=1&pan_status=missing|present`.

## Document settings

Superadmins and admins configure receipt and donation certificate PDFs at `/app/settings/documents`:

- Organization details and bundled logo (`api/assets/documents/logo.png`)
- The bundled logo is **auto-normalized for PDF output** (near-black matte removal + proper alpha re-encode via GD). Enable the PHP GD extension on the server for transparent logos in receipts and certificates; without GD, the raw PNG bytes are embedded as-is.
- Receipt/certificate wording, 80G notes, visible fields, shared signature upload, and print margins
- Live PDF preview before saving

Receipts are available immediately when a donation is completed. Donation certificates require a valid donor PAN and Accounts Team approval (`POST /api/donations/{id}/approve-certificate`) before download.

Online donors receive a public receipt download link on the thank-you screen via `public_receipt_token` returned from payment verification.

## Security notes

- `.htaccess` blocks direct access to `.env`, `data/`, and schema files on Apache
- Online donations cannot be edited manually in the admin portal except for donor PAN
- Razorpay webhooks verify HMAC signatures against the raw request body
- JWT must use HS256

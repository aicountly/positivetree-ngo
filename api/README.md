# Positive Tree Donation API

PHP + SQLite REST API for donation receipt management and Razorpay online payments.

## Requirements

- PHP 8.1+
- Composer
- SQLite extension enabled
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

1. Deploy via rsync (`public_html/` includes `api/` after `npm run copy:api`)
2. On the server, create `public_html/api/.env` with production secrets
3. Ensure `public_html/api/data/` is writable by PHP:
   ```bash
   chmod 750 public_html/api/data
   ```
4. Visit `https://positivetree.ngo/app/setup` once to create the superadmin
5. Configure Razorpay webhook:
   - URL: `https://positivetree.ngo/api/webhooks/razorpay`
   - Events: `payment.captured`, `payment.failed`

**Note:** rsync excludes `.env`, and `npm run copy:api` preserves existing `public_html/api/.env` and SQLite files.

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
| GET/PUT | `/api/donations/{id}` | JWT | View / edit offline donation |
| GET | `/api/donations/{id}/receipt` | JWT | PDF receipt |
| GET | `/api/users` | superadmin | List users |
| GET | `/api/users/{id}` | superadmin | Get user |
| POST | `/api/users` | superadmin | Create admin/viewer user |
| PUT/PATCH | `/api/users/{id}` | superadmin | Update user |
| GET | `/api/payments/razorpay/config` | Public | Razorpay public key |
| POST | `/api/payments/razorpay/order` | Public | Create payment order |
| POST | `/api/payments/razorpay/verify` | Public | Verify payment |
| POST | `/api/webhooks/razorpay` | Webhook | Payment capture/failure handler |

## Roles

- **superadmin** — manage users and donations
- **admin** — manage offline donations
- **viewer** — read-only access to donations and receipts

## Security notes

- `.htaccess` blocks direct access to `.env`, `data/`, and schema files on Apache
- Online donations cannot be edited manually in the admin portal
- Razorpay webhooks verify HMAC signatures against the raw request body
- JWT must use HS256

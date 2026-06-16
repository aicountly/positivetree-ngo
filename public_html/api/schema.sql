CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('superadmin', 'admin', 'viewer')),
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS receipt_sequences (
    year INTEGER PRIMARY KEY,
    last_number INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS certificate_sequences (
    year INTEGER PRIMARY KEY,
    last_number INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS document_settings (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    receipt_settings TEXT NOT NULL,
    certificate_settings TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    updated_by INTEGER REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS donations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_number TEXT UNIQUE,
    donor_name TEXT NOT NULL,
    donor_email TEXT,
    donor_phone TEXT,
    donor_pan TEXT,
    amount_paise INTEGER NOT NULL,
    currency TEXT NOT NULL DEFAULT 'INR',
    channel TEXT NOT NULL CHECK (channel IN ('online', 'offline')),
    cause TEXT NOT NULL,
    payment_method TEXT,
    transaction_ref TEXT,
    razorpay_order_id TEXT,
    razorpay_payment_id TEXT UNIQUE,
    status TEXT NOT NULL CHECK (status IN ('pending', 'completed', 'failed', 'refunded')),
    notes TEXT,
    donated_at TEXT NOT NULL,
    created_by INTEGER REFERENCES users(id),
    certificate_number TEXT UNIQUE,
    certificate_status TEXT NOT NULL DEFAULT 'pending' CHECK (certificate_status IN ('pending', 'approved', 'not_required')),
    certificate_approved_at TEXT,
    certificate_approved_by INTEGER REFERENCES users(id),
    public_receipt_token TEXT UNIQUE,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_donations_status ON donations(status);
CREATE INDEX IF NOT EXISTS idx_donations_channel ON donations(channel);
CREATE INDEX IF NOT EXISTS idx_donations_donated_at ON donations(donated_at);
CREATE INDEX IF NOT EXISTS idx_donations_receipt_number ON donations(receipt_number);
CREATE INDEX IF NOT EXISTS idx_donations_certificate_status ON donations(certificate_status);
CREATE INDEX IF NOT EXISTS idx_donations_public_receipt_token ON donations(public_receipt_token);

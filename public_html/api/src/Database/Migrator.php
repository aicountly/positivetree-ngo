<?php

declare(strict_types=1);

namespace App\Database;

class Migrator
{
    public static function run(\PDO $pdo): void
    {
        self::ensureColumn($pdo, 'donations', 'certificate_number', 'TEXT');
        self::ensureColumn($pdo, 'donations', 'certificate_status', "TEXT NOT NULL DEFAULT 'pending'");
        self::ensureColumn($pdo, 'donations', 'certificate_approved_at', 'TEXT');
        self::ensureColumn($pdo, 'donations', 'certificate_approved_by', 'INTEGER');
        self::ensureColumn($pdo, 'donations', 'public_receipt_token', 'TEXT');
        self::ensureColumn($pdo, 'donations', 'donor_pan', 'TEXT');

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS certificate_sequences (
                year INTEGER PRIMARY KEY,
                last_number INTEGER NOT NULL DEFAULT 0
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS document_settings (
                id INTEGER PRIMARY KEY CHECK (id = 1),
                receipt_settings TEXT NOT NULL,
                certificate_settings TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                updated_by INTEGER REFERENCES users(id)
            )'
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_donations_certificate_status ON donations(certificate_status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_donations_public_receipt_token ON donations(public_receipt_token)');

        $uploadsDir = dirname(__DIR__, 2) . '/data/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0775, true);
        }

        self::backfillDonations($pdo);
        self::ensureSetupLock($pdo);
    }

    private static function ensureSetupLock(\PDO $pdo): void
    {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin'")->fetchColumn();
        if ($count > 0) {
            \App\SetupLock::markCompleted();
        }
    }

    private static function ensureColumn(\PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->query("PRAGMA table_info({$table})");
        $columns = $stmt->fetchAll();
        foreach ($columns as $col) {
            if (($col['name'] ?? '') === $column) {
                return;
            }
        }

        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    private static function backfillDonations(\PDO $pdo): void
    {
        $rows = $pdo->query(
            "SELECT id FROM donations
             WHERE (certificate_status IS NULL OR certificate_status = '')
                OR (status = 'completed' AND (public_receipt_token IS NULL OR public_receipt_token = ''))"
        )->fetchAll();

        foreach ($rows as $row) {
            $updates = [];
            $params = ['id' => (int) $row['id']];

            $current = $pdo->prepare('SELECT status, certificate_status, public_receipt_token FROM donations WHERE id = :id');
            $current->execute(['id' => (int) $row['id']]);
            $donation = $current->fetch();
            if (!$donation) {
                continue;
            }

            if (empty($donation['certificate_status'])) {
                $updates[] = "certificate_status = 'pending'";
            }

            if ($donation['status'] === 'completed' && empty($donation['public_receipt_token'])) {
                $updates[] = 'public_receipt_token = :public_receipt_token';
                $params['public_receipt_token'] = bin2hex(random_bytes(16));
            }

            if ($updates === []) {
                continue;
            }

            $sql = 'UPDATE donations SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $pdo->prepare($sql)->execute($params);
        }
    }
}

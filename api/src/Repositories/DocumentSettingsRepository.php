<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;

class DocumentSettingsRepository
{
    /** @return array{organization: array, receipt: array, certificate: array, updated_at: ?string, updated_by: ?int} */
    public function get(): array
    {
        $defaults = require __DIR__ . '/../../config/document_defaults.php';
        $stmt = Database::connection()->query('SELECT * FROM document_settings WHERE id = 1');
        $row = $stmt->fetch();

        if (!$row) {
            $this->seedDefaults($defaults);
            return [
                'organization' => $defaults['organization'],
                'receipt' => $defaults['receipt'],
                'certificate' => $defaults['certificate'],
                'updated_at' => null,
                'updated_by' => null,
            ];
        }

        $receipt = json_decode($row['receipt_settings'], true) ?: [];
        $certificate = json_decode($row['certificate_settings'], true) ?: [];

        $organization = $this->normalizeOrganization(
            $receipt['organization'] ?? $certificate['organization'] ?? [],
            $defaults['organization']
        );

        $receiptDoc = $receipt;
        unset($receiptDoc['organization']);
        $certificateDoc = $certificate;
        unset($certificateDoc['organization']);

        return [
            'organization' => $organization,
            'receipt' => array_replace_recursive($defaults['receipt'], $receiptDoc),
            'certificate' => array_replace_recursive($defaults['certificate'], $certificateDoc),
            'updated_at' => $row['updated_at'],
            'updated_by' => $row['updated_by'] !== null ? (int) $row['updated_by'] : null,
        ];
    }

    /** @param array<string, mixed> $payload */
    public function save(array $payload, ?int $userId): array
    {
        $defaults = require __DIR__ . '/../../config/document_defaults.php';
        $organization = $this->normalizeOrganization(
            $payload['organization'] ?? [],
            $defaults['organization']
        );
        $receipt = array_replace_recursive(
            $defaults['receipt'],
            $payload['receipt'] ?? []
        );
        $certificate = array_replace_recursive(
            $defaults['certificate'],
            $payload['certificate'] ?? []
        );

        $receipt['organization'] = $organization;
        $certificate['organization'] = $organization;

        $now = nowIso();
        $stmt = Database::connection()->prepare(
            'INSERT INTO document_settings (id, receipt_settings, certificate_settings, updated_at, updated_by)
             VALUES (1, :receipt_settings, :certificate_settings, :updated_at, :updated_by)
             ON CONFLICT(id) DO UPDATE SET
                receipt_settings = excluded.receipt_settings,
                certificate_settings = excluded.certificate_settings,
                updated_at = excluded.updated_at,
                updated_by = excluded.updated_by'
        );
        $stmt->execute([
            'receipt_settings' => json_encode($receipt, JSON_THROW_ON_ERROR),
            'certificate_settings' => json_encode($certificate, JSON_THROW_ON_ERROR),
            'updated_at' => $now,
            'updated_by' => $userId,
        ]);

        return $this->get();
    }

    public function saveSignatureFilename(string $filename, ?int $userId): array
    {
        $settings = $this->get();
        $settings['receipt']['signature_filename'] = basename($filename);

        return $this->save([
            'organization' => $settings['organization'],
            'receipt' => $settings['receipt'],
            'certificate' => $settings['certificate'],
        ], $userId);
    }

    public function signatureFilename(): ?string
    {
        $settings = $this->get();
        $filename = $settings['receipt']['signature_filename'] ?? null;

        return $filename ? basename((string) $filename) : null;
    }

    public function saveLogoFilename(string $filename, ?int $userId): array
    {
        return $this->get();
    }

    /** @param array<string, mixed> $organization */
    private function normalizeOrganization(array $organization, array $defaults): array
    {
        $normalized = array_replace_recursive($defaults, $organization);
        $normalized['logo_filename'] = $defaults['logo_filename'];

        return $normalized;
    }

    /** @param array<string, mixed> $defaults */
    private function seedDefaults(array $defaults): void
    {
        $receipt = $defaults['receipt'];
        $certificate = $defaults['certificate'];
        $receipt['organization'] = $defaults['organization'];
        $certificate['organization'] = $defaults['organization'];

        $stmt = Database::connection()->prepare(
            'INSERT OR IGNORE INTO document_settings (id, receipt_settings, certificate_settings, updated_at, updated_by)
             VALUES (1, :receipt_settings, :certificate_settings, :updated_at, NULL)'
        );
        $stmt->execute([
            'receipt_settings' => json_encode($receipt, JSON_THROW_ON_ERROR),
            'certificate_settings' => json_encode($certificate, JSON_THROW_ON_ERROR),
            'updated_at' => nowIso(),
        ]);
    }
}

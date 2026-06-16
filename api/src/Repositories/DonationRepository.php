<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;

class DonationRepository
{
    /** @return array{items: array<int, array>, total: int} */
    public function list(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['channel'])) {
            $where[] = 'channel = :channel';
            $params['channel'] = $filters['channel'];
        }
        if (!empty($filters['cause'])) {
            $where[] = 'cause = :cause';
            $params['cause'] = $filters['cause'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'donated_at >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'donated_at <= :to';
            $params['to'] = $filters['to'] . 'T23:59:59Z';
        }
        if (!empty($filters['search'])) {
            $where[] = '(donor_name LIKE :search OR donor_email LIKE :search OR receipt_number LIKE :search OR transaction_ref LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sqlWhere = implode(' AND ', $where);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $countStmt = Database::connection()->prepare("SELECT COUNT(*) FROM donations WHERE {$sqlWhere}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT * FROM donations WHERE {$sqlWhere} ORDER BY donated_at DESC, id DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map([$this, 'format'], $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM donations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->format($row) : null;
    }

    public function findRawById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM donations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByRazorpayPaymentId(string $paymentId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM donations WHERE razorpay_payment_id = :id');
        $stmt->execute(['id' => $paymentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByRazorpayOrderId(string $orderId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM donations WHERE razorpay_order_id = :id');
        $stmt->execute(['id' => $orderId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): array
    {
        $now = nowIso();
        $stmt = Database::connection()->prepare(
            'INSERT INTO donations (
                receipt_number, donor_name, donor_email, donor_phone, amount_paise, currency,
                channel, cause, payment_method, transaction_ref, razorpay_order_id, razorpay_payment_id,
                status, notes, donated_at, created_by, created_at, updated_at
            ) VALUES (
                :receipt_number, :donor_name, :donor_email, :donor_phone, :amount_paise, :currency,
                :channel, :cause, :payment_method, :transaction_ref, :razorpay_order_id, :razorpay_payment_id,
                :status, :notes, :donated_at, :created_by, :created_at, :updated_at
            )'
        );

        $stmt->execute([
            'receipt_number' => $data['receipt_number'] ?? null,
            'donor_name' => trim($data['donor_name']),
            'donor_email' => $data['donor_email'] ?? null,
            'donor_phone' => $data['donor_phone'] ?? null,
            'amount_paise' => (int) $data['amount_paise'],
            'currency' => $data['currency'] ?? 'INR',
            'channel' => $data['channel'],
            'cause' => $data['cause'],
            'payment_method' => $data['payment_method'] ?? null,
            'transaction_ref' => $data['transaction_ref'] ?? null,
            'razorpay_order_id' => $data['razorpay_order_id'] ?? null,
            'razorpay_payment_id' => $data['razorpay_payment_id'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'donated_at' => $data['donated_at'] ?? $now,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById((int) Database::connection()->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $existing = $this->findRawById($id);
        if ($existing === null) {
            return null;
        }

        $fields = [];
        $params = ['id' => $id];
        $allowed = [
            'donor_name', 'donor_email', 'donor_phone', 'amount_paise', 'cause',
            'payment_method', 'transaction_ref', 'status', 'notes', 'donated_at', 'receipt_number',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if ($fields === []) {
            return $this->findById($id);
        }

        $fields[] = 'updated_at = :updated_at';
        $params['updated_at'] = nowIso();

        $sql = 'UPDATE donations SET ' . implode(', ', $fields) . ' WHERE id = :id';
        Database::connection()->prepare($sql)->execute($params);

        return $this->findById($id);
    }

    public function completeOnline(int $id, string $receiptNumber, string $paymentId, ?string $transactionRef = null): ?array
    {
        $stmt = Database::connection()->prepare(
            'UPDATE donations SET
                receipt_number = :receipt_number,
                razorpay_payment_id = :razorpay_payment_id,
                transaction_ref = :transaction_ref,
                status = :status,
                payment_method = :payment_method,
                updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'receipt_number' => $receiptNumber,
            'razorpay_payment_id' => $paymentId,
            'transaction_ref' => $transactionRef ?? $paymentId,
            'status' => 'completed',
            'payment_method' => 'razorpay',
            'updated_at' => nowIso(),
            'id' => $id,
        ]);

        return $this->findById($id);
    }

    /** @return array{total_amount_paise: int, online_count: int, offline_count: int, recent: array<int, array>} */
    public function dashboardStats(): array
    {
        $pdo = Database::connection();

        $totals = $pdo->query(
            "SELECT
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount_paise ELSE 0 END), 0) AS total_amount_paise,
                COALESCE(SUM(CASE WHEN status = 'completed' AND channel = 'online' THEN 1 ELSE 0 END), 0) AS online_count,
                COALESCE(SUM(CASE WHEN status = 'completed' AND channel = 'offline' THEN 1 ELSE 0 END), 0) AS offline_count
             FROM donations"
        )->fetch();

        $recentStmt = $pdo->query(
            "SELECT * FROM donations WHERE status = 'completed' ORDER BY donated_at DESC LIMIT 10"
        );

        return [
            'total_amount_paise' => (int) $totals['total_amount_paise'],
            'online_count' => (int) $totals['online_count'],
            'offline_count' => (int) $totals['offline_count'],
            'recent' => array_map([$this, 'format'], $recentStmt->fetchAll()),
        ];
    }

    private function format(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'receipt_number' => $row['receipt_number'],
            'donor_name' => $row['donor_name'],
            'donor_email' => $row['donor_email'],
            'donor_phone' => $row['donor_phone'],
            'amount_paise' => (int) $row['amount_paise'],
            'amount_inr' => round((int) $row['amount_paise'] / 100, 2),
            'currency' => $row['currency'],
            'channel' => $row['channel'],
            'cause' => $row['cause'],
            'payment_method' => $row['payment_method'],
            'transaction_ref' => $row['transaction_ref'],
            'razorpay_order_id' => $row['razorpay_order_id'],
            'razorpay_payment_id' => $row['razorpay_payment_id'],
            'status' => $row['status'],
            'notes' => $row['notes'],
            'donated_at' => $row['donated_at'],
            'created_by' => $row['created_by'] !== null ? (int) $row['created_by'] : null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}

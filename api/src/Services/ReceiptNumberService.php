<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

class ReceiptNumberService
{
    public function next(): string
    {
        $pdo = Database::connection();
        $year = (int) date('Y');

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT last_number FROM receipt_sequences WHERE year = :year');
            $stmt->execute(['year' => $year]);
            $row = $stmt->fetch();

            if ($row) {
                $next = (int) $row['last_number'] + 1;
                $update = $pdo->prepare('UPDATE receipt_sequences SET last_number = :num WHERE year = :year');
                $update->execute(['num' => $next, 'year' => $year]);
            } else {
                $next = 1;
                $insert = $pdo->prepare('INSERT INTO receipt_sequences (year, last_number) VALUES (:year, :num)');
                $insert->execute(['year' => $year, 'num' => $next]);
            }

            $pdo->commit();
            return sprintf('PT-%d-%05d', $year, $next);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

class CertificateNumberService
{
    public function next(): string
    {
        $pdo = Database::connection();
        $year = (int) date('Y');

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT last_number FROM certificate_sequences WHERE year = :year');
            $stmt->execute(['year' => $year]);
            $row = $stmt->fetch();

            if ($row) {
                $next = (int) $row['last_number'] + 1;
                $update = $pdo->prepare('UPDATE certificate_sequences SET last_number = :num WHERE year = :year');
                $update->execute(['num' => $next, 'year' => $year]);
            } else {
                $next = 1;
                $insert = $pdo->prepare('INSERT INTO certificate_sequences (year, last_number) VALUES (:year, :num)');
                $insert->execute(['year' => $year, 'num' => $next]);
            }

            $pdo->commit();
            return sprintf('PT-CERT-%d-%05d', $year, $next);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

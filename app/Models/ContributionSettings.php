<?php
namespace App\Models;

class ContributionSettings
{
    public static function get(int $weddingId): array
    {
        $stmt = db()->prepare(
            'SELECT contribution_target AS target, contribution_deadline AS deadline,
                    contribution_description AS description, payment_info
             FROM wedding_settings
             WHERE wedding_id = :wedding
             LIMIT 1'
        );
        $stmt->execute([':wedding' => $weddingId]);

        return ($stmt->fetch() ?: []) + [
            'target'       => null,
            'deadline'     => null,
            'description'  => null,
            'payment_info' => null,
        ];
    }

    public static function save(int $weddingId, array $data): void
    {
        $stmt = db()->prepare(
            'INSERT INTO wedding_settings
                (wedding_id, contribution_target, contribution_deadline, contribution_description, payment_info)
             VALUES (:wedding, :target, :deadline, :description, :payment)
             ON DUPLICATE KEY UPDATE
                contribution_target = VALUES(contribution_target),
                contribution_deadline = VALUES(contribution_deadline),
                contribution_description = VALUES(contribution_description),
                payment_info = VALUES(payment_info)'
        );
        $stmt->execute([
            ':wedding'     => $weddingId,
            ':target'      => $data['target'],
            ':deadline'    => $data['deadline'],
            ':description' => $data['description'],
            ':payment'     => $data['payment_info'],
        ]);
    }
}
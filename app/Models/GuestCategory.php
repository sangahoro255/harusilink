<?php
namespace App\Models;

class GuestCategory
{
    public const DEFAULTS = ['family', 'friends', 'colleagues', 'church', 'vip', 'other'];

    public static function forWedding(int $weddingId): array
    {
        $pdo = db();

        $count = $pdo->prepare('SELECT COUNT(*) FROM guest_categories WHERE wedding_id = :wedding');
        $count->execute([':wedding' => $weddingId]);

        if ((int) $count->fetchColumn() === 0) {
            $insert = $pdo->prepare('INSERT IGNORE INTO guest_categories (wedding_id, code) VALUES (:wedding, :code)');
            foreach (self::DEFAULTS as $code) {
                $insert->execute([':wedding' => $weddingId, ':code' => $code]);
            }
        }

        $stmt = $pdo->prepare('SELECT id, code, name FROM guest_categories WHERE wedding_id = :wedding ORDER BY id');
        $stmt->execute([':wedding' => $weddingId]);
        return $stmt->fetchAll();
    }

    public static function belongsToWedding(int $id, int $weddingId): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM guest_categories WHERE id = :id AND wedding_id = :wedding LIMIT 1');
        $stmt->execute([':id' => $id, ':wedding' => $weddingId]);
        return (bool) $stmt->fetchColumn();
    }
}
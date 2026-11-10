<?php
namespace App\Services;

class WeddingStats
{
    public static function forWedding(int $weddingId, int $ownerId): array
    {
        $stats = [
            'guests'         => 0,
            'accepted'       => 0,
            'declined'       => 0,
            'pending'        => 0,
            'accepted_seats' => 0,
            'checked_in'     => 0,
            'contributions'  => 0.0,
        ];

        $pdo = db();

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS guests,
                    COALESCE(SUM(g.rsvp_status = 'accepted'), 0) AS accepted,
                    COALESCE(SUM(g.rsvp_status = 'declined'), 0) AS declined,
                    COALESCE(SUM(g.rsvp_status = 'pending'), 0) AS pending,
                    COALESCE(SUM(CASE WHEN g.rsvp_status = 'accepted' THEN g.seats ELSE 0 END), 0) AS accepted_seats
             FROM guests g
             JOIN weddings w ON w.id = g.wedding_id
             WHERE g.wedding_id = :wedding AND w.owner_id = :owner AND g.deleted_at IS NULL"
        );
        $stmt->execute([':wedding' => $weddingId, ':owner' => $ownerId]);
        $row = $stmt->fetch();

        foreach (['guests', 'accepted', 'declined', 'pending', 'accepted_seats'] as $key) {
            $stats[$key] = (int) $row[$key];
        }

        $checked = $pdo->prepare(
            'SELECT COUNT(*)
             FROM guest_checkins c
             JOIN guests g ON g.id = c.guest_id AND g.wedding_id = c.wedding_id AND g.deleted_at IS NULL
             JOIN weddings w ON w.id = c.wedding_id
             WHERE c.wedding_id = :wedding AND w.owner_id = :owner'
        );
        $checked->execute([':wedding' => $weddingId, ':owner' => $ownerId]);
        $stats['checked_in'] = (int) $checked->fetchColumn();

        $money = $pdo->prepare(
            "SELECT COALESCE(SUM(c.amount), 0)
             FROM contributions c
             JOIN weddings w ON w.id = c.wedding_id
             WHERE c.wedding_id = :wedding AND w.owner_id = :owner
               AND c.status = 'paid' AND c.deleted_at IS NULL"
        );
        $money->execute([':wedding' => $weddingId, ':owner' => $ownerId]);
        $stats['contributions'] = (float) $money->fetchColumn();

        return $stats;
    }
}
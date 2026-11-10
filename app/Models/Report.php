<?php
namespace App\Models;

class Report
{
    public static function categories(int $weddingId): array
    {
        $stmt = db()->prepare(
            "SELECT COALESCE(c.code, 'none') AS code, c.name AS name,
                    COUNT(g.id) AS guests, COALESCE(SUM(g.seats), 0) AS seats,
                    COALESCE(SUM(g.rsvp_status = 'accepted'), 0) AS accepted,
                    COALESCE(SUM(g.rsvp_status = 'declined'), 0) AS declined,
                    COALESCE(SUM(g.rsvp_status = 'pending'), 0) AS pending
             FROM guests g
             LEFT JOIN guest_categories c ON c.id = g.category_id AND c.wedding_id = g.wedding_id
             WHERE g.wedding_id = :wedding AND g.deleted_at IS NULL
             GROUP BY c.id, c.code, c.name
             ORDER BY guests DESC"
        );
        $stmt->execute([':wedding' => $weddingId]);

        return $stmt->fetchAll();
    }

    public static function guestRows(int $weddingId): array
    {
        $stmt = db()->prepare(
            "SELECT g.full_name, g.phone, g.email, g.seats, COALESCE(c.code, '') AS category,
                    g.rsvp_status, COALESCE(k.checked_in_at, '') AS checked_in_at
             FROM guests g
             LEFT JOIN guest_categories c ON c.id = g.category_id AND c.wedding_id = g.wedding_id
             LEFT JOIN guest_checkins k ON k.guest_id = g.id
             WHERE g.wedding_id = :wedding AND g.deleted_at IS NULL
             ORDER BY g.full_name ASC, g.id ASC"
        );
        $stmt->execute([':wedding' => $weddingId]);

        return $stmt->fetchAll();
    }

    public static function contributionRows(int $weddingId): array
    {
        $stmt = db()->prepare(
            "SELECT contributor_name, phone, amount, method, reference, status, contributed_on, note
             FROM contributions
             WHERE wedding_id = :wedding AND deleted_at IS NULL
             ORDER BY contributed_on ASC, id ASC"
        );
        $stmt->execute([':wedding' => $weddingId]);

        return $stmt->fetchAll();
    }

    public static function checkinRows(int $weddingId): array
    {
        $stmt = db()->prepare(
            'SELECT g.full_name, k.seats, k.method, k.checked_in_at
             FROM guest_checkins k
             JOIN guests g ON g.id = k.guest_id AND g.wedding_id = k.wedding_id AND g.deleted_at IS NULL
             WHERE k.wedding_id = :wedding
             ORDER BY k.checked_in_at ASC, k.id ASC'
        );
        $stmt->execute([':wedding' => $weddingId]);

        return $stmt->fetchAll();
    }
}
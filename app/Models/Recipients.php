<?php
namespace App\Models;

class Recipients
{
    public static function counts(int $weddingId): array
    {
        $stmt = db()->prepare(
            "SELECT COALESCE(SUM(phone IS NOT NULL AND phone <> ''), 0) AS al,
                    COALESCE(SUM(phone IS NOT NULL AND phone <> '' AND rsvp_status = 'accepted'), 0) AS accepted,
                    COALESCE(SUM(phone IS NOT NULL AND phone <> '' AND rsvp_status = 'pending'), 0) AS pending,
                    COALESCE(SUM(phone IS NOT NULL AND phone <> '' AND rsvp_status = 'declined'), 0) AS declined,
                    COALESCE(SUM(phone IS NULL OR phone = ''), 0) AS no_phone
             FROM guests
             WHERE wedding_id = :wedding AND deleted_at IS NULL"
        );
        $stmt->execute([':wedding' => $weddingId]);
        $row = $stmt->fetch();

        return [
            'all'      => (int) $row['al'],
            'accepted' => (int) $row['accepted'],
            'pending'  => (int) $row['pending'],
            'declined' => (int) $row['declined'],
            'no_phone' => (int) $row['no_phone'],
        ];
    }

    public static function guests(int $weddingId, string $audience): array
    {
        $where = "wedding_id = :wedding AND deleted_at IS NULL AND phone IS NOT NULL AND phone <> ''";
        $params = [':wedding' => $weddingId];

        if (in_array($audience, ['accepted', 'pending', 'declined'], true)) {
            $where .= ' AND rsvp_status = :status';
            $params[':status'] = $audience;
        }

        $stmt = db()->prepare("SELECT id, full_name, phone FROM guests WHERE $where ORDER BY full_name ASC, id ASC");
        $stmt->execute($params);

        return self::uniqueByPhone($stmt->fetchAll());
    }

    public static function forInvitations(int $weddingId, bool $onlyUnsent): array
    {
        $where = "g.wedding_id = :wedding AND g.deleted_at IS NULL AND g.phone IS NOT NULL AND g.phone <> ''
                  AND i.revoked_at IS NULL";

        if ($onlyUnsent) {
            $where .= ' AND i.sent_at IS NULL';
        }

        $stmt = db()->prepare(
            "SELECT g.id, g.full_name, g.phone, i.token
             FROM guests g
             JOIN invitations i ON i.guest_id = g.id AND i.wedding_id = g.wedding_id
             WHERE $where
             ORDER BY g.full_name ASC, g.id ASC"
        );
        $stmt->execute([':wedding' => $weddingId]);

        return self::uniqueByPhone($stmt->fetchAll());
    }

    public static function invitationCounts(int $weddingId): array
    {
        $stmt = db()->prepare(
            "SELECT COALESCE(SUM(g.phone IS NOT NULL AND g.phone <> '' AND i.revoked_at IS NULL), 0) AS reachable,
                    COALESCE(SUM(g.phone IS NOT NULL AND g.phone <> '' AND i.revoked_at IS NULL AND i.sent_at IS NULL), 0) AS unsent,
                    COALESCE(SUM(g.phone IS NULL OR g.phone = ''), 0) AS no_phone
             FROM guests g
             JOIN invitations i ON i.guest_id = g.id AND i.wedding_id = g.wedding_id
             WHERE g.wedding_id = :wedding AND g.deleted_at IS NULL"
        );
        $stmt->execute([':wedding' => $weddingId]);
        $row = $stmt->fetch();

        return [
            'reachable' => (int) $row['reachable'],
            'unsent'    => (int) $row['unsent'],
            'no_phone'  => (int) $row['no_phone'],
        ];
    }

    public static function markInvited(int $weddingId, array $guestIds, string $channel): void
    {
        $guestIds = array_values(array_unique(array_map('intval', $guestIds)));

        if (!$guestIds) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($guestIds), '?'));
        $stmt = db()->prepare(
            "UPDATE invitations SET sent_at = NOW(), sent_channel = ?
             WHERE wedding_id = ? AND guest_id IN ($placeholders)"
        );
        $stmt->execute(array_merge([$channel, $weddingId], $guestIds));
    }

    private static function uniqueByPhone(array $rows): array
    {
        $seen = [];
        $out = [];

        foreach ($rows as $row) {
            if (isset($seen[$row['phone']])) {
                continue;
            }
            $seen[$row['phone']] = true;
            $out[] = $row;
        }

        return $out;
    }
}
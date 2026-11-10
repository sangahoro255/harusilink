<?php
namespace App\Models;

class Invitation
{
    public static function ensureForWedding(int $weddingId): void
    {
        $pdo = db();

        $stmt = $pdo->prepare(
            'SELECT g.id FROM guests g
             LEFT JOIN invitations i ON i.guest_id = g.id
             WHERE g.wedding_id = :wedding AND g.deleted_at IS NULL AND i.id IS NULL'
        );
        $stmt->execute([':wedding' => $weddingId]);
        $missing = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (!$missing) {
            return;
        }

        $insert = $pdo->prepare(
            'INSERT IGNORE INTO invitations (wedding_id, guest_id, token) VALUES (:wedding, :guest, :token)'
        );

        $pdo->beginTransaction();
        try {
            foreach ($missing as $guestId) {
                $insert->execute([
                    ':wedding' => $weddingId,
                    ':guest'   => (int) $guestId,
                    ':token'   => self::makeToken(),
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }
    }

    public static function paginate(int $weddingId, int $page, int $perPage): array
    {
        $pdo = db();

        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM invitations i
             JOIN guests g ON g.id = i.guest_id
             WHERE i.wedding_id = :wedding AND g.wedding_id = i.wedding_id AND g.deleted_at IS NULL'
        );
        $count->execute([':wedding' => $weddingId]);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            'SELECT i.token, i.revoked_at, g.id AS guest_id, g.full_name, g.phone, g.seats
             FROM invitations i
             JOIN guests g ON g.id = i.guest_id
             WHERE i.wedding_id = :wedding AND g.wedding_id = i.wedding_id AND g.deleted_at IS NULL
             ORDER BY g.full_name ASC, g.id ASC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':wedding', $weddingId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public static function findByToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT i.id AS invitation_id, i.revoked_at,
                    g.id AS guest_id, g.full_name, g.seats, g.rsvp_status,
                    w.id AS wedding_id, w.status AS wedding_status, w.bride_name, w.groom_name,
                    w.wedding_date, w.wedding_time, w.venue, w.description, w.cover_image
             FROM invitations i
             JOIN guests g ON g.id = i.guest_id AND g.deleted_at IS NULL
             JOIN weddings w ON w.id = i.wedding_id AND w.deleted_at IS NULL
             WHERE i.token = :token
             LIMIT 1'
        );
        $stmt->execute([':token' => $token]);

        return $stmt->fetch() ?: null;
    }

    public static function activeToken(int $weddingId, int $guestId): ?string
    {
        $stmt = db()->prepare(
            'SELECT i.token FROM invitations i
             JOIN guests g ON g.id = i.guest_id
             WHERE i.wedding_id = :wedding AND i.guest_id = :guest
               AND i.revoked_at IS NULL AND g.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':wedding' => $weddingId, ':guest' => $guestId]);
        $token = $stmt->fetchColumn();

        return $token !== false ? (string) $token : null;
    }

    public static function toggleRevoked(int $weddingId, int $guestId): ?string
    {
        $row = self::findOwned($weddingId, $guestId);

        if ($row === null) {
            return null;
        }

        $revoke = $row['revoked_at'] === null;

        $stmt = db()->prepare(
            'UPDATE invitations SET revoked_at = ' . ($revoke ? 'NOW()' : 'NULL') . '
             WHERE id = :id AND wedding_id = :wedding'
        );
        $stmt->execute([':id' => $row['id'], ':wedding' => $weddingId]);

        return $revoke ? 'revoked' : 'restored';
    }

    public static function regenerate(int $weddingId, int $guestId): bool
    {
        $row = self::findOwned($weddingId, $guestId);

        if ($row === null) {
            return false;
        }

        $stmt = db()->prepare(
            'UPDATE invitations SET token = :token, revoked_at = NULL
             WHERE id = :id AND wedding_id = :wedding'
        );
        $stmt->execute([':token' => self::makeToken(), ':id' => $row['id'], ':wedding' => $weddingId]);

        return true;
    }

    private static function findOwned(int $weddingId, int $guestId): ?array
    {
        $stmt = db()->prepare(
            'SELECT i.id, i.revoked_at FROM invitations i
             JOIN guests g ON g.id = i.guest_id
             WHERE i.wedding_id = :wedding AND i.guest_id = :guest AND g.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':wedding' => $weddingId, ':guest' => $guestId]);

        return $stmt->fetch() ?: null;
    }

    private static function makeToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
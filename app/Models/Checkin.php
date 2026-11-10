<?php
namespace App\Models;

class Checkin
{
    public static function evaluate(int $weddingId, string $token): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return ['state' => 'invalid'];
        }

        $stmt = db()->prepare(
            'SELECT i.revoked_at, g.id AS guest_id, g.full_name, g.seats, g.rsvp_status,
                    w.status AS wedding_status, w.bride_name, w.groom_name, c.checked_in_at
             FROM invitations i
             JOIN guests g ON g.id = i.guest_id AND g.deleted_at IS NULL
             JOIN weddings w ON w.id = i.wedding_id AND w.deleted_at IS NULL
             LEFT JOIN guest_checkins c ON c.guest_id = g.id
             WHERE i.token = :token AND i.wedding_id = :wedding
             LIMIT 1'
        );
        $stmt->execute([':token' => $token, ':wedding' => $weddingId]);

        return self::describe($stmt->fetch() ?: null);
    }

    public static function evaluateGuest(int $weddingId, int $guestId): array
    {
        $stmt = db()->prepare(
            'SELECT i.revoked_at, g.id AS guest_id, g.full_name, g.seats, g.rsvp_status,
                    w.status AS wedding_status, w.bride_name, w.groom_name, c.checked_in_at
             FROM guests g
             JOIN weddings w ON w.id = g.wedding_id AND w.deleted_at IS NULL
             LEFT JOIN invitations i ON i.guest_id = g.id
             LEFT JOIN guest_checkins c ON c.guest_id = g.id
             WHERE g.id = :guest AND g.wedding_id = :wedding AND g.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':guest' => $guestId, ':wedding' => $weddingId]);

        return self::describe($stmt->fetch() ?: null);
    }

    public static function admit(int $weddingId, string $token, bool $override): array
    {
        $reload = fn () => self::evaluate($weddingId, $token);

        return self::process($weddingId, $reload(), $override, 'qr', $reload);
    }

    public static function admitGuest(int $weddingId, int $guestId, bool $override): array
    {
        $reload = fn () => self::evaluateGuest($weddingId, $guestId);

        return self::process($weddingId, $reload(), $override, 'manual', $reload);
    }

    public static function search(int $weddingId, string $query, int $limit): array
    {
        $stmt = db()->prepare(
            'SELECT g.id, g.full_name, g.seats, g.rsvp_status, g.phone, c.checked_in_at
             FROM guests g
             LEFT JOIN guest_checkins c ON c.guest_id = g.id
             WHERE g.wedding_id = :wedding AND g.deleted_at IS NULL AND g.full_name LIKE :q
             ORDER BY g.full_name ASC, g.id ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':wedding', $weddingId, \PDO::PARAM_INT);
        $stmt->bindValue(':q', '%' . addcslashes($query, '%_\\') . '%');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map(function (array $row): array {
            $row['phone_hint'] = $row['phone'] ? '••• ' . substr($row['phone'], -3) : '';
            unset($row['phone']);
            return $row;
        }, $stmt->fetchAll());
    }

    public static function counts(int $weddingId): array
    {
        $stmt = db()->prepare(
            'SELECT COUNT(*) AS guests, COALESCE(SUM(c.seats), 0) AS people
             FROM guest_checkins c
             JOIN guests g ON g.id = c.guest_id AND g.wedding_id = c.wedding_id AND g.deleted_at IS NULL
             WHERE c.wedding_id = :wedding'
        );
        $stmt->execute([':wedding' => $weddingId]);
        $row = $stmt->fetch();

        return ['guests' => (int) $row['guests'], 'people' => (int) $row['people']];
    }

    public static function recent(int $weddingId, int $limit): array
    {
        $stmt = db()->prepare(
            'SELECT c.guest_id, c.seats, c.method, c.checked_in_at, g.full_name
             FROM guest_checkins c
             JOIN guests g ON g.id = c.guest_id AND g.wedding_id = c.wedding_id AND g.deleted_at IS NULL
             WHERE c.wedding_id = :wedding
             ORDER BY c.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':wedding', $weddingId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function undo(int $weddingId, int $guestId): bool
    {
        $stmt = db()->prepare('DELETE FROM guest_checkins WHERE wedding_id = :wedding AND guest_id = :guest');
        $stmt->execute([':wedding' => $weddingId, ':guest' => $guestId]);

        return $stmt->rowCount() > 0;
    }

    private static function describe(?array $row): array
    {
        if ($row === null) {
            return ['state' => 'invalid'];
        }

        if ($row['revoked_at'] !== null) {
            $state = 'revoked';
        } elseif ($row['wedding_status'] !== 'active') {
            $state = 'closed';
        } elseif ($row['checked_in_at'] !== null) {
            $state = 'already';
        } elseif ($row['rsvp_status'] === 'declined') {
            $state = 'declined';
        } elseif ($row['rsvp_status'] === 'pending') {
            $state = 'confirm';
        } else {
            $state = 'valid';
        }

        return [
            'state'         => $state,
            'guest_id'      => (int) $row['guest_id'],
            'name'          => $row['full_name'],
            'seats'         => (int) $row['seats'],
            'rsvp'          => $row['rsvp_status'],
            'checked_in_at' => $row['checked_in_at'],
            'couple'        => $row['bride_name'] . ' & ' . $row['groom_name'],
        ];
    }

    private static function process(int $weddingId, array $result, bool $override, string $method, callable $reload): array
    {
        if ($result['state'] === 'confirm' && !$override) {
            return $result;
        }

        if (!in_array($result['state'], ['valid', 'confirm'], true)) {
            return $result;
        }

        try {
            db()->prepare(
                'INSERT INTO guest_checkins (wedding_id, guest_id, seats, rsvp_at_entry, method)
                 VALUES (:wedding, :guest, :seats, :rsvp, :method)'
            )->execute([
                ':wedding' => $weddingId,
                ':guest'   => $result['guest_id'],
                ':seats'   => $result['seats'],
                ':rsvp'    => $result['rsvp'],
                ':method'  => $method,
            ]);
        } catch (\PDOException $ex) {
            if ($ex->getCode() === '23000') {
                return $reload();
            }
            throw $ex;
        }

        $after = $reload();
        $after['state'] = 'admitted';

        return $after;
    }
}
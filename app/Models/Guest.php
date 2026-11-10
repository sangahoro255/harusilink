<?php
namespace App\Models;

class Guest
{
    public const RSVP = ['pending', 'accepted', 'declined'];

    public static function paginate(int $weddingId, array $filters, int $perPage): array
    {
        $where = 'g.wedding_id = :wedding AND g.deleted_at IS NULL';
        $params = [':wedding' => $weddingId];

        $q = (string) ($filters['q'] ?? '');
        if ($q !== '') {
            $phoneTerm = preg_match('/^[0-9 +]+$/', $q) ? ltrim(str_replace([' ', '+'], '', $q), '0') : $q;
            if ($phoneTerm === '') {
                $phoneTerm = $q;
            }
            $where .= ' AND (g.full_name LIKE :q1 OR g.phone LIKE :q2 OR g.email LIKE :q3)';
            $params[':q1'] = '%' . addcslashes($q, '%_\\') . '%';
            $params[':q2'] = '%' . addcslashes($phoneTerm, '%_\\') . '%';
            $params[':q3'] = '%' . addcslashes($q, '%_\\') . '%';
        }

        if (in_array($filters['rsvp'] ?? '', self::RSVP, true)) {
            $where .= ' AND g.rsvp_status = :rsvp';
            $params[':rsvp'] = $filters['rsvp'];
        }

        if ((int) ($filters['category'] ?? 0) > 0) {
            $where .= ' AND g.category_id = :category';
            $params[':category'] = (int) $filters['category'];
        }

        $pdo = db();

        $count = $pdo->prepare("SELECT COUNT(*) FROM guests g WHERE $where");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($filters['page'] ?? 1)), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            "SELECT g.id, g.full_name, g.phone, g.email, g.seats, g.rsvp_status,
                    c.code AS category_code, c.name AS category_name
             FROM guests g
             LEFT JOIN guest_categories c ON c.id = g.category_id
             WHERE $where
             ORDER BY g.full_name ASC, g.id ASC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public static function summary(int $weddingId): array
    {
        $stmt = db()->prepare(
            'SELECT COUNT(*) AS guests, COALESCE(SUM(seats), 0) AS seats
             FROM guests
             WHERE wedding_id = :wedding AND deleted_at IS NULL'
        );
        $stmt->execute([':wedding' => $weddingId]);
        $row = $stmt->fetch();

        return ['guests' => (int) $row['guests'], 'seats' => (int) $row['seats']];
    }

    public static function find(int $weddingId, int $guestId): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, wedding_id, category_id, full_name, phone, email, seats, rsvp_status
             FROM guests
             WHERE id = :id AND wedding_id = :wedding AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':id' => $guestId, ':wedding' => $weddingId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $weddingId, array $data): int
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO guests (wedding_id, category_id, full_name, phone, email, seats)
             VALUES (:wedding, :category, :name, :phone, :email, :seats)'
        );
        $stmt->execute([
            ':wedding'  => $weddingId,
            ':category' => $data['category_id'],
            ':name'     => $data['full_name'],
            ':phone'    => $data['phone'],
            ':email'    => $data['email'],
            ':seats'    => $data['seats'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $weddingId, int $guestId, array $data): bool
    {
        $stmt = db()->prepare(
            'UPDATE guests
             SET category_id = :category, full_name = :name, phone = :phone, email = :email, seats = :seats
             WHERE id = :id AND wedding_id = :wedding AND deleted_at IS NULL'
        );
        $stmt->execute([
            ':category' => $data['category_id'],
            ':name'     => $data['full_name'],
            ':phone'    => $data['phone'],
            ':email'    => $data['email'],
            ':seats'    => $data['seats'],
            ':id'       => $guestId,
            ':wedding'  => $weddingId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function softDelete(int $weddingId, int $guestId): bool
    {
        $stmt = db()->prepare(
            'UPDATE guests SET deleted_at = NOW()
             WHERE id = :id AND wedding_id = :wedding AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $guestId, ':wedding' => $weddingId]);
        return $stmt->rowCount() > 0;
    }

    public static function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/[\s\-().]/', '', $raw);
        $cc = (string) env('DEFAULT_COUNTRY_CODE', '255');

        if (preg_match('/^0[0-9]{8,10}$/', $digits)) {
            $digits = '+' . $cc . substr($digits, 1);
        } elseif (preg_match('/^00[1-9][0-9]{7,13}$/', $digits)) {
            $digits = '+' . substr($digits, 2);
        } elseif (str_starts_with($digits, $cc) && preg_match('/^[0-9]{10,15}$/', $digits)) {
            $digits = '+' . $digits;
        }

        return preg_match('/^\+[1-9][0-9]{8,14}$/', $digits) ? $digits : null;
    }
}
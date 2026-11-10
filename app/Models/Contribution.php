<?php
namespace App\Models;

class Contribution
{
    public const METHODS  = ['cash', 'mobile_money', 'bank', 'other'];
    public const STATUSES = ['paid', 'pledged', 'cancelled'];

    public static function totals(int $weddingId): array
    {
        $stmt = db()->prepare(
            "SELECT COALESCE(SUM(CASE WHEN status = 'paid' THEN amount END), 0) AS paid,
                    COALESCE(SUM(CASE WHEN status = 'pledged' THEN amount END), 0) AS pledged,
                    COALESCE(SUM(status = 'paid'), 0) AS paid_count,
                    COALESCE(SUM(status = 'pledged'), 0) AS pledged_count
             FROM contributions
             WHERE wedding_id = :wedding AND deleted_at IS NULL"
        );
        $stmt->execute([':wedding' => $weddingId]);
        $row = $stmt->fetch();

        return [
            'paid'          => (float) $row['paid'],
            'pledged'       => (float) $row['pledged'],
            'paid_count'    => (int) $row['paid_count'],
            'pledged_count' => (int) $row['pledged_count'],
        ];
    }

    public static function byMethod(int $weddingId): array
    {
        $stmt = db()->prepare(
            "SELECT method, COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total
             FROM contributions
             WHERE wedding_id = :wedding AND status = 'paid' AND deleted_at IS NULL
             GROUP BY method
             ORDER BY total DESC"
        );
        $stmt->execute([':wedding' => $weddingId]);

        return $stmt->fetchAll();
    }

    public static function paginate(int $weddingId, array $filters, int $perPage): array
    {
        $where = 'wedding_id = :wedding AND deleted_at IS NULL';
        $params = [':wedding' => $weddingId];

        $q = (string) ($filters['q'] ?? '');
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where .= ' AND (contributor_name LIKE :q1 OR phone LIKE :q2 OR reference LIKE :q3)';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        if (in_array($filters['status'] ?? '', self::STATUSES, true)) {
            $where .= ' AND status = :status';
            $params[':status'] = $filters['status'];
        }

        $pdo = db();

        $count = $pdo->prepare("SELECT COUNT(*) FROM contributions WHERE $where");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($filters['page'] ?? 1)), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            "SELECT id, contributor_name, phone, amount, method, reference, status, contributed_on, note
             FROM contributions
             WHERE $where
             ORDER BY contributed_on DESC, id DESC
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

    public static function find(int $weddingId, int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, contributor_name, phone, amount, method, reference, status, contributed_on, note
             FROM contributions
             WHERE id = :id AND wedding_id = :wedding AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':wedding' => $weddingId]);

        return $stmt->fetch() ?: null;
    }

    public static function create(int $weddingId, array $data): int
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO contributions
                (wedding_id, contributor_name, phone, amount, method, reference, status, contributed_on, note)
             VALUES (:wedding, :name, :phone, :amount, :method, :reference, :status, :date, :note)'
        );
        $stmt->execute(self::bind($data) + [':wedding' => $weddingId]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $weddingId, int $id, array $data): bool
    {
        $stmt = db()->prepare(
            'UPDATE contributions
             SET contributor_name = :name, phone = :phone, amount = :amount, method = :method,
                 reference = :reference, status = :status, contributed_on = :date, note = :note
             WHERE id = :id AND wedding_id = :wedding AND deleted_at IS NULL'
        );
        $stmt->execute(self::bind($data) + [':id' => $id, ':wedding' => $weddingId]);

        return $stmt->rowCount() > 0;
    }

    public static function markPaid(int $weddingId, int $id, string $date): bool
    {
        $stmt = db()->prepare(
            "UPDATE contributions SET status = 'paid', contributed_on = :date
             WHERE id = :id AND wedding_id = :wedding AND status = 'pledged' AND deleted_at IS NULL"
        );
        $stmt->execute([':date' => $date, ':id' => $id, ':wedding' => $weddingId]);

        return $stmt->rowCount() > 0;
    }

    public static function softDelete(int $weddingId, int $id): bool
    {
        $stmt = db()->prepare(
            'UPDATE contributions SET deleted_at = NOW()
             WHERE id = :id AND wedding_id = :wedding AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id, ':wedding' => $weddingId]);

        return $stmt->rowCount() > 0;
    }

    public static function pledgedWithPhone(int $weddingId): array
    {
        $stmt = db()->prepare(
            "SELECT id, contributor_name, phone, amount
             FROM contributions
             WHERE wedding_id = :wedding AND status = 'pledged' AND deleted_at IS NULL
               AND phone IS NOT NULL AND phone <> ''
             ORDER BY contributor_name ASC, id ASC"
        );
        $stmt->execute([':wedding' => $weddingId]);

        return $stmt->fetchAll();
    }

    public static function pledgedByIds(int $weddingId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));

        if (!$ids) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare(
            "SELECT id, contributor_name, phone, amount
             FROM contributions
             WHERE wedding_id = ? AND status = 'pledged' AND deleted_at IS NULL
               AND phone IS NOT NULL AND phone <> '' AND id IN ($placeholders)"
        );
        $stmt->execute(array_merge([$weddingId], $ids));

        return $stmt->fetchAll();
    }

    public static function noContributionGuests(int $weddingId): array
    {
        $stmt = db()->prepare(
            "SELECT g.id, g.full_name, g.phone
             FROM guests g
             WHERE g.wedding_id = :wedding AND g.deleted_at IS NULL
               AND g.phone IS NOT NULL AND g.phone <> ''
               AND NOT EXISTS (
                   SELECT 1 FROM contributions c
                   WHERE c.wedding_id = g.wedding_id AND c.phone = g.phone
                     AND c.deleted_at IS NULL AND c.status <> 'cancelled'
               )
             ORDER BY g.full_name ASC, g.id ASC"
        );
        $stmt->execute([':wedding' => $weddingId]);

        return $stmt->fetchAll();
    }

    private static function bind(array $data): array
    {
        return [
            ':name'      => $data['contributor_name'],
            ':phone'     => $data['phone'],
            ':amount'    => $data['amount'],
            ':method'    => $data['method'],
            ':reference' => $data['reference'],
            ':status'    => $data['status'],
            ':date'      => $data['contributed_on'],
            ':note'      => $data['note'],
        ];
    }
}
<?php
namespace App\Models;

class Wedding
{
    private const COLUMNS = 'id, owner_id, slug, bride_name, groom_name, wedding_date, wedding_time,
                             venue, description, cover_image, status, created_at';

    public static function forOwner(int $ownerId): array
    {
        $stmt = db()->prepare(
            'SELECT ' . self::COLUMNS . ' FROM weddings
             WHERE owner_id = :owner AND deleted_at IS NULL
             ORDER BY wedding_date ASC, id DESC'
        );
        $stmt->execute([':owner' => $ownerId]);
        return $stmt->fetchAll();
    }

    public static function findForOwner(int $id, int $ownerId): ?array
    {
        $stmt = db()->prepare(
            'SELECT ' . self::COLUMNS . ' FROM weddings
             WHERE id = :id AND owner_id = :owner AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':owner' => $ownerId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $ownerId, array $data): int
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO weddings (owner_id, slug, bride_name, groom_name, wedding_date, wedding_time, venue, description)
             VALUES (:owner, :slug, :bride, :groom, :date, :time, :venue, :description)'
        );

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $stmt->execute([
                    ':owner'       => $ownerId,
                    ':slug'        => self::makeSlug($data['bride_name'], $data['groom_name']),
                    ':bride'       => $data['bride_name'],
                    ':groom'       => $data['groom_name'],
                    ':date'        => $data['wedding_date'],
                    ':time'        => $data['wedding_time'] !== '' ? $data['wedding_time'] : null,
                    ':venue'       => $data['venue'],
                    ':description' => $data['description'] !== '' ? $data['description'] : null,
                ]);
                return (int) $pdo->lastInsertId();
            } catch (\PDOException $ex) {
                if ($ex->getCode() !== '23000') {
                    throw $ex;
                }
            }
        }

        throw new \RuntimeException('Could not generate a unique wedding slug.');
    }

    public static function update(int $id, int $ownerId, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE weddings
             SET bride_name = :bride, groom_name = :groom, wedding_date = :date,
                 wedding_time = :time, venue = :venue, description = :description
             WHERE id = :id AND owner_id = :owner AND deleted_at IS NULL'
        );
        $stmt->execute([
            ':bride'       => $data['bride_name'],
            ':groom'       => $data['groom_name'],
            ':date'        => $data['wedding_date'],
            ':time'        => $data['wedding_time'] !== '' ? $data['wedding_time'] : null,
            ':venue'       => $data['venue'],
            ':description' => $data['description'] !== '' ? $data['description'] : null,
            ':id'          => $id,
            ':owner'       => $ownerId,
        ]);
    }

    public static function setCover(int $id, int $ownerId, string $filename): void
    {
        $stmt = db()->prepare(
            'UPDATE weddings SET cover_image = :cover
             WHERE id = :id AND owner_id = :owner AND deleted_at IS NULL'
        );
        $stmt->execute([':cover' => $filename, ':id' => $id, ':owner' => $ownerId]);
    }

    public static function changeStatus(int $id, int $ownerId, string $from, string $to): bool
    {
        $stmt = db()->prepare(
            'UPDATE weddings SET status = :to
             WHERE id = :id AND owner_id = :owner AND status = :from AND deleted_at IS NULL'
        );
        $stmt->execute([':to' => $to, ':id' => $id, ':owner' => $ownerId, ':from' => $from]);
        return $stmt->rowCount() > 0;
    }

    private static function makeSlug(string $bride, string $groom): string
    {
        $base = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $bride . '-' . $groom), '-'));
        $base = substr($base !== '' ? $base : 'wedding', 0, 60);
        return $base . '-' . bin2hex(random_bytes(3));
    }
}
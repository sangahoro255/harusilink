<?php
namespace App\Models;

class Rsvp
{
    public const STATUSES = ['pending', 'accepted', 'declined'];
    public const GUEST_STATUSES = ['accepted', 'declined'];
    public const GUEST_SOURCES = ['guest', 'whatsapp'];
    public const GUEST_CHANGE_LIMIT = 10;

    public static function record(int $weddingId, int $guestId, string $status, string $source): string
    {
        $byGuest = in_array($source, self::GUEST_SOURCES, true);

        if (!$byGuest && $source !== 'owner') {
            return 'invalid';
        }

        $allowed = $byGuest ? self::GUEST_STATUSES : self::STATUSES;

        if (!in_array($status, $allowed, true)) {
            return 'invalid';
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'SELECT rsvp_status FROM guests
                 WHERE id = :id AND wedding_id = :wedding AND deleted_at IS NULL
                 LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([':id' => $guestId, ':wedding' => $weddingId]);
            $current = $stmt->fetchColumn();

            if ($current === false) {
                $pdo->rollBack();
                return 'missing';
            }

            if ($current === $status) {
                $pdo->rollBack();
                return 'unchanged';
            }

            if ($byGuest) {
                $count = $pdo->prepare(
                    "SELECT COUNT(*) FROM rsvp_responses WHERE guest_id = :id AND source IN ('guest','whatsapp')"
                );
                $count->execute([':id' => $guestId]);

                if ((int) $count->fetchColumn() >= self::GUEST_CHANGE_LIMIT) {
                    $pdo->rollBack();
                    return 'limit';
                }
            }

            $pdo->prepare(
                'INSERT INTO rsvp_responses (wedding_id, guest_id, status, source)
                 VALUES (:wedding, :guest, :status, :source)'
            )->execute([
                ':wedding' => $weddingId,
                ':guest'   => $guestId,
                ':status'  => $status,
                ':source'  => $source,
            ]);

            $pdo->prepare(
                'UPDATE guests SET rsvp_status = :status WHERE id = :id AND wedding_id = :wedding'
            )->execute([':status' => $status, ':id' => $guestId, ':wedding' => $weddingId]);

            $pdo->commit();
            return 'changed';
        } catch (\Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $ex;
        }
    }

    public static function recent(int $weddingId, int $limit): array
    {
        $stmt = db()->prepare(
            'SELECT r.status, r.source, r.created_at, g.full_name
             FROM rsvp_responses r
             JOIN guests g ON g.id = r.guest_id AND g.wedding_id = r.wedding_id AND g.deleted_at IS NULL
             WHERE r.wedding_id = :wedding
             ORDER BY r.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':wedding', $weddingId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
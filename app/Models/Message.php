<?php
namespace App\Models;

class Message
{
    public const REMINDER_COOLDOWN_DAYS = 3;

    private const RANK = ['queued' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3];

    public static function paginate(int $weddingId, int $page, int $perPage): array
    {
        $pdo = db();

        $count = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE wedding_id = :wedding');
        $count->execute([':wedding' => $weddingId]);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            "SELECT m.id, m.kind, m.channel, m.body, m.recipients_count, m.created_at,
                    (SELECT COUNT(*) FROM message_logs l WHERE l.message_id = m.id AND l.status = 'failed') AS failed,
                    (SELECT COUNT(*) FROM message_logs l WHERE l.message_id = m.id AND l.status IN ('delivered','read')) AS delivered,
                    (SELECT l.provider FROM message_logs l WHERE l.message_id = m.id ORDER BY l.id ASC LIMIT 1) AS provider
             FROM messages m
             WHERE m.wedding_id = :wedding
             ORDER BY m.id DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':wedding', $weddingId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public static function find(int $weddingId, int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, kind, channel, body, recipients_count, created_at
             FROM messages
             WHERE id = :id AND wedding_id = :wedding
             LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':wedding' => $weddingId]);

        return $stmt->fetch() ?: null;
    }

    public static function logs(int $weddingId, int $messageId): array
    {
        $stmt = db()->prepare(
            'SELECT l.id, l.recipient, l.status, l.provider, l.error, l.created_at,
                    COALESCE(g.full_name, c.contributor_name) AS name
             FROM message_logs l
             LEFT JOIN guests g ON g.id = l.guest_id AND g.wedding_id = l.wedding_id
             LEFT JOIN contributions c ON c.id = l.contribution_id AND c.wedding_id = l.wedding_id
             WHERE l.message_id = :message AND l.wedding_id = :wedding
             ORDER BY l.id ASC
             LIMIT 500'
        );
        $stmt->execute([':message' => $messageId, ':wedding' => $weddingId]);

        return $stmt->fetchAll();
    }

    public static function recentlyReminded(int $weddingId): array
    {
        $days = (int) self::REMINDER_COOLDOWN_DAYS;

        $stmt = db()->prepare(
            "SELECT l.recipient, MAX(l.created_at) AS last_at
             FROM message_logs l
             JOIN messages m ON m.id = l.message_id
             WHERE l.wedding_id = :wedding AND m.kind = 'contribution_reminder'
               AND l.status <> 'failed' AND l.created_at > (NOW() - INTERVAL $days DAY)
             GROUP BY l.recipient"
        );
        $stmt->execute([':wedding' => $weddingId]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['recipient']] = $row['last_at'];
        }

        return $map;
    }

    public static function recentReminders(int $weddingId, int $limit): array
    {
        $stmt = db()->prepare(
            "SELECT l.recipient, l.status, l.created_at,
                    COALESCE(c.contributor_name, g.full_name) AS name
             FROM message_logs l
             JOIN messages m ON m.id = l.message_id
             LEFT JOIN contributions c ON c.id = l.contribution_id AND c.wedding_id = l.wedding_id
             LEFT JOIN guests g ON g.id = l.guest_id AND g.wedding_id = l.wedding_id
             WHERE l.wedding_id = :wedding AND m.kind = 'contribution_reminder'
             ORDER BY l.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':wedding', $weddingId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function applyStatus(string $providerMessageId, string $status, ?string $error): void
    {
        $pdo = db();

        $stmt = $pdo->prepare(
            "SELECT id, status FROM message_logs
             WHERE provider = 'whatsapp_cloud' AND provider_message_id = :pid
             LIMIT 1"
        );
        $stmt->execute([':pid' => $providerMessageId]);
        $row = $stmt->fetch();

        if (!$row) {
            return;
        }

        if ($status === 'failed') {
            if (in_array($row['status'], ['delivered', 'read'], true)) {
                return;
            }

            $pdo->prepare("UPDATE message_logs SET status = 'failed', error = :error WHERE id = :id")
                ->execute([':error' => $error !== null ? mb_substr($error, 0, 250) : null, ':id' => $row['id']]);
            return;
        }

        if (!isset(self::RANK[$status])) {
            return;
        }

        $current = self::RANK[$row['status']] ?? -1;

        if (self::RANK[$status] > $current) {
            $pdo->prepare('UPDATE message_logs SET status = :status, error = NULL WHERE id = :id')
                ->execute([':status' => $status, ':id' => $row['id']]);
        }
    }
}
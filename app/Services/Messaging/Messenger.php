<?php
namespace App\Services\Messaging;

class Messenger
{
    public const MAX_PER_SEND = 250;

    public static function mode(): string
    {
        return strtolower(trim((string) env('MESSAGING_MODE', 'mock'))) === 'live' ? 'live' : 'mock';
    }

    public static function provider(string $channel): ?MessageProvider
    {
        if (!in_array($channel, ['whatsapp', 'sms'], true)) {
            return null;
        }

        if (self::mode() === 'mock') {
            return new MockProvider($channel);
        }

        if ($channel === 'whatsapp' && WhatsAppCloudProvider::configured()) {
            return new WhatsAppCloudProvider();
        }

        return null;
    }

    public static function available(string $channel): bool
    {
        return self::provider($channel) !== null;
    }

    public static function personalize(string $template, array $vars): string
    {
        $pairs = [];

        foreach ($vars as $key => $value) {
            $pairs['{' . $key . '}'] = (string) $value;
        }

        return strtr($template, $pairs);
    }

    public static function dispatch(
        int $weddingId,
        ?int $userId,
        string $kind,
        string $channel,
        string $template,
        array $recipients
    ): array {
        $provider = self::provider($channel);

        if ($provider === null) {
            throw new \RuntimeException('provider_unavailable');
        }

        $recipients = array_slice(array_values($recipients), 0, self::MAX_PER_SEND);

        if (function_exists('set_time_limit')) {
            @set_time_limit(240);
        }

        $pdo = db();

        $pdo->prepare(
            'INSERT INTO messages (wedding_id, created_by, kind, channel, body, recipients_count)
             VALUES (:wedding, :user, :kind, :channel, :body, :count)'
        )->execute([
            ':wedding' => $weddingId,
            ':user'    => $userId,
            ':kind'    => $kind,
            ':channel' => $channel,
            ':body'    => $template,
            ':count'   => count($recipients),
        ]);
        $messageId = (int) $pdo->lastInsertId();

        $insertLog = $pdo->prepare(
            "INSERT INTO message_logs (message_id, wedding_id, guest_id, contribution_id, recipient, status, provider)
             VALUES (:message, :wedding, :guest, :contribution, :recipient, 'queued', :provider)"
        );
        $updateLog = $pdo->prepare(
            'UPDATE message_logs SET status = :status, provider_message_id = :pid, error = :error WHERE id = :id'
        );

        $sent = 0;
        $failed = 0;
        $results = [];

        foreach ($recipients as $recipient) {
            $text = self::personalize($template, $recipient['vars'] ?? []);

            $insertLog->execute([
                ':message'      => $messageId,
                ':wedding'      => $weddingId,
                ':guest'        => $recipient['guest_id'] ?? null,
                ':contribution' => $recipient['contribution_id'] ?? null,
                ':recipient'    => $recipient['phone'],
                ':provider'     => $provider->name(),
            ]);
            $logId = (int) $pdo->lastInsertId();

            try {
                $result = ($recipient['invite'] ?? null) !== null
                    ? $provider->sendInvitation($recipient['phone'], $recipient['invite'] + ['text' => $text])
                    : $provider->sendText($recipient['phone'], $text);
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $result = ['ok' => false, 'id' => null, 'error' => 'exception'];
            }

            $ok = !empty($result['ok']);

            $updateLog->execute([
                ':status' => $ok ? 'sent' : 'failed',
                ':pid'    => $result['id'] ?? null,
                ':error'  => $ok ? null : mb_substr((string) ($result['error'] ?? 'error'), 0, 250),
                ':id'     => $logId,
            ]);

            $ok ? $sent++ : $failed++;
            $results[] = ['guest_id' => $recipient['guest_id'] ?? null, 'ok' => $ok];
        }

        return [
            'message_id' => $messageId,
            'sent'       => $sent,
            'failed'     => $failed,
            'simulated'  => $provider->isSimulated(),
            'results'    => $results,
        ];
    }
}
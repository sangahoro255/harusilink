<?php
namespace App\Controllers;

use App\Models\Invitation;
use App\Models\Message;
use App\Models\Rsvp;
use App\Services\Messaging\WebhookSignature;
use App\Services\Messaging\WhatsAppWebhook;

class WhatsAppWebhookController
{
    public function verify(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');

        $expected = (string) env('WHATSAPP_VERIFY_TOKEN', '');
        $mode = isset($_GET['hub_mode']) && is_string($_GET['hub_mode']) ? $_GET['hub_mode'] : '';
        $token = isset($_GET['hub_verify_token']) && is_string($_GET['hub_verify_token']) ? $_GET['hub_verify_token'] : '';
        $challenge = isset($_GET['hub_challenge']) && is_string($_GET['hub_challenge']) ? $_GET['hub_challenge'] : '';

        if ($expected !== '' && $mode === 'subscribe' && hash_equals($expected, $token)) {
            echo preg_replace('/[^A-Za-z0-9_\-]/', '', $challenge);
            return;
        }

        http_response_code(403);
        echo 'forbidden';
    }

    public function receive(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');

        $body = file_get_contents('php://input', false, null, 0, 1048577);

        if ($body === false || strlen($body) > 1048576) {
            http_response_code(413);
            return;
        }

        $signature = (string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');

        if (!WebhookSignature::valid($body, $signature, (string) env('WHATSAPP_APP_SECRET', ''))) {
            http_response_code(403);
            echo 'forbidden';
            return;
        }

        $payload = json_decode($body, true);

        if (!is_array($payload)) {
            http_response_code(400);
            return;
        }

        $parsed = WhatsAppWebhook::parse($payload);

        try {
            foreach ($parsed['statuses'] as $status) {
                Message::applyStatus($status['id'], $status['status'], $status['error']);
            }

            foreach ($parsed['replies'] as $reply) {
                if ($reply['event_id'] === '' || !$this->firstTime($reply['event_id'])) {
                    continue;
                }

                $invitation = Invitation::findByToken($reply['token']);

                if ($invitation === null
                    || $invitation['revoked_at'] !== null
                    || $invitation['wedding_status'] !== 'active') {
                    continue;
                }

                Rsvp::record((int) $invitation['wedding_id'], (int) $invitation['guest_id'], $reply['status'], 'whatsapp');
            }
        } catch (\Throwable $ex) {
            error_log('whatsapp webhook: ' . $ex->getMessage());
        }

        http_response_code(200);
        echo 'ok';
    }

    private function firstTime(string $eventId): bool
    {
        $stmt = db()->prepare("INSERT IGNORE INTO webhook_events (provider, event_id) VALUES ('whatsapp', :event)");
        $stmt->execute([':event' => mb_substr($eventId, 0, 190)]);

        return $stmt->rowCount() > 0;
    }
}
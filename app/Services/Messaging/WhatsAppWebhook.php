<?php
namespace App\Services\Messaging;

class WhatsAppWebhook
{
    public static function parse(array $payload): array
    {
        $statuses = [];
        $replies = [];

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) (is_array($entry) ? ($entry['changes'] ?? []) : []) as $change) {
                $value = is_array($change) ? ($change['value'] ?? []) : [];

                if (!is_array($value)) {
                    continue;
                }

                foreach ((array) ($value['statuses'] ?? []) as $status) {
                    if (!is_array($status) || !isset($status['id'], $status['status'])) {
                        continue;
                    }

                    $statuses[] = [
                        'id'     => (string) $status['id'],
                        'status' => (string) $status['status'],
                        'error'  => isset($status['errors'][0]['title']) ? (string) $status['errors'][0]['title'] : null,
                    ];
                }

                foreach ((array) ($value['messages'] ?? []) as $message) {
                    if (!is_array($message)) {
                        continue;
                    }

                    $raw = null;

                    if (($message['type'] ?? '') === 'interactive') {
                        $raw = $message['interactive']['button_reply']['id'] ?? null;
                    } elseif (($message['type'] ?? '') === 'button') {
                        $raw = $message['button']['payload'] ?? null;
                    }

                    if (is_string($raw) && preg_match('/^rsvp:(accepted|declined):([a-f0-9]{32})$/', $raw, $match)) {
                        $replies[] = [
                            'event_id' => (string) ($message['id'] ?? ''),
                            'status'   => $match[1],
                            'token'    => $match[2],
                        ];
                    }
                }
            }
        }

        return ['statuses' => $statuses, 'replies' => $replies];
    }
}
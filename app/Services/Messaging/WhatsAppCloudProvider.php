<?php
namespace App\Services\Messaging;

class WhatsAppCloudProvider implements MessageProvider
{
    public static function configured(): bool
    {
        return trim((string) env('WHATSAPP_TOKEN', '')) !== ''
            && trim((string) env('WHATSAPP_PHONE_NUMBER_ID', '')) !== '';
    }

    public function name(): string
    {
        return 'whatsapp_cloud';
    }

    public function isSimulated(): bool
    {
        return false;
    }

    public function sendText(string $to, string $body): array
    {
        $template = trim((string) env('WHATSAPP_TEMPLATE_NOTICE', ''));

        if ($template !== '') {
            return $this->post($this->templatePayload($to, $template, [$this->clean($body)], []));
        }

        return $this->post([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->digits($to),
            'type'              => 'text',
            'text'              => ['preview_url' => false, 'body' => $body],
        ]);
    }

    public function sendInvitation(string $to, array $data): array
    {
        $template = trim((string) env('WHATSAPP_TEMPLATE_INVITE', ''));

        if ($template === '') {
            return ['ok' => false, 'id' => null, 'error' => 'WHATSAPP_TEMPLATE_INVITE is not set'];
        }

        $params = [
            $this->clean((string) ($data['guest_name'] ?? '')),
            $this->clean((string) ($data['couple'] ?? '')),
            $this->clean((string) ($data['date_text'] ?? '')),
            $this->clean((string) ($data['link'] ?? '')),
        ];

        $token = (string) ($data['token'] ?? '');

        $buttons = [
            [
                'type'       => 'button',
                'sub_type'   => 'quick_reply',
                'index'      => '0',
                'parameters' => [['type' => 'payload', 'payload' => 'rsvp:accepted:' . $token]],
            ],
            [
                'type'       => 'button',
                'sub_type'   => 'quick_reply',
                'index'      => '1',
                'parameters' => [['type' => 'payload', 'payload' => 'rsvp:declined:' . $token]],
            ],
        ];

        return $this->post($this->templatePayload($to, $template, $params, $buttons));
    }

    private function templatePayload(string $to, string $name, array $bodyParams, array $buttons): array
    {
        $lang = preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $l = (string) env('WHATSAPP_TEMPLATE_LANG', 'sw')) ? $l : 'sw';

        $components = [[
            'type'       => 'body',
            'parameters' => array_map(fn ($text) => ['type' => 'text', 'text' => $text], $bodyParams),
        ]];

        foreach ($buttons as $button) {
            $components[] = $button;
        }

        return [
            'messaging_product' => 'whatsapp',
            'to'                => $this->digits($to),
            'type'              => 'template',
            'template'          => [
                'name'       => $name,
                'language'   => ['code' => $lang],
                'components' => $components,
            ],
        ];
    }

    private function digits(string $phone): string
    {
        return preg_replace('/\D/', '', $phone) ?? '';
    }

    private function clean(string $text): string
    {
        $text = preg_replace('/[\r\n\t]+/', ' ', $text) ?? '';

        return trim(preg_replace('/ {2,}/', ' ', $text) ?? '');
    }

    private function post(array $payload): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'id' => null, 'error' => 'PHP cURL extension is not enabled'];
        }

        $version = preg_match('/^v\d+\.\d+$/', $v = (string) env('WHATSAPP_API_VERSION', 'v21.0')) ? $v : 'v21.0';
        $phoneId = rawurlencode(trim((string) env('WHATSAPP_PHONE_NUMBER_ID', '')));
        $token = trim((string) env('WHATSAPP_TOKEN', ''));

        $ch = curl_init('https://graph.facebook.com/' . $version . '/' . $phoneId . '/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'id' => null, 'error' => mb_substr($curlError !== '' ? $curlError : 'Network error', 0, 200)];
        }

        $data = json_decode((string) $response, true);
        $id = is_array($data) ? ($data['messages'][0]['id'] ?? null) : null;

        if ($status >= 200 && $status < 300 && is_string($id)) {
            return ['ok' => true, 'id' => $id, 'error' => null];
        }

        $message = is_array($data) ? (string) ($data['error']['message'] ?? '') : '';

        return ['ok' => false, 'id' => null, 'error' => mb_substr($message !== '' ? $message : 'HTTP ' . $status, 0, 200)];
    }
}
<?php
if (PHP_SAPI !== 'cli') {
    exit;
}

$env = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];
$secret = $env['WHATSAPP_APP_SECRET'] ?? '';
$base = rtrim($env['APP_URL'] ?? '', '/');

$token = $argv[1] ?? '';
$choice = $argv[2] ?? 'accepted';
$sign = ($argv[3] ?? 'signed') !== 'unsigned';

if (!preg_match('/^[a-f0-9]{32}$/', $token) || !in_array($choice, ['accepted', 'declined'], true)) {
    exit("Matumizi: php tests/simulate_whatsapp.php TOKEN accepted|declined [signed|unsigned]\n");
}

if ($secret === '' || $base === '') {
    exit("Weka WHATSAPP_APP_SECRET na APP_URL kwenye .env kwanza.\n");
}

$payload = json_encode([
    'object' => 'whatsapp_business_account',
    'entry'  => [[
        'id'      => 'SIMULATED',
        'changes' => [[
            'field' => 'messages',
            'value' => [
                'messages' => [[
                    'id'          => 'wamid.SIM' . bin2hex(random_bytes(6)),
                    'type'        => 'button',
                    'button'      => ['payload' => 'rsvp:' . $choice . ':' . $token, 'text' => 'SIM'],
                ]],
            ],
        ]],
    ]],
]);

$headers = "Content-Type: application/json\r\n";
if ($sign) {
    $headers .= 'X-Hub-Signature-256: sha256=' . hash_hmac('sha256', $payload, $secret) . "\r\n";
}

$context = stream_context_create(['http' => [
    'method'        => 'POST',
    'header'        => $headers,
    'content'       => $payload,
    'ignore_errors' => true,
    'timeout'       => 10,
]]);

$response = file_get_contents($base . '/webhooks/whatsapp', false, $context);
$status = $http_response_header[0] ?? 'no response';

echo $status . ' | ' . trim((string) $response) . "\n";
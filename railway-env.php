<?php
// Inatengeneza .env kutoka kwenye variables za Railway
$keys = [
    'APP_ENV','APP_DEBUG','APP_NAME','APP_URL',
    'DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS',
    'MESSAGING_MODE','WHATSAPP_TOKEN','WHATSAPP_PHONE_NUMBER_ID',
    'WHATSAPP_API_VERSION','WHATSAPP_VERIFY_TOKEN','WHATSAPP_APP_SECRET',
    'WHATSAPP_TEMPLATE_INVITE','WHATSAPP_TEMPLATE_NOTICE','WHATSAPP_TEMPLATE_LANG',
];
$lines = [];
foreach ($keys as $k) {
    $v = getenv($k);
    if ($v !== false) {
        $lines[] = $k . '="' . str_replace('"', '\"', $v) . '"';
    }
}
file_put_contents(__DIR__ . '/.env', implode("\n", $lines) . "\n");
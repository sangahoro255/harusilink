<?php
if (PHP_SAPI !== 'cli') {
    exit;
}

require __DIR__ . '/bootstrap.php';

use App\Helpers\CsvExport;
use App\Helpers\Format;
use App\Helpers\FormNonce;
use App\Helpers\Money;
use App\Helpers\Url;
use App\Models\Guest;
use App\Services\GuestValidator;
use App\Services\InvitationTemplates;
use App\Services\Messaging\Messenger;
use App\Services\Messaging\WebhookSignature;
use App\Services\Messaging\WhatsAppWebhook;
use App\Services\RateLimiter;

class RouteProbe
{
    public function show($id)
    {
        echo 'show:' . $id;
    }

    public function token($token)
    {
        echo 'token:' . $token;
    }

    public function slug($slug)
    {
        echo 'slug:' . $slug;
    }
}

$results = ['pass' => 0, 'fail' => []];

function check(string $name, bool $ok): void
{
    global $results;

    if ($ok) {
        $results['pass']++;
        return;
    }

    $results['fail'][] = $name;
    echo "  KOSA: $name\n";
}

function same(string $name, $expected, $actual): void
{
    $ok = $expected === $actual;

    if (!$ok) {
        $name .= ' | ilitarajiwa ' . var_export($expected, true) . ', imepatikana ' . var_export($actual, true);
    }

    check($name, $ok);
}

function section(string $title): void
{
    echo "\n== $title\n";
}

function route(string $method, string $uri): string
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['REQUEST_URI'] = $uri;

    $router = new \App\Core\Router();
    $router->get('/weddings/{id}', [RouteProbe::class, 'show']);
    $router->post('/weddings/{id}/update', [RouteProbe::class, 'show']);
    $router->get('/i/{token}', [RouteProbe::class, 'token']);
    $router->get('/scan/{slug}', [RouteProbe::class, 'slug']);

    ob_start();
    $router->dispatch();

    return (string) ob_get_clean();
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'example.org';
$_SERVER['REMOTE_ADDR'] = '203.0.113.5';
unset($_SERVER['HTTPS']);

section('Namba za simu');

foreach ([
    ['0712 345 678', '+255712345678'],
    ['0712-345-678', '+255712345678'],
    ['(0712) 345.678', '+255712345678'],
    ['+255712345678', '+255712345678'],
    ['255712345678', '+255712345678'],
    ['00254712345678', '+254712345678'],
    ['123', null],
    ['0712', null],
    ['abc', null],
    ['', null],
] as [$input, $expected]) {
    same("simu '$input'", $expected, Guest::normalizePhone($input));
}

section('Kiasi cha pesa');

foreach ([
    ['1,500,000', '1500000'],
    ['1500000.50', '1500000.50'],
    [' 50 000 ', '50000'],
    ['999999999999.99', '999999999999.99'],
    ['0', null],
    ['0.00', null],
    ['-5', null],
    ['1.234', null],
    ['1e5', null],
    ['1234567890123', null],
    ['abc', null],
    ['', null],
] as [$input, $expected]) {
    same("kiasi '$input'", $expected, Money::parse($input));
}

same('muundo wa kiasi kizima', 'TZS 500,000', Money::format(500000));
same('muundo wa kiasi chenye senti', 'TZS 1,500.50', Money::format('1500.5'));
same('muundo wa sifuri', 'TZS 0', Money::format(0));

$GLOBALS['TEST_ENV']['CURRENCY'] = 'usd';
same('sarafu kutoka .env', 'USD 10', Money::format(10));

$GLOBALS['TEST_ENV']['CURRENCY'] = 'DOLLARS';
same('sarafu isiyo sahihi inarudi TZS', 'TZS 10', Money::format(10));

$GLOBALS['TEST_ENV'] = [];

section('Ulinzi wa CSV injection');

same('fomula =', "'=1+1", CsvExport::cell('=1+1'));
same('fomula @', "'@SUM(A1)", CsvExport::cell('@SUM(A1)'));
same('fomula +cmd', "'+cmd|x", CsvExport::cell('+cmd|x'));
same('fomula -', "'-5", CsvExport::cell('-5'));
same('tab mwanzoni', "'\tcmd", CsvExport::cell("\tcmd"));
same('simu inabaki', '+255712345678', CsvExport::cell('+255712345678'));
same('jina la kawaida', 'Peter John', CsvExport::cell('Peter John'));
same('tupu', '', CsvExport::cell(''));
same('namba kamili', '12345', CsvExport::cell(12345));

section('Rangi za kadi');

foreach (InvitationTemplates::PALETTES as $name => [$primary, $accent]) {
    check("paleti $name: rangi kuu inasomeka", InvitationTemplates::contrastWithWhite($primary) >= InvitationTemplates::MIN_CONTRAST);
    check("paleti $name: rangi ni hex", InvitationTemplates::isHex($primary) && InvitationTemplates::isHex($accent));
}

check('navy inasomeka', InvitationTemplates::contrastWithWhite('#0B1F3A') > 16);
check('njano hafifu haisomeki', InvitationTemplates::contrastWithWhite('#FFEB99') < InvitationTemplates::MIN_CONTRAST);
check('kijivu #777777 kinakataliwa', InvitationTemplates::contrastWithWhite('#777777') < InvitationTemplates::MIN_CONTRAST);
check('kijivu #767676 kinakubaliwa', InvitationTemplates::contrastWithWhite('#767676') >= InvitationTemplates::MIN_CONTRAST);
check('nyeusi', abs(InvitationTemplates::contrastWithWhite('#000000') - 21.0) < 0.01);

same('hex ndogo', true, InvitationTemplates::isHex('#0b1f3a'));
same('hex bila #', false, InvitationTemplates::isHex('0B1F3A'));
same('hex fupi', false, InvitationTemplates::isHex('#12345'));
same('hex yenye herufi mbaya', false, InvitationTemplates::isHex('#GGGGGG'));
same('jina la rangi', false, InvitationTemplates::isHex('red'));

same(
    'CSS ya rangi hatari inakataliwa',
    '--inv-primary:#0B1F3A;--inv-accent:#D4A72C',
    InvitationTemplates::styleVars(['primary_color' => 'red;background:url(x)', 'accent_color' => '#D4A72C'])
);
same('rangi tupu zinapata za msingi', '--inv-primary:#0B1F3A;--inv-accent:#D4A72C', InvitationTemplates::styleVars([]));
check('violezo vyote viko kwenye orodha', in_array('classic', InvitationTemplates::TEMPLATES, true) && in_array('modern', InvitationTemplates::TEMPLATES, true));

section('Ubinafsishaji wa ujumbe');

same('majina yanabadilishwa', 'Mpendwa Amina, harusi ya Tito & Princess', Messenger::personalize('Mpendwa {name}, harusi ya {couple}', ['name' => 'Amina', 'couple' => 'Tito & Princess']));
same('kigezo kisichojulikana kinabaki', 'Hi {kitu}', Messenger::personalize('Hi {kitu}', ['name' => 'A']));
same('maandishi yaliyobadilishwa hayabadilishwi tena', 'Hi {couple} & X', Messenger::personalize('Hi {name} & {couple}', ['name' => '{couple}', 'couple' => 'X']));
same('thamani tupu', 'Kiasi:  tu', Messenger::personalize('Kiasi: {amount} tu', ['amount' => '']));

section('Saini ya webhook');

$body = '{"a":1}';
$secret = 'secret';
$header = 'sha256=' . hash_hmac('sha256', $body, $secret);

same('HMAC ya kumbukumbu', 'aa9e2e3575f5d7098b6caccd790888c36d5fdb63342a73bada2d6a51747a8494', hash_hmac('sha256', $body, $secret));
same('saini sahihi', true, WebhookSignature::valid($body, $header, $secret));
same('siri tofauti', false, WebhookSignature::valid($body, $header, 'nyingine'));
same('mwili umebadilishwa', false, WebhookSignature::valid('{"a":2}', $header, $secret));
same('siri tupu inakataliwa', false, WebhookSignature::valid($body, 'sha256=' . hash_hmac('sha256', $body, ''), ''));
same('bila kiambishi sha256=', false, WebhookSignature::valid($body, hash_hmac('sha256', $body, $secret), $secret));
same('kichwa tupu', false, WebhookSignature::valid($body, '', $secret));

section('Uchambuzi wa ujumbe wa WhatsApp');

$token = str_repeat('a', 32);

$parsed = WhatsAppWebhook::parse([
    'entry' => [[
        'changes' => [[
            'value' => [
                'statuses' => [
                    ['id' => 'wamid.1', 'status' => 'delivered'],
                    ['id' => 'wamid.2', 'status' => 'failed', 'errors' => [['title' => 'Undeliverable']]],
                    ['bad' => true],
                ],
                'messages' => [
                    ['id' => 'm1', 'type' => 'button', 'button' => ['payload' => 'rsvp:accepted:' . $token]],
                    ['id' => 'm2', 'type' => 'interactive', 'interactive' => ['button_reply' => ['id' => 'rsvp:declined:' . $token]]],
                    ['id' => 'm3', 'type' => 'button', 'button' => ['payload' => 'rsvp:hacked:' . $token]],
                    ['id' => 'm4', 'type' => 'text', 'text' => ['body' => 'habari']],
                    ['id' => 'm5', 'type' => 'button', 'button' => ['payload' => 'rsvp:accepted:short']],
                ],
            ],
        ]],
    ]],
]);

same('hali mbili zinapatikana', 2, count($parsed['statuses']));
same('hali ya kwanza', ['id' => 'wamid.1', 'status' => 'delivered', 'error' => null], $parsed['statuses'][0]);
same('hali ya pili ina kosa', 'Undeliverable', $parsed['statuses'][1]['error']);
same('majibu mawili halali pekee', 2, count($parsed['replies']));
same('jibu la kitufe cha kawaida', ['event_id' => 'm1', 'status' => 'accepted', 'token' => $token], $parsed['replies'][0]);
same('jibu la kitufe cha mazungumzo', 'declined', $parsed['replies'][1]['status']);
same('ujumbe wa kuingia tupu', ['statuses' => [], 'replies' => []], WhatsAppWebhook::parse([]));
same('ujumbe wenye muundo mbovu', ['statuses' => [], 'replies' => []], WhatsAppWebhook::parse(['entry' => 'x']));

section('Anwani kamili (Url)');

$GLOBALS['TEST_ENV']['APP_URL'] = 'https://example.com';
same('APP_URL ya kawaida', 'https://example.com/verify/x', Url::absolute('/verify/x'));

$GLOBALS['TEST_ENV']['APP_URL'] = 'https://example.com/';
same('APP_URL yenye / mwishoni', 'https://example.com/verify/x', Url::absolute('/verify/x'));

$GLOBALS['TEST_ENV']['APP_URL'] = 'http://localhost/harusilink/public';
same('APP_URL yenye njia', 'http://localhost/harusilink/public/verify/x', Url::absolute('/verify/x'));

$GLOBALS['TEST_ENV']['APP_URL'] = 'javascript:alert(1)';
same('APP_URL hatari inakataliwa', 'http://example.org/verify/x', Url::absolute('/verify/x'));

$GLOBALS['TEST_ENV']['APP_URL'] = '';
same('bila APP_URL inatumia host', 'http://example.org/verify/x', Url::absolute('/verify/x'));

$_SERVER['HTTP_HOST'] = 'evil.com/<x>';
same('host mbovu inakataliwa', 'http://localhost/verify/x', Url::absolute('/verify/x'));

$_SERVER['HTTP_HOST'] = 'example.org';
$_SERVER['HTTPS'] = 'on';
same('HTTPS inatambuliwa', 'https://example.org/verify/x', Url::absolute('/verify/x'));

unset($_SERVER['HTTPS']);
$GLOBALS['TEST_ENV'] = [];

section('Tarehe na saa');

same('saa bila sekunde', '10:00', Format::time('10:00:00'));
same('saa tupu (null)', '', Format::time(null));
same('saa tupu (maandishi)', '', Format::time(''));
same('tarehe 20 Desemba 2026 ni Jumapili', 'weekday.7, 20 month.12 2026', Format::date('2026-12-20'));
same('tarehe mbovu inarudishwa ilivyo', 'garbage', Format::date('garbage'));

section('Uthibitisho wa taarifa za mgeni');

[$errors, $data] = GuestValidator::check(['full_name' => 'Peter John', 'phone' => '0712 345 678', 'email' => '', 'seats' => '2']);
same('mgeni halali hana makosa', [], $errors);
same('simu inasafishwa', '+255712345678', $data['phone']);
same('viti ni namba', 2, $data['seats']);

[$errors] = GuestValidator::check(['full_name' => 'A', 'phone' => '', 'email' => '', 'seats' => '1']);
check('jina fupi linakataliwa', in_array('err.guest_name', $errors, true));

[$errors] = GuestValidator::check(['full_name' => 'Peter', 'phone' => '123', 'email' => '', 'seats' => '1']);
check('simu mbaya inakataliwa', in_array('err.guest_phone', $errors, true));

[$errors] = GuestValidator::check(['full_name' => 'Peter', 'phone' => '', 'email' => 'si-barua', 'seats' => '1']);
check('barua pepe mbaya inakataliwa', in_array('err.guest_email', $errors, true));

foreach (['0', '21', 'abc', '-1', '1.5', ''] as $seats) {
    [$errors] = GuestValidator::check(['full_name' => 'Peter', 'phone' => '', 'email' => '', 'seats' => $seats]);
    check("viti '$seats' vinakataliwa", in_array('err.guest_seats', $errors, true));
}

[$errors] = GuestValidator::check(['full_name' => 'Peter', 'phone' => '', 'email' => '', 'seats' => '20']);
same('viti 20 vinakubaliwa', [], $errors);

section('Kinga ya kutuma mara mbili');

$_SESSION = [];

$nonce = FormNonce::issue('a:1');
check('nonce ni hex ya herufi 32', preg_match('/^[a-f0-9]{32}$/', $nonce) === 1);
same('inakubaliwa mara ya kwanza', true, FormNonce::consume('a:1', $nonce));
same('haikubaliwi mara ya pili', false, FormNonce::consume('a:1', $nonce));

$nonce = FormNonce::issue('a:1');
same('thamani isiyo sahihi', false, FormNonce::consume('a:1', 'x'));
same('nonce inaondolewa hata ikikosewa', false, FormNonce::consume('a:1', $nonce));

$nonce = FormNonce::issue('a:1');
same('eneo tofauti', false, FormNonce::consume('b:1', $nonce));

section('Kikomo cha maombi (ufunguo)');

$_SERVER['REMOTE_ADDR'] = '203.0.113.5';
$keyA = RateLimiter::clientKey('rsvp');
$_SERVER['REMOTE_ADDR'] = '203.0.113.6';
$keyB = RateLimiter::clientKey('rsvp');

check('ufunguo una muundo sahihi', preg_match('/^rsvp:[a-f0-9]{32}$/', $keyA) === 1);
check('IP tofauti zina funguo tofauti', $keyA !== $keyB);
check('IP haionekani kwenye ufunguo', !str_contains($keyA, '203.0.113'));

section('Njia za URL (Router)');

same('namba ya harusi', 'show:15', route('GET', '/weddings/15'));
same('query string inapuuzwa', 'show:15', route('GET', '/weddings/15?x=1'));
same('/ mwishoni inaruhusiwa', 'show:15', route('GET', '/weddings/15/'));
same('herufi badala ya namba', 'err.not_found', route('GET', '/weddings/abc'));
same('njia ya kurudi nyuma', 'err.not_found', route('GET', '/weddings/../x'));
same('GET kwa njia ya POST', 'err.not_found', route('GET', '/weddings/15/update'));
same('POST kwa njia ya POST', 'show:15', route('POST', '/weddings/15/update'));
same('tokeni halali', 'token:' . str_repeat('a1', 16), route('GET', '/i/' . str_repeat('a1', 16)));
same('tokeni fupi', 'err.not_found', route('GET', '/i/abc'));
same('tokeni ya herufi kubwa', 'err.not_found', route('GET', '/i/' . str_repeat('A1', 16)));
same('slug halali', 'slug:anna-and-tito-a1b2c3', route('GET', '/scan/anna-and-tito-a1b2c3'));
same('slug ya herufi kubwa', 'err.not_found', route('GET', '/scan/Anna-And-Tito'));
same('njia isiyopo', 'err.not_found', route('GET', '/haipo'));

$failed = count($results['fail']);

echo "\n------------------------------\n";
echo 'Majaribio yaliyopita: ' . $results['pass'] . "\n";
echo "Yaliyoshindwa: $failed\n";

if ($failed > 0) {
    echo "\nYaliyoshindwa:\n - " . implode("\n - ", $results['fail']) . "\n";
}

exit($failed === 0 ? 0 : 1);
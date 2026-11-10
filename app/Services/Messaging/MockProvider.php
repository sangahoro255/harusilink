<?php
namespace App\Services\Messaging;

class MockProvider implements MessageProvider
{
    private string $channel;

    public function __construct(string $channel)
    {
        $this->channel = $channel;
    }

    public function name(): string
    {
        return 'mock';
    }

    public function isSimulated(): bool
    {
        return true;
    }

    public function sendText(string $to, string $body): array
    {
        return $this->record($to, $body);
    }

    public function sendInvitation(string $to, array $data): array
    {
        return $this->record($to, (string) ($data['text'] ?? ''));
    }

    private function record(string $to, string $text): array
    {
        $line = date('c') . ' [' . $this->channel . '] ***' . substr($to, -3) . ' | '
            . str_replace(["\r", "\n"], ' ', $text);

        @file_put_contents(BASE_PATH . '/storage/logs/messages.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);

        return ['ok' => true, 'id' => 'mock-' . bin2hex(random_bytes(6)), 'error' => null];
    }
}
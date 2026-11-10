<?php
namespace App\Services\Messaging;

interface MessageProvider
{
    public function name(): string;

    public function isSimulated(): bool;

    public function sendText(string $to, string $body): array;

    public function sendInvitation(string $to, array $data): array;
}
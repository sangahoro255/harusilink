<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Invitation;
use App\Models\Wedding;
use App\Services\QrCode;

class QrController
{
    public function owner(int $weddingId, int $guestId): void
    {
        $user = Auth::requireRole('owner');
        $wedding = Wedding::findForOwner($weddingId, (int) $user['id']);
        $token = $wedding !== null ? Invitation::activeToken($weddingId, $guestId) : null;

        if ($token === null) {
            $this->notFound();
            return;
        }

        $this->send(QrCode::verifyUrl($token), 'harusilink-qr-' . $guestId . '.svg');
    }

    public function guest(string $token): void
    {
        $row = Invitation::findByToken($token);

        $allowed = $row !== null
            && $row['revoked_at'] === null
            && $row['wedding_status'] === 'active'
            && $row['rsvp_status'] === 'accepted';

        if (!$allowed) {
            $this->notFound();
            return;
        }

        $this->send(QrCode::verifyUrl($token), 'harusilink-qr.svg');
    }

    private function send(string $content, string $filename): void
    {
        try {
            $svg = QrCode::svg($content);
        } catch (\Throwable $ex) {
            error_log($ex->getMessage());
            http_response_code(500);
            echo e(t('err.generic'));
            return;
        }

        header('Content-Type: image/svg+xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: private, no-store');

        echo $svg;
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo e(t('err.not_found'));
    }
}
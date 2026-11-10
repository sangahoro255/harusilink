<?php
namespace App\Controllers;

use App\Models\Invitation;
use App\Models\Rsvp;
use App\Models\WeddingSettings;
use App\Services\QrCode;
use App\Services\RateLimiter;

class PublicInvitationController
{
    public function show(string $token): void
    {
        $this->headers();

        $row = $this->load($token);

        if ($row === null) {
            $this->unavailable();
            return;
        }

        $flash = $_SESSION['rsvp_flash'] ?? null;
        unset($_SESSION['rsvp_flash']);

        $canRsvp = $row['wedding_status'] === 'active';

        $qr = null;
        if ($canRsvp && $row['rsvp_status'] === 'accepted') {
            try {
                $qr = QrCode::dataUri(QrCode::verifyUrl($token));
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
            }
        }

        view('invitations/card', [
            'row'      => $row,
            'token'    => $token,
            'settings' => WeddingSettings::get((int) $row['wedding_id']),
            'flash'    => $flash,
            'canRsvp'  => $canRsvp,
            'qr'       => $qr,
        ]);
    }

    public function rsvp(string $token): void
    {
        $this->headers();
        csrf_verify();

        $row = $this->load($token);

        if ($row === null) {
            $this->unavailable();
            return;
        }

        if ($row['wedding_status'] !== 'active') {
            $_SESSION['rsvp_flash'] = 'rsvp.flash.closed';
            redirect('/i/' . $token);
        }

        $key = RateLimiter::clientKey('rsvp');

        if (RateLimiter::tooMany($key, 40, 600)) {
            $_SESSION['rsvp_flash'] = 'rsvp.flash.limit';
            redirect('/i/' . $token);
        }

        RateLimiter::hit($key);

        $status = post('status');

        if (in_array($status, Rsvp::GUEST_STATUSES, true)) {
            try {
                $result = Rsvp::record((int) $row['wedding_id'], (int) $row['guest_id'], $status, 'guest');

                $_SESSION['rsvp_flash'] = match ($result) {
                    'changed'   => 'rsvp.flash.' . $status,
                    'unchanged' => 'rsvp.flash.unchanged',
                    'limit'     => 'rsvp.flash.limit',
                    default     => 'rsvp.flash.error',
                };
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $_SESSION['rsvp_flash'] = 'rsvp.flash.error';
            }
        }

        redirect('/i/' . $token);
    }

    private function load(string $token): ?array
    {
        $row = Invitation::findByToken($token);

        $available = $row !== null
            && $row['revoked_at'] === null
            && in_array($row['wedding_status'], ['active', 'completed'], true);

        return $available ? $row : null;
    }

    private function unavailable(): void
    {
        http_response_code(404);
        view('invitations/unavailable');
    }

    private function headers(): void
    {
        header('X-Robots-Tag: noindex, nofollow');
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: private, no-store');
    }
}
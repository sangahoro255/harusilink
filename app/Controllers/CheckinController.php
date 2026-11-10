<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Checkin;
use App\Models\Wedding;
use App\Services\AuditLog;
use App\Services\ScannerAccess;
use App\Services\WeddingStats;

class CheckinController
{
    public function index(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        header('Cache-Control: private, no-store');

        $flash = $_SESSION['flash'] ?? null;
        $newPin = $_SESSION['new_pin'] ?? null;
        unset($_SESSION['flash'], $_SESSION['new_pin']);

        view('checkin/index', [
            'user'    => $user,
            'wedding' => $wedding,
            'stats'   => WeddingStats::forWedding($weddingId, (int) $user['id']),
            'counts'  => Checkin::counts($weddingId),
            'recent'  => Checkin::recent($weddingId, 20),
            'hasPin'  => ScannerAccess::hasPin($weddingId, (int) $user['id']),
            'newPin'  => is_string($newPin) ? $newPin : null,
            'flash'   => $flash,
        ]);
    }

    public function generatePin(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user] = $ctx;
        csrf_verify();

        $pin = ScannerAccess::setPin($weddingId, (int) $user['id']);

        if ($pin !== null) {
            $_SESSION['new_pin'] = $pin;
            AuditLog::record((int) $user['id'], 'checkin.pin_set', 'wedding', $weddingId);
        }

        redirect('/weddings/' . $weddingId . '/checkin');
    }

    public function disablePin(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user] = $ctx;
        csrf_verify();

        if (ScannerAccess::clearPin($weddingId, (int) $user['id'])) {
            AuditLog::record((int) $user['id'], 'checkin.pin_clear', 'wedding', $weddingId);
            $_SESSION['flash'] = 'checkin.flash.pin_cleared';
        }

        redirect('/weddings/' . $weddingId . '/checkin');
    }

    public function undo(int $weddingId, int $guestId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user] = $ctx;
        csrf_verify();

        if (Checkin::undo($weddingId, $guestId)) {
            AuditLog::record((int) $user['id'], 'checkin.undo', 'guest', $guestId, ['wedding_id' => $weddingId]);
            $_SESSION['flash'] = 'checkin.flash.undone';
        }

        redirect('/weddings/' . $weddingId . '/checkin');
    }

    private function context(int $weddingId): ?array
    {
        $user = Auth::requireRole('owner');
        $wedding = Wedding::findForOwner($weddingId, (int) $user['id']);

        if ($wedding === null) {
            http_response_code(404);
            echo e(t('err.not_found'));
            return null;
        }

        return [$user, $wedding];
    }
}
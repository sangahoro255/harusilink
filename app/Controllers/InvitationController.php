<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Invitation;
use App\Models\Wedding;
use App\Services\AuditLog;

class InvitationController
{
    public function index(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        Invitation::ensureForWedding($weddingId);

        $page = isset($_GET['page']) && is_string($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $result = Invitation::paginate($weddingId, $page, 25);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        view('invitations/index', [
            'user'    => $user,
            'wedding' => $wedding,
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'pages'   => $result['pages'],
            'flash'   => $flash,
        ]);
    }

    public function toggle(int $weddingId, int $guestId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user] = $ctx;
        csrf_verify();

        $state = Invitation::toggleRevoked($weddingId, $guestId);

        if ($state !== null) {
            AuditLog::record(
                (int) $user['id'],
                $state === 'revoked' ? 'invitation.revoke' : 'invitation.restore',
                'guest',
                $guestId,
                ['wedding_id' => $weddingId]
            );
            $_SESSION['flash'] = $state === 'revoked' ? 'inv.flash.revoked' : 'inv.flash.restored';
        }

        redirect($this->backUrl($weddingId));
    }

    public function regenerate(int $weddingId, int $guestId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user] = $ctx;
        csrf_verify();

        if (Invitation::regenerate($weddingId, $guestId)) {
            AuditLog::record((int) $user['id'], 'invitation.regenerate', 'guest', $guestId, ['wedding_id' => $weddingId]);
            $_SESSION['flash'] = 'inv.flash.regenerated';
        }

        redirect($this->backUrl($weddingId));
    }

    private function backUrl(int $weddingId): string
    {
        $page = max(1, (int) post('page'));
        return '/weddings/' . $weddingId . '/invitation' . ($page > 1 ? '?page=' . $page : '');
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
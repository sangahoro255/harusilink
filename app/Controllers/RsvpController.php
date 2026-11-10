<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Models\Wedding;
use App\Services\AuditLog;
use App\Services\WeddingStats;

class RsvpController
{
    public function index(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        $q = isset($_GET['q']) && is_string($_GET['q']) ? mb_substr(trim($_GET['q']), 0, 100) : '';
        $filter = isset($_GET['status']) && is_string($_GET['status']) && in_array($_GET['status'], Rsvp::STATUSES, true)
            ? $_GET['status']
            : '';
        $page = isset($_GET['page']) && is_string($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

        $result = Guest::paginate($weddingId, ['q' => $q, 'rsvp' => $filter, 'page' => $page], 25);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        view('rsvp/index', [
            'user'    => $user,
            'wedding' => $wedding,
            'stats'   => WeddingStats::forWedding($weddingId, (int) $user['id']),
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'pages'   => $result['pages'],
            'recent'  => Rsvp::recent($weddingId, 8),
            'q'       => $q,
            'filter'  => $filter,
            'flash'   => $flash,
        ]);
    }

    public function set(int $weddingId, int $guestId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user] = $ctx;
        csrf_verify();

        $status = post('status');

        if (in_array($status, Rsvp::STATUSES, true)) {
            try {
                $result = Rsvp::record($weddingId, $guestId, $status, 'owner');

                if ($result === 'changed') {
                    AuditLog::record((int) $user['id'], 'rsvp.set', 'guest', $guestId, [
                        'wedding_id' => $weddingId,
                        'status'     => $status,
                    ]);
                    $_SESSION['flash'] = 'rsvp.flash.set';
                }
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
            }
        }

        $filter = post('filter');
        $page = max(1, (int) post('page'));

        $query = http_build_query(array_filter([
            'q'      => mb_substr(post('q'), 0, 100),
            'status' => in_array($filter, Rsvp::STATUSES, true) ? $filter : '',
            'page'   => $page > 1 ? $page : null,
        ]));

        redirect('/weddings/' . $weddingId . '/rsvp' . ($query !== '' ? '?' . $query : ''));
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
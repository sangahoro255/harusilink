<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Guest;
use App\Models\GuestCategory;
use App\Models\Wedding;
use App\Services\AuditLog;
use App\Services\GuestValidator;

class GuestController
{
    public function index(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        $q = isset($_GET['q']) && is_string($_GET['q']) ? mb_substr(trim($_GET['q']), 0, 100) : '';
        $rsvp = isset($_GET['rsvp']) && is_string($_GET['rsvp']) && in_array($_GET['rsvp'], Guest::RSVP, true)
            ? $_GET['rsvp']
            : '';
        $cat = isset($_GET['cat']) && is_string($_GET['cat']) ? max(0, (int) $_GET['cat']) : 0;
        $page = isset($_GET['page']) && is_string($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

        $result = Guest::paginate($weddingId, ['q' => $q, 'rsvp' => $rsvp, 'category' => $cat, 'page' => $page], 25);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        view('guests/index', [
            'user'       => $user,
            'wedding'    => $wedding,
            'rows'       => $result['rows'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'pages'      => $result['pages'],
            'summary'    => Guest::summary($weddingId),
            'categories' => GuestCategory::forWedding($weddingId),
            'q'          => $q,
            'rsvp'       => $rsvp,
            'cat'        => $cat,
            'flash'      => $flash,
        ]);
    }

    public function create(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        view('guests/form', [
            'user'       => $user,
            'wedding'    => $wedding,
            'guest'      => null,
            'errors'     => [],
            'old'        => ['full_name' => '', 'phone' => '', 'email' => '', 'seats' => '1', 'category_id' => ''],
            'categories' => GuestCategory::forWedding($weddingId),
        ]);
    }

    public function store(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;
        csrf_verify();

        $old = $this->readInput();
        [$errors, $data] = $this->validate($old, $weddingId);

        if (!$errors) {
            try {
                $id = Guest::create($weddingId, $data);
                AuditLog::record((int) $user['id'], 'guest.create', 'guest', $id, ['wedding_id' => $weddingId]);
                $_SESSION['flash'] = 'guest.flash.created';
                redirect('/weddings/' . $weddingId . '/guests');
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $errors[] = t('err.generic');
            }
        }

        view('guests/form', [
            'user'       => $user,
            'wedding'    => $wedding,
            'guest'      => null,
            'errors'     => $errors,
            'old'        => $old,
            'categories' => GuestCategory::forWedding($weddingId),
        ]);
    }

    public function edit(int $weddingId, int $guestId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        $guest = Guest::find($weddingId, $guestId);
        if ($guest === null) {
            $this->notFound();
            return;
        }

        view('guests/form', [
            'user'       => $user,
            'wedding'    => $wedding,
            'guest'      => $guest,
            'errors'     => [],
            'old'        => [
                'full_name'   => $guest['full_name'],
                'phone'       => (string) $guest['phone'],
                'email'       => (string) $guest['email'],
                'seats'       => (string) $guest['seats'],
                'category_id' => $guest['category_id'] !== null ? (string) $guest['category_id'] : '',
            ],
            'categories' => GuestCategory::forWedding($weddingId),
        ]);
    }

    public function update(int $weddingId, int $guestId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;
        csrf_verify();

        $guest = Guest::find($weddingId, $guestId);
        if ($guest === null) {
            $this->notFound();
            return;
        }

        $old = $this->readInput();
        [$errors, $data] = $this->validate($old, $weddingId);

        if (!$errors) {
            try {
                Guest::update($weddingId, $guestId, $data);
                AuditLog::record((int) $user['id'], 'guest.update', 'guest', $guestId, ['wedding_id' => $weddingId]);
                $_SESSION['flash'] = 'guest.flash.updated';
                redirect('/weddings/' . $weddingId . '/guests');
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $errors[] = t('err.generic');
            }
        }

        view('guests/form', [
            'user'       => $user,
            'wedding'    => $wedding,
            'guest'      => $guest,
            'errors'     => $errors,
            'old'        => $old,
            'categories' => GuestCategory::forWedding($weddingId),
        ]);
    }

    public function delete(int $weddingId, int $guestId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user] = $ctx;
        csrf_verify();

        if (Guest::softDelete($weddingId, $guestId)) {
            AuditLog::record((int) $user['id'], 'guest.delete', 'guest', $guestId, ['wedding_id' => $weddingId]);
            $_SESSION['flash'] = 'guest.flash.deleted';
        }

        redirect('/weddings/' . $weddingId . '/guests');
    }

    private function context(int $weddingId): ?array
    {
        $user = Auth::requireRole('owner');
        $wedding = Wedding::findForOwner($weddingId, (int) $user['id']);

        if ($wedding === null) {
            $this->notFound();
            return null;
        }

        return [$user, $wedding];
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo e(t('err.not_found'));
    }

    private function readInput(): array
    {
        return [
            'full_name'   => trim(post('full_name')),
            'phone'       => trim(post('phone')),
            'email'       => strtolower(trim(post('email'))),
            'seats'       => trim(post('seats')),
            'category_id' => trim(post('category_id')),
        ];
    }

    private function validate(array $in, int $weddingId): array
    {
        [$errors, $data] = GuestValidator::check($in);
        $data['category_id'] = null;

        if ($in['category_id'] !== '') {
            if (ctype_digit($in['category_id']) && GuestCategory::belongsToWedding((int) $in['category_id'], $weddingId)) {
                $data['category_id'] = (int) $in['category_id'];
            } else {
                $errors[] = t('err.category');
            }
        }

        return [$errors, $data];
    }
}
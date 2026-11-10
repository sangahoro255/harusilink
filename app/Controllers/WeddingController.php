<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Wedding;
use App\Services\AuditLog;
use App\Services\ImageUpload;
use App\Services\WeddingStats;

class WeddingController
{
    public function create(): void
    {
        $user = Auth::requireRole('owner');
        view('weddings/create', ['user' => $user, 'errors' => [], 'old' => []]);
    }

    public function store(): void
    {
        $user = Auth::requireRole('owner');
        csrf_verify();

        $old = $this->input();
        $errors = $this->validate($old);

        if (!$errors) {
            try {
                $id = Wedding::create((int) $user['id'], $old);
                AuditLog::record((int) $user['id'], 'wedding.create', 'wedding', $id);
                redirect('/weddings/' . $id);
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $errors[] = t('err.generic');
            }
        }

        view('weddings/create', ['user' => $user, 'errors' => $errors, 'old' => $old]);
    }

    public function show(int $id): void
    {
        $user = Auth::requireRole('owner');
        $wedding = $this->ownedOrNotFound($id, $user);
        if ($wedding === null) {
            return;
        }

        $stats = WeddingStats::forWedding($id, (int) $user['id']);

        $tz = new \DateTimeZone('Africa/Dar_es_Salaam');
        $today = new \DateTimeImmutable('today', $tz);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $wedding['wedding_date'], $tz);
        $days = (int) $today->diff($date)->format('%r%a');

        $checklist = [
            ['label' => 'ws.check.details',  'done' => true,                              'link' => null],
            ['label' => 'ws.check.cover',    'done' => !empty($wedding['cover_image']),   'link' => '/weddings/' . $id . '/edit'],
            ['label' => 'ws.check.activate', 'done' => $wedding['status'] !== 'draft',    'link' => '/weddings/' . $id . '/details'],
            ['label' => 'ws.check.guests',   'done' => $stats['guests'] > 0,              'link' => '/weddings/' . $id . '/guests/create'],
        ];

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        view('weddings/show', [
            'user'      => $user,
            'wedding'   => $wedding,
            'stats'     => $stats,
            'days'      => $days,
            'checklist' => $checklist,
            'flash'     => $flash,
        ]);
    }

    public function details(int $id): void
    {
        $user = Auth::requireRole('owner');
        $wedding = $this->ownedOrNotFound($id, $user);
        if ($wedding === null) {
            return;
        }

        view('weddings/details', ['user' => $user, 'wedding' => $wedding]);
    }

    public function edit(int $id): void
    {
        $user = Auth::requireRole('owner');
        $wedding = $this->ownedOrNotFound($id, $user);
        if ($wedding === null) {
            return;
        }

        $old = [
            'bride_name'   => $wedding['bride_name'],
            'groom_name'   => $wedding['groom_name'],
            'wedding_date' => $wedding['wedding_date'],
            'wedding_time' => $wedding['wedding_time'] ? substr($wedding['wedding_time'], 0, 5) : '',
            'venue'        => $wedding['venue'],
            'description'  => (string) $wedding['description'],
        ];

        view('weddings/edit', ['user' => $user, 'wedding' => $wedding, 'errors' => [], 'old' => $old]);
    }

    public function update(int $id): void
    {
        $user = Auth::requireRole('owner');
        csrf_verify();

        $wedding = $this->ownedOrNotFound($id, $user);
        if ($wedding === null) {
            return;
        }

        $ownerId = (int) $user['id'];
        $old = $this->input();
        $errors = $this->validate($old, $wedding['wedding_date']);
        $newCover = null;

        $file = $_FILES['cover'] ?? null;
        $hasFile = is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if (!$errors && $hasFile) {
            try {
                $newCover = ImageUpload::saveCover($file);
            } catch (\InvalidArgumentException $ex) {
                $errors[] = t($ex->getMessage());
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $errors[] = t('err.generic');
            }
        }

        if (!$errors) {
            try {
                Wedding::update($id, $ownerId, $old);
                if ($newCover !== null) {
                    Wedding::setCover($id, $ownerId, $newCover);
                    ImageUpload::deleteCover($wedding['cover_image']);
                }
                AuditLog::record($ownerId, 'wedding.update', 'wedding', $id);
                $_SESSION['flash'] = 'wedding.flash.updated';
                redirect('/weddings/' . $id);
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                ImageUpload::deleteCover($newCover);
                $errors[] = t('err.generic');
            }
        }

        view('weddings/edit', ['user' => $user, 'wedding' => $wedding, 'errors' => $errors, 'old' => $old]);
    }

    public function activate(int $id): void
    {
        $user = Auth::requireRole('owner');
        csrf_verify();

        $wedding = $this->ownedOrNotFound($id, $user);
        if ($wedding === null) {
            return;
        }

        if (Wedding::changeStatus($id, (int) $user['id'], 'draft', 'active')) {
            AuditLog::record((int) $user['id'], 'wedding.activate', 'wedding', $id);
            $_SESSION['flash'] = 'wedding.flash.activated';
        }

        redirect('/weddings/' . $id);
    }

    private function ownedOrNotFound(int $id, array $user): ?array
    {
        $wedding = Wedding::findForOwner($id, (int) $user['id']);

        if ($wedding === null) {
            http_response_code(404);
            echo e(t('err.not_found'));
        }

        return $wedding;
    }

    private function input(): array
    {
        return [
            'bride_name'   => trim(post('bride_name')),
            'groom_name'   => trim(post('groom_name')),
            'wedding_date' => trim(post('wedding_date')),
            'wedding_time' => trim(post('wedding_time')),
            'venue'        => trim(post('venue')),
            'description'  => trim(post('description')),
        ];
    }

    private function validate(array $in, ?string $currentDate = null): array
    {
        $errors = [];

        if (mb_strlen($in['bride_name']) < 2 || mb_strlen($in['bride_name']) > 100) {
            $errors[] = t('err.bride_name');
        }
        if (mb_strlen($in['groom_name']) < 2 || mb_strlen($in['groom_name']) > 100) {
            $errors[] = t('err.groom_name');
        }

        $tz = new \DateTimeZone('Africa/Dar_es_Salaam');
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $in['wedding_date'], $tz);
        if (!$date || $date->format('Y-m-d') !== $in['wedding_date']) {
            $errors[] = t('err.date');
        } elseif ($in['wedding_date'] !== $currentDate && $date < new \DateTimeImmutable('today', $tz)) {
            $errors[] = t('err.date_past');
        }

        if ($in['wedding_time'] !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $in['wedding_time'])) {
            $errors[] = t('err.time');
        }
        if (mb_strlen($in['venue']) < 2 || mb_strlen($in['venue']) > 255) {
            $errors[] = t('err.venue');
        }
        if (mb_strlen($in['description']) > 2000) {
            $errors[] = t('err.description');
        }

        return $errors;
    }
}
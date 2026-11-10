<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Helpers\Money;
use App\Models\Contribution;
use App\Models\ContributionSettings;
use App\Models\Guest;
use App\Models\Wedding;
use App\Services\AuditLog;

class ContributionController
{
    public function index(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        $q = isset($_GET['q']) && is_string($_GET['q']) ? mb_substr(trim($_GET['q']), 0, 100) : '';
        $status = isset($_GET['status']) && is_string($_GET['status']) && in_array($_GET['status'], Contribution::STATUSES, true)
            ? $_GET['status']
            : '';
        $page = isset($_GET['page']) && is_string($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

        $result = Contribution::paginate($weddingId, ['q' => $q, 'status' => $status, 'page' => $page], 25);
        $settings = ContributionSettings::get($weddingId);
        $totals = Contribution::totals($weddingId);

        $target = $settings['target'] !== null ? (float) $settings['target'] : null;
        $percent = ($target !== null && $target > 0) ? min(100, (int) floor($totals['paid'] / $target * 100)) : null;

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        view('contributions/index', [
            'user'     => $user,
            'wedding'  => $wedding,
            'rows'     => $result['rows'],
            'total'    => $result['total'],
            'page'     => $result['page'],
            'pages'    => $result['pages'],
            'settings' => $settings,
            'totals'   => $totals,
            'target'   => $target,
            'percent'  => $percent,
            'daysLeft' => $this->daysUntil($settings['deadline']),
            'q'        => $q,
            'status'   => $status,
            'flash'    => $flash,
        ]);
    }

    public function create(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        view('contributions/form', [
            'user'         => $user,
            'wedding'      => $wedding,
            'contribution' => null,
            'errors'       => [],
            'old'          => [
                'contributor_name' => '',
                'phone'            => '',
                'amount'           => '',
                'method'           => 'cash',
                'reference'        => '',
                'status'           => 'paid',
                'contributed_on'   => $this->today(),
                'note'             => '',
            ],
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
        [$errors, $data] = $this->validate($old);

        if (!$errors) {
            try {
                $id = Contribution::create($weddingId, $data);
                AuditLog::record((int) $user['id'], 'contribution.create', 'contribution', $id, ['wedding_id' => $weddingId]);
                $_SESSION['flash'] = 'contrib.flash.created';
                redirect('/weddings/' . $weddingId . '/contributions');
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $errors[] = t('err.generic');
            }
        }

        view('contributions/form', [
            'user'         => $user,
            'wedding'      => $wedding,
            'contribution' => null,
            'errors'       => $errors,
            'old'          => $old,
        ]);
    }

    public function edit(int $weddingId, int $contributionId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        $contribution = Contribution::find($weddingId, $contributionId);
        if ($contribution === null) {
            $this->notFound();
            return;
        }

        view('contributions/form', [
            'user'         => $user,
            'wedding'      => $wedding,
            'contribution' => $contribution,
            'errors'       => [],
            'old'          => [
                'contributor_name' => $contribution['contributor_name'],
                'phone'            => (string) $contribution['phone'],
                'amount'           => rtrim(rtrim((string) $contribution['amount'], '0'), '.'),
                'method'           => $contribution['method'],
                'reference'        => (string) $contribution['reference'],
                'status'           => $contribution['status'],
                'contributed_on'   => $contribution['contributed_on'],
                'note'             => (string) $contribution['note'],
            ],
        ]);
    }

    public function update(int $weddingId, int $contributionId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;
        csrf_verify();

        $contribution = Contribution::find($weddingId, $contributionId);
        if ($contribution === null) {
            $this->notFound();
            return;
        }

        $old = $this->readInput();
        [$errors, $data] = $this->validate($old);

        if (!$errors) {
            try {
                Contribution::update($weddingId, $contributionId, $data);
                AuditLog::record((int) $user['id'], 'contribution.update', 'contribution', $contributionId, ['wedding_id' => $weddingId]);
                $_SESSION['flash'] = 'contrib.flash.updated';
                redirect('/weddings/' . $weddingId . '/contributions');
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $errors[] = t('err.generic');
            }
        }

        view('contributions/form', [
            'user'         => $user,
            'wedding'      => $wedding,
            'contribution' => $contribution,
            'errors'       => $errors,
            'old'          => $old,
        ]);
    }

    public function markPaid(int $weddingId, int $contributionId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user] = $ctx;
        csrf_verify();

        if (Contribution::markPaid($weddingId, $contributionId, $this->today())) {
            AuditLog::record((int) $user['id'], 'contribution.paid', 'contribution', $contributionId, ['wedding_id' => $weddingId]);
            $_SESSION['flash'] = 'contrib.flash.paid';
        }

        redirect('/weddings/' . $weddingId . '/contributions');
    }

    public function delete(int $weddingId, int $contributionId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user] = $ctx;
        csrf_verify();

        if (Contribution::softDelete($weddingId, $contributionId)) {
            AuditLog::record((int) $user['id'], 'contribution.delete', 'contribution', $contributionId, ['wedding_id' => $weddingId]);
            $_SESSION['flash'] = 'contrib.flash.deleted';
        }

        redirect('/weddings/' . $weddingId . '/contributions');
    }

    public function settings(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        $settings = ContributionSettings::get($weddingId);

        view('contributions/settings', [
            'user'    => $user,
            'wedding' => $wedding,
            'errors'  => [],
            'old'     => [
                'target'       => $settings['target'] !== null ? rtrim(rtrim((string) $settings['target'], '0'), '.') : '',
                'deadline'     => (string) $settings['deadline'],
                'description'  => (string) $settings['description'],
                'payment_info' => (string) $settings['payment_info'],
            ],
        ]);
    }

    public function saveSettings(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;
        csrf_verify();

        $old = [
            'target'       => trim(post('target')),
            'deadline'     => trim(post('deadline')),
            'description'  => str_replace("\r\n", "\n", trim(post('description'))),
            'payment_info' => str_replace("\r\n", "\n", trim(post('payment_info'))),
        ];
        $errors = [];
        $data = ['target' => null, 'deadline' => null, 'description' => null, 'payment_info' => null];

        if ($old['target'] !== '') {
            $amount = Money::parse($old['target']);
            if ($amount === null) {
                $errors[] = t('err.contrib_target');
            } else {
                $data['target'] = $amount;
            }
        }

        if ($old['deadline'] !== '') {
            if ($this->validDate($old['deadline'])) {
                $data['deadline'] = $old['deadline'];
            } else {
                $errors[] = t('err.contrib_deadline');
            }
        }

        foreach (['description', 'payment_info'] as $field) {
            if (mb_strlen($old[$field]) > 1000) {
                $errors[] = t('err.contrib_text');
            } elseif ($old[$field] !== '') {
                $data[$field] = $old[$field];
            }
        }

        if (!$errors) {
            try {
                ContributionSettings::save($weddingId, $data);
                AuditLog::record((int) $user['id'], 'contribution.settings', 'wedding', $weddingId);
                $_SESSION['flash'] = 'contrib.flash.settings';
                redirect('/weddings/' . $weddingId . '/contributions');
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $errors[] = t('err.generic');
            }
        }

        view('contributions/settings', ['user' => $user, 'wedding' => $wedding, 'errors' => $errors, 'old' => $old]);
    }

    private function readInput(): array
    {
        return [
            'contributor_name' => trim(post('contributor_name')),
            'phone'            => trim(post('phone')),
            'amount'           => trim(post('amount')),
            'method'           => trim(post('method')),
            'reference'        => trim(post('reference')),
            'status'           => trim(post('status')),
            'contributed_on'   => trim(post('contributed_on')),
            'note'             => trim(post('note')),
        ];
    }

    private function validate(array $in): array
    {
        $errors = [];
        $data = [
            'contributor_name' => $in['contributor_name'],
            'phone'            => null,
            'amount'           => '0',
            'method'           => 'cash',
            'reference'        => null,
            'status'           => 'paid',
            'contributed_on'   => $in['contributed_on'],
            'note'             => null,
        ];

        if (mb_strlen($in['contributor_name']) < 2 || mb_strlen($in['contributor_name']) > 120) {
            $errors[] = t('err.contrib_name');
        }

        if ($in['phone'] !== '') {
            $phone = Guest::normalizePhone($in['phone']);
            if ($phone === null) {
                $errors[] = t('err.guest_phone');
            }
            $data['phone'] = $phone;
        }

        $amount = Money::parse($in['amount']);
        if ($amount === null) {
            $errors[] = t('err.contrib_amount');
        } else {
            $data['amount'] = $amount;
        }

        if (in_array($in['method'], Contribution::METHODS, true)) {
            $data['method'] = $in['method'];
        } else {
            $errors[] = t('err.contrib_method');
        }

        if (in_array($in['status'], Contribution::STATUSES, true)) {
            $data['status'] = $in['status'];
        } else {
            $errors[] = t('err.contrib_status');
        }

        if (mb_strlen($in['reference']) > 80) {
            $errors[] = t('err.contrib_reference');
        } elseif ($in['reference'] !== '') {
            $data['reference'] = $in['reference'];
        }

        if (!$this->validDate($in['contributed_on'])) {
            $errors[] = t('err.contrib_date');
        } elseif ($data['status'] === 'paid' && $in['contributed_on'] > $this->today()) {
            $errors[] = t('err.contrib_date_future');
        }

        if (mb_strlen($in['note']) > 255) {
            $errors[] = t('err.contrib_note');
        } elseif ($in['note'] !== '') {
            $data['note'] = $in['note'];
        }

        return [$errors, $data];
    }

    private function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function today(): string
    {
        return (new \DateTimeImmutable('today', new \DateTimeZone('Africa/Dar_es_Salaam')))->format('Y-m-d');
    }

    private function daysUntil(?string $date): ?int
    {
        if ($date === null || $date === '') {
            return null;
        }

        $tz = new \DateTimeZone('Africa/Dar_es_Salaam');
        $target = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, $tz);

        if ($target === false) {
            return null;
        }

        return (int) (new \DateTimeImmutable('today', $tz))->diff($target)->format('%r%a');
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo e(t('err.not_found'));
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
}
<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Helpers\FormNonce;
use App\Helpers\Format;
use App\Helpers\Money;
use App\Models\Contribution;
use App\Models\ContributionSettings;
use App\Models\Message;
use App\Models\Wedding;
use App\Services\AuditLog;
use App\Services\Messaging\Messenger;
use App\Services\RateLimiter;

class ReminderController
{
    public function index(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        $flash = $_SESSION['flash'] ?? null;
        $flashVars = $_SESSION['flash_vars'] ?? [];
        unset($_SESSION['flash'], $_SESSION['flash_vars']);

        $this->render(
            $user,
            $wedding,
            [],
            ['channel' => 'whatsapp', 'body' => t('rem.default_body')],
            is_string($flash) ? $flash : null,
            is_array($flashVars) ? $flashVars : []
        );
    }

    public function send(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;
        csrf_verify();

        if (!FormNonce::consume('reminder:' . $weddingId, post('nonce'))) {
            $_SESSION['flash'] = 'msg.flash.expired';
            redirect('/weddings/' . $weddingId . '/reminders');
        }

        $only = (int) post('only');
        $audience = $only > 0 ? 'selected' : trim(post('audience'));

        $ids = [];
        if ($only > 0) {
            $ids = [$only];
        } elseif (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = array_map('intval', array_filter($_POST['ids'], 'is_string'));
        }

        $old = [
            'channel' => trim(post('channel')),
            'body'    => trim(str_replace("\r\n", "\n", post('body'))),
        ];
        $errors = [];

        if (!in_array($old['channel'], ['whatsapp', 'sms'], true)) {
            $errors[] = t('msg.err.channel');
        }
        if (!in_array($audience, ['selected', 'pledged_all', 'no_contribution'], true)) {
            $errors[] = t('msg.err.audience');
        }
        if (mb_strlen($old['body']) < 10 || mb_strlen($old['body']) > 500) {
            $errors[] = t('rem.err.body');
        }
        if ($audience === 'no_contribution' && str_contains($old['body'], '{amount}')) {
            $errors[] = t('rem.err.amount_var');
        }
        if ($audience === 'selected' && !$ids) {
            $errors[] = t('rem.err.none_selected');
        }
        if (!$errors && !Messenger::available($old['channel'])) {
            $errors[] = t('msg.err.unavailable');
        }
        if (!$errors && RateLimiter::tooMany('send:' . $weddingId, 12, 3600)) {
            $errors[] = t('err.rate_limited');
        }

        $skipped = 0;
        $recipients = [];

        if (!$errors) {
            $settings = ContributionSettings::get($weddingId);
            $deadline = $settings['deadline'] ? Format::date($settings['deadline']) : '';
            $couple = $wedding['bride_name'] . ' & ' . $wedding['groom_name'];
            $cooldown = Message::recentlyReminded($weddingId);
            $isGuest = $audience === 'no_contribution';

            if ($audience === 'selected') {
                $candidates = Contribution::pledgedByIds($weddingId, $ids);
            } elseif ($audience === 'pledged_all') {
                $candidates = Contribution::pledgedWithPhone($weddingId);
            } else {
                $candidates = Contribution::noContributionGuests($weddingId);
            }

            $seen = [];

            foreach ($candidates as $candidate) {
                $phone = $candidate['phone'];

                if (isset($cooldown[$phone])) {
                    $skipped++;
                    continue;
                }
                if (isset($seen[$phone])) {
                    continue;
                }
                $seen[$phone] = true;

                $recipients[] = [
                    'phone'           => $phone,
                    'guest_id'        => $isGuest ? (int) $candidate['id'] : null,
                    'contribution_id' => $isGuest ? null : (int) $candidate['id'],
                    'vars'            => [
                        'name'     => $isGuest ? $candidate['full_name'] : $candidate['contributor_name'],
                        'amount'   => $isGuest ? '' : Money::format($candidate['amount']),
                        'deadline' => $deadline,
                        'couple'   => $couple,
                    ],
                ];
            }

            if (!$recipients) {
                $errors[] = t('rem.err.nobody', ['skipped' => $skipped, 'days' => Message::REMINDER_COOLDOWN_DAYS]);
            }
        }

        if (!$errors) {
            try {
                $left = max(0, count($recipients) - Messenger::MAX_PER_SEND);

                RateLimiter::hit('send:' . $weddingId);
                $result = Messenger::dispatch($weddingId, (int) $user['id'], 'contribution_reminder', $old['channel'], $old['body'], $recipients);

                AuditLog::record((int) $user['id'], 'message.reminder', 'message', $result['message_id'], [
                    'wedding_id' => $weddingId,
                    'audience'   => $audience,
                    'sent'       => $result['sent'],
                    'failed'     => $result['failed'],
                    'skipped'    => $skipped,
                    'simulated'  => $result['simulated'],
                ]);

                $_SESSION['flash'] = $result['simulated'] ? 'rem.flash.sent_sim' : 'rem.flash.sent';
                $_SESSION['flash_vars'] = [
                    'sent'    => $result['sent'],
                    'failed'  => $result['failed'],
                    'skipped' => $skipped,
                    'left'    => $left,
                ];
                redirect('/weddings/' . $weddingId . '/reminders');
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $errors[] = t('err.generic');
            }
        }

        $this->render($user, $wedding, $errors, $old, null, []);
    }

    private function render(array $user, array $wedding, array $errors, array $old, ?string $flash, array $flashVars): void
    {
        $weddingId = (int) $wedding['id'];
        $cooldown = Message::recentlyReminded($weddingId);

        $pledged = Contribution::pledgedWithPhone($weddingId);
        foreach ($pledged as $index => $row) {
            $pledged[$index]['reminded_at'] = $cooldown[$row['phone']] ?? null;
        }

        $noContribution = 0;
        foreach (Contribution::noContributionGuests($weddingId) as $guest) {
            if (!isset($cooldown[$guest['phone']])) {
                $noContribution++;
            }
        }

        view('reminders/index', [
            'user'           => $user,
            'wedding'        => $wedding,
            'pledged'        => $pledged,
            'noContribution' => $noContribution,
            'history'        => Message::recentReminders($weddingId, 15),
            'channels'       => ['whatsapp' => Messenger::available('whatsapp'), 'sms' => Messenger::available('sms')],
            'mode'           => Messenger::mode(),
            'days'           => Message::REMINDER_COOLDOWN_DAYS,
            'nonce'          => FormNonce::issue('reminder:' . $weddingId),
            'errors'         => $errors,
            'old'            => $old,
            'flash'          => $flash,
            'flashVars'      => $flashVars,
        ]);
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
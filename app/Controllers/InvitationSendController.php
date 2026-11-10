<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Helpers\FormNonce;
use App\Helpers\Format;
use App\Helpers\Url;
use App\Models\Invitation;
use App\Models\Recipients;
use App\Models\Wedding;
use App\Services\AuditLog;
use App\Services\Messaging\Messenger;
use App\Services\RateLimiter;

class InvitationSendController
{
    public function show(int $weddingId): void
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
            ['channel' => 'whatsapp', 'scope' => 'unsent'],
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

        if (!FormNonce::consume('invite:' . $weddingId, post('nonce'))) {
            $_SESSION['flash'] = 'msg.flash.expired';
            redirect('/weddings/' . $weddingId . '/messages/invitations');
        }

        $old = ['channel' => trim(post('channel')), 'scope' => trim(post('scope'))];
        $errors = [];

        if ($wedding['status'] !== 'active') {
            $errors[] = t('msg.err.not_active');
        }
        if (!in_array($old['channel'], ['whatsapp', 'sms'], true)) {
            $errors[] = t('msg.err.channel');
        }
        if (!in_array($old['scope'], ['unsent', 'all'], true)) {
            $errors[] = t('msg.err.audience');
        }
        if (!$errors && !Messenger::available($old['channel'])) {
            $errors[] = t('msg.err.unavailable');
        }
        if (!$errors && RateLimiter::tooMany('send:' . $weddingId, 12, 3600)) {
            $errors[] = t('err.rate_limited');
        }

        $recipients = [];

        if (!$errors) {
            Invitation::ensureForWedding($weddingId);

            $couple = $wedding['bride_name'] . ' & ' . $wedding['groom_name'];
            $dateText = Format::date($wedding['wedding_date']);

            foreach (Recipients::forInvitations($weddingId, $old['scope'] === 'unsent') as $row) {
                $link = Url::absolute('/i/' . $row['token']);

                $recipients[] = [
                    'phone'           => $row['phone'],
                    'guest_id'        => (int) $row['id'],
                    'contribution_id' => null,
                    'vars'            => ['name' => $row['full_name'], 'couple' => $couple, 'date' => $dateText, 'link' => $link],
                    'invite'          => [
                        'guest_name' => $row['full_name'],
                        'couple'     => $couple,
                        'date_text'  => $dateText,
                        'link'       => $link,
                        'token'      => $row['token'],
                    ],
                ];
            }

            if (!$recipients) {
                $errors[] = t('msg.err.no_recipients');
            } elseif (count($recipients) > Messenger::MAX_PER_SEND) {
                $errors[] = t('msg.err.too_many', ['max' => Messenger::MAX_PER_SEND, 'count' => count($recipients)]);
            }
        }

        if (!$errors) {
            try {
                RateLimiter::hit('send:' . $weddingId);
                $result = Messenger::dispatch($weddingId, (int) $user['id'], 'invitation', $old['channel'], t('msg.invite.text'), $recipients);

                $delivered = [];
                foreach ($result['results'] as $item) {
                    if ($item['ok'] && $item['guest_id'] !== null) {
                        $delivered[] = $item['guest_id'];
                    }
                }
                Recipients::markInvited($weddingId, $delivered, $old['channel']);

                AuditLog::record((int) $user['id'], 'message.invitations', 'message', $result['message_id'], [
                    'wedding_id' => $weddingId,
                    'sent'       => $result['sent'],
                    'failed'     => $result['failed'],
                    'simulated'  => $result['simulated'],
                ]);

                $_SESSION['flash'] = $result['simulated'] ? 'msg.flash.sent_sim' : 'msg.flash.sent';
                $_SESSION['flash_vars'] = ['sent' => $result['sent'], 'failed' => $result['failed']];
                redirect('/weddings/' . $weddingId . '/messages/invitations');
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

        Invitation::ensureForWedding($weddingId);

        view('messages/invitations', [
            'user'      => $user,
            'wedding'   => $wedding,
            'counts'    => Recipients::invitationCounts($weddingId),
            'channels'  => ['whatsapp' => Messenger::available('whatsapp'), 'sms' => Messenger::available('sms')],
            'mode'      => Messenger::mode(),
            'max'       => Messenger::MAX_PER_SEND,
            'nonce'     => FormNonce::issue('invite:' . $weddingId),
            'errors'    => $errors,
            'old'       => $old,
            'flash'     => $flash,
            'flashVars' => $flashVars,
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
<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Helpers\FormNonce;
use App\Models\Message;
use App\Models\Recipients;
use App\Models\Wedding;
use App\Services\AuditLog;
use App\Services\Messaging\Messenger;
use App\Services\RateLimiter;

class MessageController
{
    public function index(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        [$flash, $flashVars] = $this->takeFlash();

        $this->renderIndex($user, $wedding, [], ['channel' => 'whatsapp', 'audience' => 'all', 'body' => ''], $flash, $flashVars);
    }

    public function send(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;
        csrf_verify();

        if (!FormNonce::consume('notice:' . $weddingId, post('nonce'))) {
            $_SESSION['flash'] = 'msg.flash.expired';
            redirect('/weddings/' . $weddingId . '/messages');
        }

        $old = [
            'channel'  => trim(post('channel')),
            'audience' => trim(post('audience')),
            'body'     => trim(str_replace("\r\n", "\n", post('body'))),
        ];
        $errors = [];

        if (!in_array($old['channel'], ['whatsapp', 'sms'], true)) {
            $errors[] = t('msg.err.channel');
        }
        if (!in_array($old['audience'], ['all', 'accepted', 'pending', 'declined'], true)) {
            $errors[] = t('msg.err.audience');
        }
        if (mb_strlen($old['body']) < 5 || mb_strlen($old['body']) > 500) {
            $errors[] = t('msg.err.body');
        }
        if (!$errors && !Messenger::available($old['channel'])) {
            $errors[] = t('msg.err.unavailable');
        }
        if (!$errors && RateLimiter::tooMany('send:' . $weddingId, 12, 3600)) {
            $errors[] = t('err.rate_limited');
        }

        $recipients = [];

        if (!$errors) {
            $couple = $wedding['bride_name'] . ' & ' . $wedding['groom_name'];

            foreach (Recipients::guests($weddingId, $old['audience']) as $guest) {
                $recipients[] = [
                    'phone'           => $guest['phone'],
                    'guest_id'        => (int) $guest['id'],
                    'contribution_id' => null,
                    'vars'            => ['name' => $guest['full_name'], 'couple' => $couple],
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
                $result = Messenger::dispatch($weddingId, (int) $user['id'], 'notice', $old['channel'], $old['body'], $recipients);

                AuditLog::record((int) $user['id'], 'message.notice', 'message', $result['message_id'], [
                    'wedding_id' => $weddingId,
                    'sent'       => $result['sent'],
                    'failed'     => $result['failed'],
                    'simulated'  => $result['simulated'],
                ]);

                $_SESSION['flash'] = $result['simulated'] ? 'msg.flash.sent_sim' : 'msg.flash.sent';
                $_SESSION['flash_vars'] = ['sent' => $result['sent'], 'failed' => $result['failed']];
                redirect('/weddings/' . $weddingId . '/messages');
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $errors[] = t('err.generic');
            }
        }

        $this->renderIndex($user, $wedding, $errors, $old, null, []);
    }

    public function show(int $weddingId, int $messageId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        $message = Message::find($weddingId, $messageId);
        if ($message === null) {
            $this->notFound();
            return;
        }

        view('messages/show', [
            'user'    => $user,
            'wedding' => $wedding,
            'message' => $message,
            'logs'    => Message::logs($weddingId, $messageId),
        ]);
    }

    private function renderIndex(array $user, array $wedding, array $errors, array $old, ?string $flash, array $flashVars): void
    {
        $weddingId = (int) $wedding['id'];
        $page = isset($_GET['page']) && is_string($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $result = Message::paginate($weddingId, $page, 15);

        view('messages/index', [
            'user'      => $user,
            'wedding'   => $wedding,
            'rows'      => $result['rows'],
            'total'     => $result['total'],
            'page'      => $result['page'],
            'pages'     => $result['pages'],
            'counts'    => Recipients::counts($weddingId),
            'channels'  => ['whatsapp' => Messenger::available('whatsapp'), 'sms' => Messenger::available('sms')],
            'mode'      => Messenger::mode(),
            'max'       => Messenger::MAX_PER_SEND,
            'nonce'     => FormNonce::issue('notice:' . $weddingId),
            'errors'    => $errors,
            'old'       => $old,
            'flash'     => $flash,
            'flashVars' => $flashVars,
        ]);
    }

    private function takeFlash(): array
    {
        $flash = $_SESSION['flash'] ?? null;
        $vars = $_SESSION['flash_vars'] ?? [];
        unset($_SESSION['flash'], $_SESSION['flash_vars']);

        return [is_string($flash) ? $flash : null, is_array($vars) ? $vars : []];
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
<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Wedding;
use App\Models\WeddingSettings;
use App\Services\AuditLog;
use App\Services\InvitationTemplates;

class InvitationDesignController
{
    public function show(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        $settings = WeddingSettings::get($weddingId);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        view('invitations/design', [
            'user'     => $user,
            'wedding'  => $wedding,
            'settings' => $settings,
            'errors'   => [],
            'old'      => [
                'template'      => $settings['template'],
                'palette'       => $settings['palette'],
                'primary_color' => $settings['primary_color'],
                'accent_color'  => $settings['accent_color'],
                'message'       => (string) $settings['invitation_message'],
            ],
            'flash'    => $flash,
        ]);
    }

    public function save(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;
        csrf_verify();

        $old = [
            'template'      => trim(post('template')),
            'palette'       => trim(post('palette')),
            'primary_color' => trim(post('primary_color')),
            'accent_color'  => trim(post('accent_color')),
            'message'       => str_replace("\r\n", "\n", trim(post('message'))),
        ];

        [$errors, $data] = $this->validate($old);

        if (!$errors) {
            try {
                WeddingSettings::save($weddingId, $data);
                AuditLog::record((int) $user['id'], 'invitation.design', 'wedding', $weddingId);
                $_SESSION['flash'] = 'inv.flash.design_saved';
                redirect('/weddings/' . $weddingId . '/invitation/design');
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $errors[] = t('err.generic');
            }
        }

        view('invitations/design', [
            'user'     => $user,
            'wedding'  => $wedding,
            'settings' => WeddingSettings::get($weddingId),
            'errors'   => $errors,
            'old'      => $old,
            'flash'    => null,
        ]);
    }

    private function validate(array $in): array
    {
        $errors = [];
        $data = [
            'template'      => 'classic',
            'palette'       => 'navy_gold',
            'primary_color' => InvitationTemplates::DEFAULT_PRIMARY,
            'accent_color'  => InvitationTemplates::DEFAULT_ACCENT,
            'message'       => null,
        ];

        if (in_array($in['template'], InvitationTemplates::TEMPLATES, true)) {
            $data['template'] = $in['template'];
        } else {
            $errors[] = t('err.inv_template');
        }

        if ($in['palette'] === 'custom') {
            if (InvitationTemplates::isHex($in['primary_color']) && InvitationTemplates::isHex($in['accent_color'])) {
                $data['palette'] = 'custom';
                $data['primary_color'] = strtoupper($in['primary_color']);
                $data['accent_color'] = strtoupper($in['accent_color']);
            } else {
                $errors[] = t('err.inv_color');
            }
        } elseif (isset(InvitationTemplates::PALETTES[$in['palette']])) {
            [$primary, $accent] = InvitationTemplates::PALETTES[$in['palette']];
            $data['palette'] = $in['palette'];
            $data['primary_color'] = $primary;
            $data['accent_color'] = $accent;
        } else {
            $errors[] = t('err.inv_palette');
        }

        if (!$errors && InvitationTemplates::contrastWithWhite($data['primary_color']) < InvitationTemplates::MIN_CONTRAST) {
            $errors[] = t('err.inv_contrast');
        }

        if (mb_strlen($in['message']) > 600) {
            $errors[] = t('err.inv_message');
        } elseif ($in['message'] !== '') {
            $data['message'] = $in['message'];
        }

        return [$errors, $data];
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
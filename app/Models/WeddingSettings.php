<?php
namespace App\Models;

use App\Services\InvitationTemplates;

class WeddingSettings
{
    private const DEFAULTS = [
        'template'           => 'classic',
        'palette'            => 'navy_gold',
        'primary_color'      => InvitationTemplates::DEFAULT_PRIMARY,
        'accent_color'       => InvitationTemplates::DEFAULT_ACCENT,
        'invitation_message' => null,
    ];

    public static function get(int $weddingId): array
    {
        $stmt = db()->prepare(
            'SELECT template, palette, primary_color, accent_color, invitation_message
             FROM wedding_settings
             WHERE wedding_id = :wedding
             LIMIT 1'
        );
        $stmt->execute([':wedding' => $weddingId]);

        return ($stmt->fetch() ?: []) + self::DEFAULTS;
    }

    public static function save(int $weddingId, array $data): void
    {
        $stmt = db()->prepare(
            'INSERT INTO wedding_settings (wedding_id, template, palette, primary_color, accent_color, invitation_message)
             VALUES (:wedding, :template, :palette, :primary, :accent, :message)
             ON DUPLICATE KEY UPDATE
                template = VALUES(template),
                palette = VALUES(palette),
                primary_color = VALUES(primary_color),
                accent_color = VALUES(accent_color),
                invitation_message = VALUES(invitation_message)'
        );
        $stmt->execute([
            ':wedding'  => $weddingId,
            ':template' => $data['template'],
            ':palette'  => $data['palette'],
            ':primary'  => $data['primary_color'],
            ':accent'   => $data['accent_color'],
            ':message'  => $data['message'],
        ]);
    }
}
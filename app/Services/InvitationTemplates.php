<?php
namespace App\Services;

class InvitationTemplates
{
    public const TEMPLATES = ['classic', 'modern'];

    public const PALETTES = [
        'navy_gold'     => ['#0B1F3A', '#D4A72C'],
        'burgundy_gold' => ['#6B1D2E', '#D4A72C'],
        'emerald_gold'  => ['#0F5132', '#D4A72C'],
        'plum_rose'     => ['#4A2C5A', '#D98FA8'],
        'teal_coral'    => ['#0E5A63', '#E07A5F'],
    ];

    public const DEFAULT_PRIMARY = '#0B1F3A';
    public const DEFAULT_ACCENT  = '#D4A72C';
    public const MIN_CONTRAST    = 4.5;

    public static function isHex(string $value): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $value);
    }

    public static function contrastWithWhite(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $channels = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];

        $linear = array_map(function ($value) {
            $c = $value / 255;
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, $channels);

        $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];

        return 1.05 / ($luminance + 0.05);
    }

    public static function styleVars(array $settings): string
    {
        $primary = self::isHex((string) ($settings['primary_color'] ?? '')) ? $settings['primary_color'] : self::DEFAULT_PRIMARY;
        $accent  = self::isHex((string) ($settings['accent_color'] ?? '')) ? $settings['accent_color'] : self::DEFAULT_ACCENT;

        return '--inv-primary:' . $primary . ';--inv-accent:' . $accent;
    }

    public static function render(array $wedding, array $settings, ?string $guestName, int $seats): void
    {
        $template = in_array($settings['template'] ?? '', self::TEMPLATES, true) ? $settings['template'] : 'classic';

        $custom = trim((string) ($settings['invitation_message'] ?? ''));
        $message = $custom !== '' ? $custom : t('inv.default_message');

        view('invitations/templates/' . $template, [
            'wedding'   => $wedding,
            'guestName' => $guestName,
            'seats'     => $seats,
            'message'   => $message,
            'style'     => self::styleVars($settings),
        ]);
    }
}
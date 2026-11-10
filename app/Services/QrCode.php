<?php
namespace App\Services;

use App\Helpers\Url;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCode
{
    public static function verifyUrl(string $token): string
    {
        return Url::absolute('/verify/' . $token);
    }

    public static function svg(string $content): string
    {
        // Ikiwa maktaba ya Bacon imesakinishwa (composer install) itatumika;
        // vinginevyo kisimbaji cha ndani (SimpleQr) kinatumika - hakuna hatua ya ziada inayohitajika.
        if (self::loadBacon()) {
            $renderer = new ImageRenderer(new RendererStyle(320), new SvgImageBackEnd());

            return (new Writer($renderer))->writeString($content);
        }

        return SimpleQr::svg($content, 320);
    }

    public static function dataUri(string $content): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(self::svg($content));
    }

    private static function loadBacon(): bool
    {
        $autoload = BASE_PATH . '/vendor/autoload.php';

        if (!is_file($autoload)) {
            return false;
        }

        require_once $autoload;

        return class_exists(Writer::class);
    }
}

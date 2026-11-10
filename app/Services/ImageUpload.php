<?php
namespace App\Services;

class ImageUpload
{
    private const MAX_BYTES  = 8388608;
    private const MAX_PIXELS = 16000000;
    private const MAX_SIDE   = 1600;
    private const ALLOWED    = ['image/jpeg', 'image/png', 'image/webp'];

    public static function saveCover(array $file): string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $tmp   = $file['tmp_name'] ?? null;

        if (!is_int($error) || !is_string($tmp)) {
            throw new \InvalidArgumentException('err.image_upload');
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new \InvalidArgumentException('err.image_size');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
            throw new \InvalidArgumentException('err.image_upload');
        }
        if (filesize($tmp) > self::MAX_BYTES) {
            throw new \InvalidArgumentException('err.image_size');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!in_array($mime, self::ALLOWED, true)) {
            throw new \InvalidArgumentException('err.image_type');
        }

        $info = @getimagesize($tmp);
        if ($info === false) {
            throw new \InvalidArgumentException('err.image_type');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw new \InvalidArgumentException('err.image_dimensions');
        }

        if (!function_exists('imagecreatefromstring')) {
            throw new \RuntimeException('GD extension is not enabled.');
        }

        $image = @imagecreatefromstring((string) file_get_contents($tmp));
        if ($image === false) {
            throw new \InvalidArgumentException('err.image_type');
        }

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($tmp);
            $angle = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
            if ($angle !== 0) {
                $image = imagerotate($image, $angle, 0);
            }
        }

        $width  = imagesx($image);
        $height = imagesy($image);
        $scale  = min(1, self::MAX_SIDE / max($width, $height));
        $newW   = max(1, (int) round($width * $scale));
        $newH   = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newW, $newH);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);

        $dir = BASE_PATH . '/public/uploads/covers';
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \RuntimeException('Cannot create upload directory.');
        }

        $name = bin2hex(random_bytes(16)) . '.jpg';
        if (!imagejpeg($canvas, $dir . '/' . $name, 85)) {
            throw new \RuntimeException('Cannot save the image.');
        }

        return $name;
    }

    public static function deleteCover(?string $name): void
    {
        if ($name !== null && preg_match('/^[a-f0-9]{32}\.jpg$/', $name)) {
            $path = BASE_PATH . '/public/uploads/covers/' . $name;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
<?php
namespace App\Services;

use App\Models\Guest;

class GuestImport
{
    public const MAX_BYTES = 1048576;
    public const MAX_ROWS  = 1000;

    private const ALIASES = [
        'family' => 'family', 'familia' => 'family',
        'friends' => 'friends', 'friend' => 'friends', 'marafiki' => 'friends', 'rafiki' => 'friends',
        'colleagues' => 'colleagues', 'colleague' => 'colleagues',
        'wafanyakazi wenzangu' => 'colleagues', 'wafanyakazi' => 'colleagues',
        'church' => 'church', 'kanisa' => 'church',
        'vip' => 'vip',
        'other' => 'other', 'nyingine' => 'other',
    ];

    private const HEADER_NAMES = ['name', 'jina', 'jina kamili', 'full name', 'full_name'];

    public static function readRows(array $file): array
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $tmp   = $file['tmp_name'] ?? null;
        $name  = $file['name'] ?? '';

        if (!is_int($error) || !is_string($tmp) || !is_string($name)) {
            throw new \InvalidArgumentException('err.import_upload');
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new \InvalidArgumentException('err.import_size');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
            throw new \InvalidArgumentException('err.import_upload');
        }
        if (filesize($tmp) > self::MAX_BYTES) {
            throw new \InvalidArgumentException('err.import_size');
        }
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['csv', 'txt'], true)) {
            throw new \InvalidArgumentException('err.import_type');
        }

        $content = file_get_contents($tmp);
        if ($content === false || str_contains($content, "\0")) {
            throw new \InvalidArgumentException('err.import_type');
        }

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        if (trim($content) === '') {
            throw new \InvalidArgumentException('err.import_empty');
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiter = ',';
        $best = substr_count($firstLine, ',');
        foreach ([';', "\t"] as $candidate) {
            $count = substr_count($firstLine, $candidate);
            if ($count > $best) {
                $best = $count;
                $delimiter = $candidate;
            }
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $rows = [];
        $line = 0;

        while (($cols = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $line++;
            if ($cols === [null]) {
                continue;
            }
            $cols = array_map(fn ($value) => trim((string) $value), $cols);
            if (implode('', $cols) === '') {
                continue;
            }
            $rows[] = ['line' => $line, 'cols' => $cols];
            if (count($rows) > self::MAX_ROWS + 1) {
                fclose($stream);
                throw new \InvalidArgumentException('err.import_rows');
            }
        }
        fclose($stream);

        if ($rows && in_array(mb_strtolower($rows[0]['cols'][0]), self::HEADER_NAMES, true)) {
            array_shift($rows);
        }
        if (!$rows) {
            throw new \InvalidArgumentException('err.import_empty');
        }
        if (count($rows) > self::MAX_ROWS) {
            throw new \InvalidArgumentException('err.import_rows');
        }

        return $rows;
    }

    public static function run(int $weddingId, array $rows, array $categories): array
    {
        $lookup = [];
        foreach ($categories as $category) {
            $lookup[$category['code']] = (int) $category['id'];
            if (!empty($category['name'])) {
                $lookup[mb_strtolower($category['name'])] = (int) $category['id'];
            }
        }

        $pdo = db();
        $stmt = $pdo->prepare(
            'SELECT phone FROM guests
             WHERE wedding_id = :wedding AND deleted_at IS NULL AND phone IS NOT NULL'
        );
        $stmt->execute([':wedding' => $weddingId]);
        $seen = array_fill_keys($stmt->fetchAll(\PDO::FETCH_COLUMN), true);

        $valid = [];
        $skipped = [];

        foreach ($rows as $row) {
            $c = $row['cols'];
            $in = [
                'full_name' => $c[0] ?? '',
                'phone'     => $c[1] ?? '',
                'email'     => strtolower($c[2] ?? ''),
                'seats'     => ($c[3] ?? '') !== '' ? $c[3] : '1',
            ];

            [$errors, $data] = GuestValidator::check($in);

            $categoryId = null;
            $rawCategory = mb_strtolower($c[4] ?? '');
            if ($rawCategory !== '') {
                $key = self::ALIASES[$rawCategory] ?? $rawCategory;
                $categoryId = $lookup[$key] ?? $lookup[$rawCategory] ?? null;
                if ($categoryId === null) {
                    $errors[] = t('err.category');
                }
            }

            if (!$errors && $data['phone'] !== null && isset($seen[$data['phone']])) {
                $errors[] = t('import.reason.duplicate');
            }

            if ($errors) {
                $skipped[] = ['line' => $row['line'], 'name' => $in['full_name'], 'reason' => implode(' ', $errors)];
                continue;
            }

            if ($data['phone'] !== null) {
                $seen[$data['phone']] = true;
            }
            $valid[] = $data + ['category_id' => $categoryId];
        }

        if ($valid) {
            $pdo->beginTransaction();
            try {
                foreach ($valid as $data) {
                    Guest::create($weddingId, $data);
                }
                $pdo->commit();
            } catch (\Throwable $ex) {
                $pdo->rollBack();
                throw $ex;
            }
        }

        return ['imported' => count($valid), 'skipped' => $skipped];
    }
}
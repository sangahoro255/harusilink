<?php
namespace App\Helpers;

class CsvExport
{
    public static function cell($value): string
    {
        $text = (string) $value;

        if ($text !== '' && preg_match('/^[=+\-@\t\r]/', $text) && !preg_match('/^\+?\d[\d ]*$/', $text)) {
            return "'" . $text;
        }

        return $text;
    }

    public static function send(string $filename, array $header, array $rows): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');

        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, $header, ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($out, array_map([self::class, 'cell'], array_values($row)), ',', '"', '');
        }

        fclose($out);
        exit;
    }
}
<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\GuestCategory;
use App\Models\Wedding;
use App\Services\AuditLog;
use App\Services\GuestImport;

class GuestImportController
{
    public function show(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        view('guests/import', ['user' => $user, 'wedding' => $wedding, 'errors' => [], 'result' => null]);
    }

    public function template(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="harusilink-guests-template.csv"');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, ['name', 'phone', 'email', 'seats', 'category'], ',', '"', '');
        fputcsv($out, ['Peter John', '0712345678', 'peter@example.com', '2', 'family'], ',', '"', '');
        fputcsv($out, ['Mary Joseph', '0755123456', '', '1', 'friends'], ',', '"', '');
        fclose($out);
        exit;
    }

    public function import(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;
        csrf_verify();

        $errors = [];
        $result = null;
        $file = $_FILES['file'] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = t('err.import_nofile');
        } else {
            try {
                $rows = GuestImport::readRows($file);
                $result = GuestImport::run($weddingId, $rows, GuestCategory::forWedding($weddingId));
                AuditLog::record((int) $user['id'], 'guest.import', 'wedding', $weddingId, [
                    'imported' => $result['imported'],
                    'skipped'  => count($result['skipped']),
                ]);
            } catch (\InvalidArgumentException $ex) {
                $errors[] = t($ex->getMessage());
            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
                $errors[] = t('err.generic');
            }
        }

        view('guests/import', ['user' => $user, 'wedding' => $wedding, 'errors' => $errors, 'result' => $result]);
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
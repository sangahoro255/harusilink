<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Helpers\CsvExport;
use App\Models\Checkin;
use App\Models\Contribution;
use App\Models\ContributionSettings;
use App\Models\Report;
use App\Models\Wedding;
use App\Services\AuditLog;
use App\Services\WeddingStats;

class ReportController
{
    public function index(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user, $wedding] = $ctx;

        $settings = ContributionSettings::get($weddingId);

        view('reports/index', [
            'user'       => $user,
            'wedding'    => $wedding,
            'stats'      => WeddingStats::forWedding($weddingId, (int) $user['id']),
            'checkins'   => Checkin::counts($weddingId),
            'totals'     => Contribution::totals($weddingId),
            'methods'    => Contribution::byMethod($weddingId),
            'categories' => Report::categories($weddingId),
            'target'     => $settings['target'] !== null ? (float) $settings['target'] : null,
        ]);
    }

    public function exportGuests(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user] = $ctx;

        AuditLog::record((int) $user['id'], 'report.export', 'wedding', $weddingId, ['type' => 'guests']);

        CsvExport::send(
            'harusilink-guests.csv',
            ['name', 'phone', 'email', 'seats', 'category', 'rsvp', 'checked_in_at'],
            Report::guestRows($weddingId)
        );
    }

    public function exportContributions(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user] = $ctx;

        AuditLog::record((int) $user['id'], 'report.export', 'wedding', $weddingId, ['type' => 'contributions']);

        CsvExport::send(
            'harusilink-contributions.csv',
            ['name', 'phone', 'amount', 'method', 'reference', 'status', 'date', 'note'],
            Report::contributionRows($weddingId)
        );
    }

    public function exportCheckins(int $weddingId): void
    {
        $ctx = $this->context($weddingId);
        if ($ctx === null) {
            return;
        }
        [$user] = $ctx;

        AuditLog::record((int) $user['id'], 'report.export', 'wedding', $weddingId, ['type' => 'checkins']);

        CsvExport::send(
            'harusilink-checkins.csv',
            ['name', 'people', 'method', 'checked_in_at'],
            Report::checkinRows($weddingId)
        );
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
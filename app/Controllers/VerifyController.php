<?php
namespace App\Controllers;

use App\Helpers\SecureHeaders;
use App\Models\Checkin;
use App\Services\ScannerAccess;

class VerifyController
{
    public function show(string $token): void
    {
        SecureHeaders::scanner();

        $wedding = ScannerAccess::current();

        if ($wedding === null) {
            view('verify/pending');
            return;
        }

        $result = Checkin::evaluate((int) $wedding['id'], $token);

        $justAdmitted = ($_SESSION['verify_admitted'] ?? null) === $token;
        unset($_SESSION['verify_admitted']);

        if ($justAdmitted && $result['state'] === 'already') {
            $result['state'] = 'admitted';
        }

        view('verify/result', [
            'wedding'  => $wedding,
            'result'   => $result,
            'admitUrl' => url('/verify/' . $token . '/admit'),
            'manual'   => false,
        ]);
    }

    public function admit(string $token): void
    {
        SecureHeaders::scanner();

        $wedding = ScannerAccess::current();

        if ($wedding === null) {
            view('verify/pending');
            return;
        }

        csrf_verify();

        try {
            $result = Checkin::admit((int) $wedding['id'], $token, post('override') === '1');

            if ($result['state'] === 'admitted') {
                $_SESSION['verify_admitted'] = $token;
            }
        } catch (\Throwable $ex) {
            error_log($ex->getMessage());
        }

        redirect('/verify/' . $token);
    }
}
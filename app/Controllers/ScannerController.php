<?php
namespace App\Controllers;

use App\Helpers\SecureHeaders;
use App\Models\Checkin;
use App\Services\ScannerAccess;

class ScannerController
{
    public function show(string $slug): void
    {
        $this->headers();
        $wedding = $this->authorized($slug);

        if ($wedding === null) {
            view('scan/index', ['authed' => false, 'slug' => $slug, 'error' => null]);
            return;
        }

        $flash = $_SESSION['scan_flash'] ?? null;
        unset($_SESSION['scan_flash']);

        view('scan/index', [
            'authed'  => true,
            'slug'    => $slug,
            'wedding' => $wedding,
            'counts'  => Checkin::counts((int) $wedding['id']),
            'recent'  => Checkin::recent((int) $wedding['id'], 5),
            'flash'   => $flash,
            'query'   => '',
            'results' => null,
        ]);
    }

    public function login(string $slug): void
    {
        $this->headers();
        csrf_verify();

        $result = ScannerAccess::attempt($slug, mb_substr(post('pin'), 0, 20));

        if ($result === 'ok') {
            redirect('/scan/' . $slug);
        }

        if ($result === 'blocked') {
            http_response_code(429);
        }

        view('scan/index', [
            'authed' => false,
            'slug'   => $slug,
            'error'  => $result === 'blocked' ? 'scan.err.blocked' : 'scan.err.pin',
        ]);
    }

    public function lookup(string $slug): void
    {
        $this->headers();

        if ($this->authorized($slug) === null) {
            redirect('/scan/' . $slug);
        }

        csrf_verify();

        $code = strtolower(mb_substr(post('code'), 0, 300));

        if (preg_match('/[a-f0-9]{32}/', $code, $match) === 1) {
            redirect('/verify/' . $match[0]);
        }

        $_SESSION['scan_flash'] = 'scan.err.code';
        redirect('/scan/' . $slug);
    }

    public function search(string $slug): void
    {
        $this->headers();
        $wedding = $this->authorized($slug);

        if ($wedding === null) {
            redirect('/scan/' . $slug);
        }

        csrf_verify();

        $query = trim(mb_substr(post('q'), 0, 60));

        if (mb_strlen($query) < 2) {
            $_SESSION['scan_flash'] = 'scan.err.search_short';
            redirect('/scan/' . $slug);
        }

        view('scan/index', [
            'authed'  => true,
            'slug'    => $slug,
            'wedding' => $wedding,
            'counts'  => Checkin::counts((int) $wedding['id']),
            'recent'  => Checkin::recent((int) $wedding['id'], 5),
            'flash'   => null,
            'query'   => $query,
            'results' => Checkin::search((int) $wedding['id'], $query, 10),
        ]);
    }

    public function guest(string $slug, string $guest): void
    {
        SecureHeaders::scanner();
        $wedding = $this->authorized($slug);

        if ($wedding === null) {
            redirect('/scan/' . $slug);
        }

        $guestId = (int) $guest;
        $result  = Checkin::evaluateGuest((int) $wedding['id'], $guestId);

        $justAdmitted = ($_SESSION['scan_admitted'] ?? null) === $guestId;
        unset($_SESSION['scan_admitted']);

        if ($justAdmitted && $result['state'] === 'already') {
            $result['state'] = 'admitted';
        }

        view('verify/result', [
            'wedding'  => $wedding,
            'result'   => $result,
            'admitUrl' => url('/scan/' . $slug . '/guest/' . $guestId . '/admit'),
            'manual'   => true,
        ]);
    }

    public function admitGuest(string $slug, string $guest): void
    {
        SecureHeaders::scanner();
        $wedding = $this->authorized($slug);

        if ($wedding === null) {
            redirect('/scan/' . $slug);
        }

        csrf_verify();

        $guestId = (int) $guest;

        try {
            $result = Checkin::admitGuest((int) $wedding['id'], $guestId, post('override') === '1');

            if ($result['state'] === 'admitted') {
                $_SESSION['scan_admitted'] = $guestId;
            }
        } catch (\Throwable $ex) {
            error_log($ex->getMessage());
        }

        redirect('/scan/' . $slug . '/guest/' . $guestId);
    }

    public function logout(string $slug): void
    {
        csrf_verify();
        ScannerAccess::logout();
        redirect('/scan/' . $slug);
    }

    private function authorized(string $slug): ?array
    {
        $wedding = ScannerAccess::current();

        return ($wedding !== null && $wedding['slug'] === $slug) ? $wedding : null;
    }

    private function headers(): void
    {
        header('X-Robots-Tag: noindex, nofollow');
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: private, no-store');
    }
}
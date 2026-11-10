<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Wedding;

class DashboardController
{
    public function index(): void
    {
        $user = Auth::requireLogin();

        if ($user['role'] === 'super_admin') {
            redirect('/admin');
        }

        view('admin/dashboard', [
            'user'     => $user,
            'weddings' => Wedding::forOwner((int) $user['id']),
        ]);
    }
}
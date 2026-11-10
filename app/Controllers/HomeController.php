<?php
namespace App\Controllers;

class HomeController
{
    public function index(): void
    {
        redirect('/login');
    }

    public function about(): void
    {
        echo '<h1>Kuhusu HarusiLink</h1>';
    }
}
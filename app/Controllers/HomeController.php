<?php
namespace App\Controllers;

class HomeController
{
    public function index(): void
    {
        view('home', ['title' => 'Karibu HarusiLink']);
    }

    public function about(): void
    {
        echo '<h1>Kuhusu HarusiLink</h1>';
    }
}
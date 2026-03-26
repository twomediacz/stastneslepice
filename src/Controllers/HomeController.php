<?php

namespace App\Controllers;

use App\Core\View;

class HomeController
{
    public function index(): void
    {
        View::render('home', [
            'title' => 'Šťastné slepice – Doloplazy',
        ]);
    }
}

<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Chicken;

class ChickensController
{
    public function index(): void
    {
        Auth::requireAuth();

        $chickens = Chicken::getAll();
        $counts = Chicken::getCount();

        View::render('chickens', [
            'title' => 'Slepice – Evidence',
            'chickens' => $chickens,
            'counts' => $counts,
        ]);
    }
}

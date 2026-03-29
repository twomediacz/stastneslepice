<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Chicken;

class ChickensController
{
    public function index(): void
    {
        $chickens = Chicken::getAll();
        $counts = Chicken::getCount();

        View::render('chickens', [
            'title' => 'Chov slepic – Doloplazy – Slepice',
            'chickens' => $chickens,
            'counts' => $counts,
        ]);
    }
}

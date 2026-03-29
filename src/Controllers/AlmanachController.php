<?php

namespace App\Controllers;

use App\Core\View;

class AlmanachController
{
    public function index(): void
    {
        $sections = require __DIR__ . '/../../data/almanach.php';

        View::render('almanach', [
            'title' => 'Chov slepic – Doloplazy – Almanach',
            'sections' => $sections,
        ]);
    }

    public function pokrocily(): void
    {
        $data = require __DIR__ . '/../../data/almanach_pokrocily.php';

        View::render('almanach_pokrocily', [
            'title' => 'Chov slepic – Doloplazy – Almanach',
            'subtitle' => $data['subtitle'],
            'footer' => $data['footer'],
            'sections' => $data['sections'],
        ]);
    }
}

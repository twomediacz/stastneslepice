<?php

namespace App\Controllers;

use App\Core\View;
use App\Models\Note;

class DiaryController
{
    public function index(): void
    {
        View::render('diary', [
            'title' => 'Chov slepic – Doloplazy – Deník',
            'notes' => Note::getAllOrdered(),
        ]);
    }
}

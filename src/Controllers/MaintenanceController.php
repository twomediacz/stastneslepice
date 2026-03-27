<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\BeddingChange;
use App\Models\Repair;
use App\Models\Setting;

class MaintenanceController
{
    public function index(): void
    {
        Auth::requireAuth();

        $beddingChanges = BeddingChange::getAll();
        $repairs = Repair::getAll();
        $intervalDays = (int) (Setting::get('bedding_interval_days') ?? 14);
        $lastBedding = BeddingChange::getLatest();

        $lastBeddingDate = $lastBedding ? $lastBedding['changed_at'] : null;
        $nextBeddingDate = $lastBeddingDate
            ? date('Y-m-d', strtotime($lastBeddingDate . " +{$intervalDays} days"))
            : null;

        View::render('maintenance', [
            'title' => 'Údržba',
            'beddingChanges' => $beddingChanges,
            'repairs' => $repairs,
            'intervalDays' => $intervalDays,
            'lastBeddingDate' => $lastBeddingDate,
            'nextBeddingDate' => $nextBeddingDate,
        ]);
    }
}

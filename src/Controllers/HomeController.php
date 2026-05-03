<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Setting;
use App\Models\EggRecord;
use App\Models\ClimateRecord;
use App\Models\Chicken;
use App\Models\Note;
use App\Models\Photo;
use App\Models\BeddingChange;
use App\Models\TextSnippet;
use App\Models\VisitStat;

class HomeController
{
    public function index(): void
    {
        VisitStat::recordHomeVisit();

        $settings = Setting::getAll();
        $todayEggs = EggRecord::getByDate(date('Y-m-d'));
        $recentEggs = EggRecord::getRecent(14);
        $latestCoop = ClimateRecord::getLatest('coop');
        $latestOutdoor = ClimateRecord::getLatest('outdoor');
        $totalEggs = EggRecord::getTotalEggs();
        $dailyAvg = EggRecord::getDailyAverage();
        $chickenCount = Chicken::getCount();
        $notes = Note::getAllOrdered();
        $photos = Photo::getAll(12);
        $visitStats = VisitStat::getHomeSummary();

        $dailyJoke = TextSnippet::getDaily('joke');

        $intervalDays = (int) ($settings['bedding_interval_days'] ?? 14);
        $lastBedding = BeddingChange::getLatest();
        $lastBeddingDate = $lastBedding ? $lastBedding['changed_at'] : null;
        $nextBeddingDate = $lastBeddingDate
            ? date('Y-m-d', strtotime($lastBeddingDate . " +{$intervalDays} days"))
            : null;

        View::render('home', [
            'title' => 'Chov slepic – Doloplazy – Nástěnka',
            'settings' => $settings,
            'todayEggs' => $todayEggs,
            'recentEggs' => $recentEggs,
            'climateCoop' => $latestCoop,
            'climateOutdoor' => $latestOutdoor,
            'totalEggs' => $totalEggs,
            'dailyAvg' => $dailyAvg,
            'chickenCount' => $chickenCount,
            'notes' => $notes,
            'photos' => $photos,
            'visitStats' => $visitStats,
            'lastBeddingDate' => $lastBeddingDate,
            'nextBeddingDate' => $nextBeddingDate,
            'dailyJoke' => $dailyJoke,
        ]);
    }
}

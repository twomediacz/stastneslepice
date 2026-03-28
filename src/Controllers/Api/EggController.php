<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\EggRecord;

class EggController extends Controller
{
    public function index(): void
    {
        $group = $_GET['group'] ?? null;

        if ($group === 'month') {
            $months = (int) ($_GET['months'] ?? 12);
            $records = EggRecord::getMonthlyAggregated($months);
            $this->json([
                'records' => $records,
                'grouped' => 'month',
                'total' => EggRecord::getTotalEggs(),
                'average' => EggRecord::getDailyAverage(),
            ]);
            return;
        }

        $days = (int) ($_GET['days'] ?? 14);
        $records = EggRecord::getRecent($days);
        $this->json([
            'records' => $records,
            'total' => EggRecord::getTotalEggs(),
            'average' => EggRecord::getDailyAverage(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $date = $data['date'] ?? date('Y-m-d');
        $count = (int) ($data['egg_count'] ?? 0);
        $note = trim($data['note'] ?? '') ?: null;

        if ($count < 0) {
            $this->jsonError('Počet vajec nemůže být záporný.');
        }

        EggRecord::upsert($date, $count, $note);

        $this->json([
            'success' => true,
            'record' => EggRecord::getByDate($date),
            'total' => EggRecord::getTotalEggs(),
            'average' => EggRecord::getDailyAverage(),
        ]);
    }

    public function destroy(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID záznamu.');
        }

        EggRecord::delete($id);

        $this->json([
            'success' => true,
            'total' => EggRecord::getTotalEggs(),
            'average' => EggRecord::getDailyAverage(),
        ]);
    }
}

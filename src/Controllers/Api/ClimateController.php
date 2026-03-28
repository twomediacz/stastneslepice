<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\ClimateRecord;
use App\Models\Setting;

class ClimateController extends Controller
{
    public function latest(): void
    {
        $this->json([
            'coop' => ClimateRecord::getLatest('coop'),
            'outdoor' => ClimateRecord::getLatest('outdoor'),
        ]);
    }

    public function history(): void
    {
        $group = $_GET['group'] ?? null;

        if ($group === 'day') {
            $days = (int) ($_GET['days'] ?? 7);
            $this->json([
                'coop' => ClimateRecord::getDailyMinMax('coop', $days),
                'outdoor' => ClimateRecord::getDailyMinMax('outdoor', $days),
                'grouped' => 'day',
            ]);
            return;
        }

        if ($group === 'month') {
            $months = (int) ($_GET['months'] ?? 12);
            $this->json([
                'coop' => ClimateRecord::getMonthlyMinMax('coop', $months),
                'outdoor' => ClimateRecord::getMonthlyMinMax('outdoor', $months),
                'grouped' => 'month',
            ]);
            return;
        }

        $hours = (int) ($_GET['hours'] ?? 24);
        $this->json([
            'coop' => ClimateRecord::getHistory('coop', $hours),
            'outdoor' => ClimateRecord::getHistory('outdoor', $hours),
        ]);
    }

    public function store(): void
    {
        // API endpoint pro manuální zadání i IoT senzor
        // IoT senzor se autentizuje přes api_token (musí odpovídat hodnotě v nastavení)
        $data = $this->getPostData();
        $apiToken = $data['api_token'] ?? null;

        if ($apiToken) {
            $validToken = Setting::get('climate_api_token');
            if (!$validToken || !hash_equals($validToken, $apiToken)) {
                $this->jsonError('Neplatný API token.', 401);
            }
        } else {
            Auth::requireAuthApi();
        }

        $location = $data['location'] ?? '';
        $temperature = isset($data['temperature']) ? (float) $data['temperature'] : null;
        $humidity = isset($data['humidity']) ? (float) $data['humidity'] : null;

        if (!in_array($location, ['coop', 'outdoor'])) {
            $this->jsonError('Neplatná lokace. Použijte "coop" nebo "outdoor".');
        }
        if ($temperature === null && $humidity === null) {
            $this->jsonError('Zadejte alespoň teplotu nebo vlhkost.');
        }

        $id = ClimateRecord::add($location, $temperature ?? 0, $humidity ?? 0);
        $this->json(['success' => true, 'id' => $id]);
    }
}

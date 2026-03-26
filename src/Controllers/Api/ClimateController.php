<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\ClimateRecord;

class ClimateController extends Controller
{
    public function latest(): void
    {
        Auth::requireAuthApi();
        $this->json([
            'coop' => ClimateRecord::getLatest('coop'),
            'outdoor' => ClimateRecord::getLatest('outdoor'),
        ]);
    }

    public function history(): void
    {
        Auth::requireAuthApi();
        $hours = (int) ($_GET['hours'] ?? 24);
        $this->json([
            'coop' => ClimateRecord::getHistory('coop', $hours),
            'outdoor' => ClimateRecord::getHistory('outdoor', $hours),
        ]);
    }

    public function store(): void
    {
        // API endpoint pro manuální zadání i budoucí IoT senzor
        // IoT senzor se může autentizovat přes api_token v POST datech
        $data = $this->getPostData();
        $apiToken = $data['api_token'] ?? null;

        if (!$apiToken) {
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

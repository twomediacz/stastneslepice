<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Setting;

class WeatherController extends Controller
{
    private const CACHE_FILE = __DIR__ . '/../../../storage/cache/weather.json';
    private const CACHE_TTL = 3600; // 1 hodina

    // Doloplazy, CZ – souřadnice
    private const LAT = 49.5883;
    private const LON = 17.2225;

    public function forecast(): void
    {
        Auth::requireAuthApi();

        // Zkusit cache
        if (file_exists(self::CACHE_FILE) && (time() - filemtime(self::CACHE_FILE)) < self::CACHE_TTL) {
            $cached = json_decode(file_get_contents(self::CACHE_FILE), true);
            if ($cached) {
                $this->json($cached);
            }
        }

        $apiKey = Setting::get('weather_api_key');
        if (!$apiKey) {
            $this->jsonError('API klíč pro počasí není nastaven.', 503);
        }

        $url = sprintf(
            'https://api.openweathermap.org/data/2.5/forecast?lat=%s&lon=%s&appid=%s&units=metric&lang=cs&cnt=24',
            self::LAT,
            self::LON,
            $apiKey
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            $this->jsonError('Nepodařilo se získat předpověď počasí.', 502);
        }

        $raw = json_decode($response, true);
        if (!$raw || !isset($raw['list'])) {
            $this->jsonError('Neplatná odpověď z API počasí.', 502);
        }

        // Zpracovat do 3denní předpovědi (polední hodnoty)
        $forecast = $this->processForecast($raw['list']);

        $result = ['forecast' => $forecast];

        // Uložit cache
        $cacheDir = dirname(self::CACHE_FILE);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents(self::CACHE_FILE, json_encode($result, JSON_UNESCAPED_UNICODE));

        $this->json($result);
    }

    private function processForecast(array $list): array
    {
        $days = [];
        foreach ($list as $item) {
            $date = date('Y-m-d', $item['dt']);
            $hour = (int) date('H', $item['dt']);
            $temp = round($item['main']['temp']);

            if (!isset($days[$date])) {
                $days[$date] = [
                    'date' => $date,
                    'icon_hour' => $hour,
                    'temp_day' => $temp,
                    'temp_night' => $temp,
                    'description' => $item['weather'][0]['description'] ?? '',
                    'icon' => $item['weather'][0]['icon'] ?? '01d',
                ];
            }

            // Denní teplota = maximum přes den (9–18h)
            if ($hour >= 9 && $hour <= 18 && $temp > $days[$date]['temp_day']) {
                $days[$date]['temp_day'] = $temp;
            }

            // Noční teplota = minimum přes noc (0–6h, 21–24h)
            if (($hour <= 6 || $hour >= 21) && $temp < $days[$date]['temp_night']) {
                $days[$date]['temp_night'] = $temp;
            }

            // Ikona z poledne
            if (abs($hour - 12) < abs($days[$date]['icon_hour'] - 12)) {
                $days[$date]['icon_hour'] = $hour;
                $days[$date]['description'] = $item['weather'][0]['description'] ?? '';
                $days[$date]['icon'] = $item['weather'][0]['icon'] ?? '01d';
            }
        }

        // Odstranit interní pole
        foreach ($days as &$day) {
            unset($day['icon_hour']);
        }

        $result = array_values($days);
        return array_slice($result, 0, 3);
    }
}

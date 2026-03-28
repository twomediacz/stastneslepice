<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Setting;

class SettingController extends Controller
{
    private const SENSITIVE_KEYS = ['weather_api_key', 'climate_api_token'];

    public function index(): void
    {
        $settings = Setting::getAll();

        // Skrýt citlivá nastavení pro nepřihlášené
        if (!Auth::check()) {
            foreach (self::SENSITIVE_KEYS as $key) {
                unset($settings[$key]);
            }
        }

        $this->json(['settings' => $settings]);
    }

    public function update(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $key = $data['setting_key'] ?? '';
        $value = $data['setting_value'] ?? '';

        if ($key === '') {
            $this->jsonError('Klíč nastavení je povinný.');
        }

        Setting::set($key, $value);
        $this->json([
            'success' => true,
            'setting_key' => $key,
            'setting_value' => $value,
        ]);
    }
}

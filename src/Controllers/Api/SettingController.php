<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index(): void
    {
        Auth::requireAuthApi();
        $this->json(['settings' => Setting::getAll()]);
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

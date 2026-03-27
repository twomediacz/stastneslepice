<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Setting;

class LiveController
{
    public function index(): void
    {
        Auth::requireAuth();

        $settings = Setting::getAll();

        $streams = [];
        $urls = [
            ['key' => 'youtube_livestream_url', 'label' => 'Kurník', 'color' => 'brown'],
            ['key' => 'youtube_livestream_url_2', 'label' => 'Výběh', 'color' => 'green'],
        ];

        foreach ($urls as $stream) {
            $url = $settings[$stream['key']] ?? '';
            $embedUrl = '';
            if ($url && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|live\/))([a-zA-Z0-9_-]+)/', $url, $m)) {
                $embedUrl = 'https://www.youtube.com/embed/' . $m[1] . '?autoplay=1&mute=1';
            }
            $streams[] = [
                'label' => $stream['label'],
                'embedUrl' => $embedUrl,
                'color' => $stream['color'],
            ];
        }

        View::render('live', [
            'title' => 'Živě – Kurník',
            'settings' => $settings,
            'streams' => $streams,
        ]);
    }
}

<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\EventWod;
use App\Models\EventWodVideo;

class EventWodVideoController extends Controller
{
    public function index(): void
    {
        $wodId = (int) ($_GET['wod_id'] ?? 0);
        if ($wodId <= 0) {
            $this->jsonError('Neplatné ID WODu.');
        }

        $this->json(['videos' => EventWodVideo::findByWod($wodId)]);
    }

    public function store(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $wodId = (int) ($data['wod_id'] ?? 0);
        if ($wodId <= 0 || !EventWod::findById($wodId)) {
            $this->jsonError('WOD nebyl nalezen.');
        }

        $youtubeUrl = trim($data['youtube_url'] ?? '');
        if ($youtubeUrl === '') {
            $this->jsonError('YouTube URL je povinná.');
        }

        if (!filter_var($youtubeUrl, FILTER_VALIDATE_URL)) {
            $this->jsonError('Neplatná URL adresa.');
        }

        $sortOrder = EventWodVideo::getMaxSortOrder($wodId) + 1;

        $id = EventWodVideo::insert([
            'wod_id' => $wodId,
            'youtube_url' => $youtubeUrl,
            'title' => trim($data['title'] ?? '') ?: null,
            'sort_order' => $sortOrder,
        ]);

        $this->json([
            'success' => true,
            'video' => EventWodVideo::findById($id),
        ]);
    }

    public function update(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        $video = EventWodVideo::findById($id);
        if (!$video) {
            $this->jsonError('Video nebylo nalezeno.', 404);
        }

        $youtubeUrl = trim($data['youtube_url'] ?? '');
        if ($youtubeUrl === '') {
            $this->jsonError('YouTube URL je povinná.');
        }

        if (!filter_var($youtubeUrl, FILTER_VALIDATE_URL)) {
            $this->jsonError('Neplatná URL adresa.');
        }

        EventWodVideo::update($id, [
            'youtube_url' => $youtubeUrl,
            'title' => trim($data['title'] ?? '') ?: null,
        ]);

        $this->json([
            'success' => true,
            'video' => EventWodVideo::findById($id),
        ]);
    }

    public function delete(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        if (!EventWodVideo::findById($id)) {
            $this->jsonError('Video nebylo nalezeno.', 404);
        }

        EventWodVideo::delete($id);
        $this->json(['success' => true]);
    }

    public function reorder(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $ids = $data['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            $this->jsonError('Pole ID je povinné.');
        }

        EventWodVideo::reorder($ids);
        $this->json(['success' => true]);
    }
}

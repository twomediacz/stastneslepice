<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Event;
use App\Models\EventWod;
use App\Models\EventWodImage;

class EventWodController extends Controller
{
    public function index(): void
    {
        $eventId = (int) ($_GET['event_id'] ?? 0);
        if ($eventId <= 0) {
            $this->jsonError('Neplatné ID akce.');
        }

        $this->json(['wods' => EventWod::findByEvent($eventId)]);
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        $wod = EventWod::findByIdWithMedia($id);
        if (!$wod) {
            $this->jsonError('WOD nebyl nalezen.', 404);
        }

        $this->json(['wod' => $wod]);
    }

    public function store(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $eventId = (int) ($data['event_id'] ?? 0);
        if ($eventId <= 0 || !Event::findById($eventId)) {
            $this->jsonError('Akce nebyla nalezena.');
        }

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            $this->jsonError('Název WODu je povinný.');
        }

        $sortOrder = EventWod::getMaxSortOrder($eventId) + 1;

        $id = EventWod::insert([
            'event_id' => $eventId,
            'name' => $name,
            'description' => trim($data['description'] ?? '') ?: null,
            'sort_order' => $sortOrder,
        ]);

        $this->json([
            'success' => true,
            'wod' => EventWod::findById($id),
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

        $wod = EventWod::findById($id);
        if (!$wod) {
            $this->jsonError('WOD nebyl nalezen.', 404);
        }

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            $this->jsonError('Název WODu je povinný.');
        }

        EventWod::update($id, [
            'name' => $name,
            'description' => trim($data['description'] ?? '') ?: null,
        ]);

        $this->json([
            'success' => true,
            'wod' => EventWod::findById($id),
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

        $wod = EventWod::findById($id);
        if (!$wod) {
            $this->jsonError('WOD nebyl nalezen.', 404);
        }

        // Smazat soubory obrázků z disku před cascade delete
        EventWodImage::deleteByWod($id);

        EventWod::delete($id);
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

        EventWod::reorder($ids);
        $this->json(['success' => true]);
    }
}

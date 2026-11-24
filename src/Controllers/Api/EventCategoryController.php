<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Event;
use App\Models\EventCategory;

class EventCategoryController extends Controller
{
    public function index(): void
    {
        $eventId = (int) ($_GET['event_id'] ?? 0);
        if ($eventId <= 0) {
            $this->jsonError('Neplatné ID akce.');
        }

        $this->json(['categories' => EventCategory::findByEvent($eventId)]);
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
            $this->jsonError('Název kategorie je povinný.');
        }

        $sortOrder = EventCategory::getMaxSortOrder($eventId) + 1;

        $id = EventCategory::insert([
            'event_id' => $eventId,
            'name' => $name,
            'description' => trim($data['description'] ?? '') ?: null,
            'sort_order' => $sortOrder,
        ]);

        $this->json([
            'success' => true,
            'category' => EventCategory::findById($id),
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

        $category = EventCategory::findById($id);
        if (!$category) {
            $this->jsonError('Kategorie nebyla nalezena.', 404);
        }

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            $this->jsonError('Název kategorie je povinný.');
        }

        EventCategory::update($id, [
            'name' => $name,
            'description' => trim($data['description'] ?? '') ?: null,
        ]);

        $this->json([
            'success' => true,
            'category' => EventCategory::findById($id),
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

        if (!EventCategory::findById($id)) {
            $this->jsonError('Kategorie nebyla nalezena.', 404);
        }

        EventCategory::delete($id);
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

        EventCategory::reorder($ids);
        $this->json(['success' => true]);
    }
}

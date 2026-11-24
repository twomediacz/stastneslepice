<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Event;
use App\Models\EventWod;
use App\Models\EventWodImage;

class EventController extends Controller
{
    public function index(): void
    {
        $type = $_GET['type'] ?? null;
        $status = $_GET['status'] ?? null;

        if ($type && !in_array($type, Event::validTypes())) {
            $type = null;
        }
        if ($status && !in_array($status, Event::validStatuses())) {
            $status = null;
        }

        $this->json(['events' => Event::getAll($type, $status)]);
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        $event = Event::findByIdWithDetails($id);
        if (!$event) {
            $this->jsonError('Akce nebyla nalezena.', 404);
        }

        $this->json(['event' => $event]);
    }

    public function store(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $title = trim($data['title'] ?? '');
        if ($title === '') {
            $this->jsonError('Název akce je povinný.');
        }

        $type = $data['type'] ?? 'race';
        if (!in_array($type, Event::validTypes())) {
            $this->jsonError('Neplatný typ akce.');
        }

        $status = $data['status'] ?? 'draft';
        if (!in_array($status, Event::validStatuses())) {
            $this->jsonError('Neplatný stav akce.');
        }

        $insert = [
            'type' => $type,
            'status' => $status,
            'title' => $title,
            'subtitle' => trim($data['subtitle'] ?? '') ?: null,
            'description' => trim($data['description'] ?? '') ?: null,
            'venue' => trim($data['venue'] ?? '') ?: null,
            'capacity' => !empty($data['capacity']) ? (int) $data['capacity'] : null,
            'registration_start' => $data['registration_start'] ?? null,
            'registration_end' => $data['registration_end'] ?? null,
            'event_start' => $data['event_start'] ?? null,
            'event_end' => $data['event_end'] ?? null,
        ];

        $id = Event::insert($insert);

        $this->json([
            'success' => true,
            'event' => Event::findById($id),
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

        $event = Event::findById($id);
        if (!$event) {
            $this->jsonError('Akce nebyla nalezena.', 404);
        }

        $title = trim($data['title'] ?? '');
        if ($title === '') {
            $this->jsonError('Název akce je povinný.');
        }

        $type = $data['type'] ?? $event['type'];
        if (!in_array($type, Event::validTypes())) {
            $this->jsonError('Neplatný typ akce.');
        }

        $status = $data['status'] ?? $event['status'];
        if (!in_array($status, Event::validStatuses())) {
            $this->jsonError('Neplatný stav akce.');
        }

        $updateData = [
            'type' => $type,
            'status' => $status,
            'title' => $title,
            'subtitle' => trim($data['subtitle'] ?? '') ?: null,
            'description' => trim($data['description'] ?? '') ?: null,
            'venue' => trim($data['venue'] ?? '') ?: null,
            'capacity' => !empty($data['capacity']) ? (int) $data['capacity'] : null,
            'registration_start' => $data['registration_start'] ?? null,
            'registration_end' => $data['registration_end'] ?? null,
            'event_start' => $data['event_start'] ?? null,
            'event_end' => $data['event_end'] ?? null,
        ];

        Event::update($id, $updateData);

        $this->json([
            'success' => true,
            'event' => Event::findById($id),
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

        $event = Event::findById($id);
        if (!$event) {
            $this->jsonError('Akce nebyla nalezena.', 404);
        }

        // Smazat soubory obrázků z disku před cascade delete
        $wods = EventWod::findByEvent($id);
        foreach ($wods as $wod) {
            EventWodImage::deleteByWod($wod['id']);
        }

        Event::delete($id);

        $this->json(['success' => true]);
    }

    public function updateStatus(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        $event = Event::findById($id);
        if (!$event) {
            $this->jsonError('Akce nebyla nalezena.', 404);
        }

        $status = $data['status'] ?? '';
        if (!in_array($status, Event::validStatuses())) {
            $this->jsonError('Neplatný stav akce.');
        }

        Event::update($id, ['status' => $status]);

        $this->json([
            'success' => true,
            'event' => Event::findById($id),
        ]);
    }
}

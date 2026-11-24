<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Event;
use App\Models\EventInfobox;

class EventInfoboxController extends Controller
{
    public function index(): void
    {
        $eventId = (int) ($_GET['event_id'] ?? 0);
        if ($eventId <= 0) {
            $this->jsonError('Neplatné ID akce.');
        }

        $this->json(['infoboxes' => EventInfobox::findByEvent($eventId)]);
    }

    public function store(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $eventId = (int) ($data['event_id'] ?? 0);
        if ($eventId <= 0 || !Event::findById($eventId)) {
            $this->jsonError('Akce nebyla nalezena.');
        }

        $title = trim($data['title'] ?? '');
        if ($title === '') {
            $this->jsonError('Titulek infoboxu je povinný.');
        }

        $content = trim($data['content'] ?? '');
        if ($content === '') {
            $this->jsonError('Obsah infoboxu je povinný.');
        }

        $sortOrder = EventInfobox::getMaxSortOrder($eventId) + 1;

        $id = EventInfobox::insert([
            'event_id' => $eventId,
            'title' => $title,
            'content' => $content,
            'sort_order' => $sortOrder,
        ]);

        $this->json([
            'success' => true,
            'infobox' => EventInfobox::findById($id),
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

        $infobox = EventInfobox::findById($id);
        if (!$infobox) {
            $this->jsonError('Infobox nebyl nalezen.', 404);
        }

        $title = trim($data['title'] ?? '');
        if ($title === '') {
            $this->jsonError('Titulek infoboxu je povinný.');
        }

        $content = trim($data['content'] ?? '');
        if ($content === '') {
            $this->jsonError('Obsah infoboxu je povinný.');
        }

        EventInfobox::update($id, [
            'title' => $title,
            'content' => $content,
        ]);

        $this->json([
            'success' => true,
            'infobox' => EventInfobox::findById($id),
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

        if (!EventInfobox::findById($id)) {
            $this->jsonError('Infobox nebyl nalezen.', 404);
        }

        EventInfobox::delete($id);
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

        EventInfobox::reorder($ids);
        $this->json(['success' => true]);
    }
}

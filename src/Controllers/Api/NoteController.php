<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Note;
use App\Services\TextToSpeechService;
use RuntimeException;

class NoteController extends Controller
{
    public function index(): void
    {
        $limit = (int) ($_GET['limit'] ?? 20);
        $this->json(['notes' => Note::getRecent($limit)]);
    }

    public function store(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $content = trim($data['content'] ?? '');
        $date = $data['note_date'] ?? date('Y-m-d');

        if ($content === '') {
            $this->jsonError('Poznámka nemůže být prázdná.');
        }

        $id = Note::add($date, $content);
        $this->json([
            'success' => true,
            'note' => Note::findById($id),
        ]);
    }

    public function speech(): void
    {
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID poznámky.');
        }

        $note = Note::findById($id);
        if (!$note) {
            $this->jsonError('Poznámka nebyla nalezena.', 404);
        }

        $content = trim((string) ($note['content'] ?? ''));
        if ($content === '') {
            $this->jsonError('Poznámka je prázdná.');
        }

        try {
            $speech = (new TextToSpeechService())->synthesizeNote($id, $content);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 503);
        }

        $this->json([
            'success' => true,
            'mime_type' => $speech['mime_type'],
            'audio_base64' => base64_encode($speech['binary']),
        ]);
    }

    public function update(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $id = (int) ($data['id'] ?? 0);
        $content = trim($data['content'] ?? '');

        if ($id <= 0) {
            $this->jsonError('Neplatné ID poznámky.');
        }
        if ($content === '') {
            $this->jsonError('Poznámka nemůže být prázdná.');
        }

        $updateData = ['content' => $content];
        $noteDate = trim($data['note_date'] ?? '');
        if ($noteDate !== '') {
            $updateData['note_date'] = $noteDate;
        }
        Note::update($id, $updateData);
        $this->json([
            'success' => true,
            'note' => Note::findById($id),
        ]);
    }

    public function destroy(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID poznámky.');
        }

        Note::deleteNote($id);
        $this->json(['success' => true]);
    }
}

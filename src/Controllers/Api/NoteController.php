<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Note;

class NoteController extends Controller
{
    public function index(): void
    {
        Auth::requireAuthApi();
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

        Note::update($id, ['content' => $content]);
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

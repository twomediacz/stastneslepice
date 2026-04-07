<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\TextSnippet;

class TextSnippetController extends Controller
{
    public function random(): void
    {
        $type = $_GET['type'] ?? 'joke';
        $snippet = $type === 'joke'
            ? TextSnippet::getDaily($type)
            : TextSnippet::getRandom($type);
        $this->json(['snippet' => $snippet]);
    }

    public function index(): void
    {
        $type = $_GET['type'] ?? 'joke';
        $limit = (int) ($_GET['limit'] ?? 100);
        $this->json(['snippets' => TextSnippet::getAllByType($type, $limit)]);
    }

    public function store(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $type = trim($data['type'] ?? 'joke');
        $content = trim($data['content'] ?? '');

        if ($content === '') {
            $this->jsonError('Text nemůže být prázdný.');
        }

        $id = TextSnippet::add($type, $content);
        $this->json([
            'success' => true,
            'snippet' => TextSnippet::findById($id),
        ]);
    }

    public function update(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $id = (int) ($data['id'] ?? 0);
        $content = trim($data['content'] ?? '');

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }
        if ($content === '') {
            $this->jsonError('Text nemůže být prázdný.');
        }

        $updateData = ['content' => $content];
        $type = trim($data['type'] ?? '');
        if ($type !== '') {
            $updateData['type'] = $type;
        }

        TextSnippet::update($id, $updateData);
        $this->json([
            'success' => true,
            'snippet' => TextSnippet::findById($id),
        ]);
    }

    public function destroy(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        TextSnippet::delete($id);
        $this->json(['success' => true]);
    }
}

<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Chicken;

class ChickenController extends Controller
{
    public function index(): void
    {
        Auth::requireAuthApi();
        $this->json([
            'chickens' => Chicken::getAll(),
            'counts' => Chicken::getCount(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuthApi();

        $data = $this->getPostData();
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            $this->jsonError('Jméno slepice je povinné.');
        }

        $record = [
            'name' => $name,
            'breed' => trim($data['breed'] ?? '') ?: null,
            'color' => trim($data['color'] ?? '') ?: null,
            'birth_date' => $data['birth_date'] ?: null,
            'acquired_date' => $data['acquired_date'] ?: null,
            'status' => $data['status'] ?? 'active',
            'note' => trim($data['note'] ?? '') ?: null,
        ];

        $id = Chicken::insert($record);
        $this->json([
            'success' => true,
            'chicken' => Chicken::findById($id),
        ]);
    }

    public function update(): void
    {
        Auth::requireAuthApi();

        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }
        if ($name === '') {
            $this->jsonError('Jméno slepice je povinné.');
        }

        $record = [
            'name' => $name,
            'breed' => trim($data['breed'] ?? '') ?: null,
            'color' => trim($data['color'] ?? '') ?: null,
            'birth_date' => $data['birth_date'] ?: null,
            'acquired_date' => $data['acquired_date'] ?: null,
            'status' => $data['status'] ?? 'active',
            'note' => trim($data['note'] ?? '') ?: null,
        ];

        Chicken::update($id, $record);
        $this->json([
            'success' => true,
            'chicken' => Chicken::findById($id),
        ]);
    }

    public function uploadPhoto(): void
    {
        Auth::requireAuthApi();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonError('Žádný soubor nebyl nahrán.');
        }

        $file = $_FILES['photo'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($file['type'], $allowed)) {
            $this->jsonError('Povolené formáty: JPEG, PNG, WebP.');
        }

        $ext = match ($file['type']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        };

        $filename = 'chicken_' . $id . '_' . uniqid() . '.' . $ext;
        $uploadDir = __DIR__ . '/../../../public/uploads/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            $this->jsonError('Nepodařilo se uložit soubor.', 500);
        }

        // Smazat starou fotku
        $chicken = Chicken::findById($id);
        if ($chicken && $chicken['photo']) {
            @unlink($uploadDir . $chicken['photo']);
        }

        Chicken::update($id, ['photo' => $filename]);

        $this->json([
            'success' => true,
            'photo' => $filename,
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

        // Smazat fotku
        $chicken = Chicken::findById($id);
        if ($chicken && $chicken['photo']) {
            $uploadDir = __DIR__ . '/../../../public/uploads/';
            @unlink($uploadDir . $chicken['photo']);
        }

        Chicken::delete($id);
        $this->json(['success' => true]);
    }
}

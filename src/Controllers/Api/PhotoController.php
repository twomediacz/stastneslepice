<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Photo;
use App\Services\ImageUploadService;

class PhotoController extends Controller
{
    private const MAX_SIZE = 10 * 1024 * 1024; // 10 MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const PHOTO_MAX_LONG_SIDE = 1080;
    private const THUMB_MAX_WIDTH = 300;

    public function index(): void
    {
        $limit = (int) ($_GET['limit'] ?? 50);
        $this->json(['photos' => Photo::getAll($limit)]);
    }

    public function store(): void
    {
        Auth::requireAuthApi();

        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonError('Žádný soubor nebyl nahrán.');
        }

        $file = $_FILES['photo'];

        if (!in_array($file['type'], self::ALLOWED_TYPES, true)) {
            $this->jsonError('Povolené formáty: JPEG, PNG, WebP.');
        }

        if ($file['size'] > self::MAX_SIZE) {
            $this->jsonError('Maximální velikost souboru je 10 MB.');
        }

        $ext = match ($file['type']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        };

        $filename = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
        $uploadDir = __DIR__ . '/../../../public/uploads/';
        $thumbDir = $uploadDir . 'thumbs/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $dest = $uploadDir . $filename;
        if (!ImageUploadService::saveResized($file['tmp_name'], $dest, $ext, self::PHOTO_MAX_LONG_SIDE)) {
            $this->jsonError('Nepodařilo se zpracovat obrázek.', 500);
        }

        $this->createThumbnail($dest, $thumbDir . $filename, $ext);

        $caption = trim($_POST['caption'] ?? '') ?: null;
        $id = Photo::add($filename, $caption);

        $this->json([
            'success' => true,
            'photo' => Photo::findById($id),
        ]);
    }

    public function destroy(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID fotky.');
        }

        Photo::deletePhoto($id);
        $this->json(['success' => true]);
    }

    public function regenerateThumbs(): void
    {
        Auth::requireAuthApi();

        $uploadDir = __DIR__ . '/../../../public/uploads/';
        $thumbDir = $uploadDir . 'thumbs/';

        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $files = array_merge(
            glob($uploadDir . '*.jpg'),
            glob($uploadDir . '*.jpeg'),
            glob($uploadDir . '*.png'),
            glob($uploadDir . '*.webp')
        );

        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($files as $source) {
            $filename = basename($source);
            $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
            if ($ext === 'jpeg') $ext = 'jpg';

            $this->createThumbnail($source, $thumbDir . $filename, $ext);
            $success++;
        }

        $this->json([
            'success' => true,
            'regenerated' => $success,
            'failed' => $failed,
            'total' => count($files),
        ]);
    }

    private function createThumbnail(string $source, string $dest, string $ext): void
    {
        ImageUploadService::createThumbnail($source, $dest, $ext, self::THUMB_MAX_WIDTH);
    }
}

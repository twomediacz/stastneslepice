<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Photo;

class PhotoController extends Controller
{
    private const MAX_SIZE = 5 * 1024 * 1024; // 5 MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const THUMB_MAX_WIDTH = 300;

    public function index(): void
    {
        Auth::requireAuthApi();
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

        if (!in_array($file['type'], self::ALLOWED_TYPES)) {
            $this->jsonError('Povolené formáty: JPEG, PNG, WebP.');
        }

        if ($file['size'] > self::MAX_SIZE) {
            $this->jsonError('Maximální velikost souboru je 5 MB.');
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

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            $this->jsonError('Nepodařilo se uložit soubor.', 500);
        }

        $this->createThumbnail($uploadDir . $filename, $thumbDir . $filename, $ext);

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

    private function createThumbnail(string $source, string $dest, string $ext): void
    {
        $image = match ($ext) {
            'jpg' => @imagecreatefromjpeg($source),
            'png' => @imagecreatefrompng($source),
            'webp' => @imagecreatefromwebp($source),
            default => false,
        };

        if (!$image) return;

        $origW = imagesx($image);
        $origH = imagesy($image);

        if ($origW <= self::THUMB_MAX_WIDTH) {
            copy($source, $dest);
            imagedestroy($image);
            return;
        }

        $ratio = self::THUMB_MAX_WIDTH / $origW;
        $newW = self::THUMB_MAX_WIDTH;
        $newH = (int) round($origH * $ratio);

        $thumb = imagecreatetruecolor($newW, $newH);

        if ($ext === 'png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        match ($ext) {
            'jpg' => imagejpeg($thumb, $dest, 85),
            'png' => imagepng($thumb, $dest),
            'webp' => imagewebp($thumb, $dest, 85),
        };

        imagedestroy($image);
        imagedestroy($thumb);
    }
}

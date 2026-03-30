<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Photo;

class PhotoController extends Controller
{
    private const MAX_SIZE = 10 * 1024 * 1024; // 10 MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
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

        if (!in_array($file['type'], self::ALLOWED_TYPES)) {
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
        $image = match ($ext) {
            'jpg' => @imagecreatefromjpeg($source),
            'png' => @imagecreatefrompng($source),
            'webp' => @imagecreatefromwebp($source),
            default => false,
        };

        if (!$image) return;

        // Fix orientation based on EXIF data (mainly for JPEG from mobile phones)
        if ($ext === 'jpg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($source);
            if ($exif && !empty($exif['Orientation'])) {
                $image = match ((int) $exif['Orientation']) {
                    3 => imagerotate($image, 180, 0),
                    6 => imagerotate($image, -90, 0),
                    8 => imagerotate($image, 90, 0),
                    default => $image,
                };
            }
        }

        $origW = imagesx($image);
        $origH = imagesy($image);

        if ($origW <= self::THUMB_MAX_WIDTH) {
            match ($ext) {
                'jpg' => imagejpeg($image, $dest, 85),
                'png' => imagepng($image, $dest),
                'webp' => imagewebp($image, $dest, 85),
            };
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

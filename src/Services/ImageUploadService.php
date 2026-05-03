<?php

namespace App\Services;

class ImageUploadService
{
    public static function saveResized(string $source, string $dest, string $ext, int $maxLongSide, int $quality = 85): bool
    {
        $image = self::createImage($source, $ext);
        if (!$image) {
            return false;
        }

        $image = self::applyOrientation($image, $source, $ext);
        $origW = imagesx($image);
        $origH = imagesy($image);
        $longSide = max($origW, $origH);

        if ($longSide <= $maxLongSide) {
            return self::saveImage($image, $dest, $ext, $quality);
        }

        $ratio = $maxLongSide / $longSide;
        $newW = max(1, (int) round($origW * $ratio));
        $newH = max(1, (int) round($origH * $ratio));
        $resized = imagecreatetruecolor($newW, $newH);

        self::prepareCanvas($resized, $ext);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        $result = self::saveImage($resized, $dest, $ext, $quality);

        return $result;
    }

    public static function createThumbnail(string $source, string $dest, string $ext, int $maxWidth, int $quality = 85): bool
    {
        $image = self::createImage($source, $ext);
        if (!$image) {
            return false;
        }

        $image = self::applyOrientation($image, $source, $ext);
        $origW = imagesx($image);
        $origH = imagesy($image);

        if ($origW <= $maxWidth) {
            return self::saveImage($image, $dest, $ext, $quality);
        }

        $ratio = $maxWidth / $origW;
        $newW = $maxWidth;
        $newH = max(1, (int) round($origH * $ratio));
        $thumb = imagecreatetruecolor($newW, $newH);

        self::prepareCanvas($thumb, $ext);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        $result = self::saveImage($thumb, $dest, $ext, $quality);

        return $result;
    }

    private static function createImage(string $source, string $ext): mixed
    {
        return match ($ext) {
            'jpg' => @imagecreatefromjpeg($source),
            'png' => @imagecreatefrompng($source),
            'webp' => @imagecreatefromwebp($source),
            default => false,
        };
    }

    private static function applyOrientation(mixed $image, string $source, string $ext): mixed
    {
        if ($ext !== 'jpg' || !function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($source);
        if (!$exif || empty($exif['Orientation'])) {
            return $image;
        }

        $rotated = match ((int) $exif['Orientation']) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated ?: $image;
    }

    private static function prepareCanvas(mixed $canvas, string $ext): void
    {
        if ($ext !== 'png' && $ext !== 'webp') {
            return;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, imagesx($canvas), imagesy($canvas), $transparent);
    }

    private static function saveImage(mixed $image, string $dest, string $ext, int $quality): bool
    {
        return match ($ext) {
            'jpg' => imagejpeg($image, $dest, $quality),
            'png' => imagepng($image, $dest),
            'webp' => imagewebp($image, $dest, $quality),
            default => false,
        };
    }
}

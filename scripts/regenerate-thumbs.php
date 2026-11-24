#!/usr/bin/env php
<?php

/**
 * Regenerate all photo thumbnails with correct EXIF orientation.
 *
 * Usage: php scripts/regenerate-thumbs.php
 */

$uploadDir = __DIR__ . '/../public/uploads/';
$thumbDir = $uploadDir . 'thumbs/';
$maxWidth = 300;

if (!is_dir($uploadDir)) {
    echo "Adresář $uploadDir neexistuje.\n";
    exit(1);
}

if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

$extensions = ['jpg', 'jpeg', 'png', 'webp'];
$files = [];

foreach ($extensions as $ext) {
    $files = array_merge($files, glob($uploadDir . '*.' . $ext));
}

if (empty($files)) {
    echo "Žádné fotky k přegenerování.\n";
    exit(0);
}

$total = count($files);
$success = 0;
$failed = 0;

echo "Nalezeno $total fotek. Regeneruji náhledy...\n";

foreach ($files as $source) {
    $filename = basename($source);
    $dest = $thumbDir . $filename;
    $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));

    if ($ext === 'jpeg') {
        $ext = 'jpg';
    }

    $image = match ($ext) {
        'jpg' => @imagecreatefromjpeg($source),
        'png' => @imagecreatefrompng($source),
        'webp' => @imagecreatefromwebp($source),
        default => false,
    };

    if (!$image) {
        echo "  CHYBA: $filename — nepodařilo se načíst obrázek\n";
        $failed++;
        continue;
    }

    // Fix orientation based on EXIF data
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

    if ($origW <= $maxWidth) {
        // Image is small enough — save directly with orientation fix applied
        match ($ext) {
            'jpg' => imagejpeg($image, $dest, 85),
            'png' => imagepng($image, $dest),
            'webp' => imagewebp($image, $dest, 85),
        };
    } else {
        $ratio = $maxWidth / $origW;
        $newW = $maxWidth;
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

        imagedestroy($thumb);
    }

    imagedestroy($image);
    $success++;
    echo "  OK: $filename\n";
}

echo "\nHotovo. Úspěšně: $success, chyby: $failed, celkem: $total\n";

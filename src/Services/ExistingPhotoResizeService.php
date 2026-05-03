<?php

namespace App\Services;

use PDO;
use PDOException;

class ExistingPhotoResizeService
{
    private const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'webp'];

    public function run(PDO $db, string $root, array $options = []): array
    {
        $maxLongSide = (int) ($options['max'] ?? 1080);
        $dryRun = (bool) ($options['dry_run'] ?? true);
        $backup = (bool) ($options['backup'] ?? true);
        $uploadDir = rtrim($root, '/') . '/public/uploads';
        $thumbDir = $uploadDir . '/thumbs';

        $lines = [];
        $stats = [
            'checked' => 0,
            'resized' => 0,
            'already_ok' => 0,
            'missing' => 0,
            'failed' => 0,
            'thumbs' => 0,
        ];

        if ($maxLongSide < 1) {
            return $this->result(false, ['Hodnota max musí být kladné číslo.'], $stats);
        }

        if (!is_dir($uploadDir)) {
            return $this->result(false, ['Adresář public/uploads neexistuje.'], $stats);
        }

        if (!$dryRun && !is_dir($thumbDir) && !mkdir($thumbDir, 0755, true)) {
            return $this->result(false, ['Nepodařilo se vytvořit adresář public/uploads/thumbs.'], $stats);
        }

        try {
            $galleryFiles = $this->fetchColumn($db, 'SELECT filename FROM photos WHERE filename IS NOT NULL AND filename <> ""');
            $chickenFiles = $this->fetchColumn($db, 'SELECT photo FROM chickens WHERE photo IS NOT NULL AND photo <> ""');
        } catch (PDOException $e) {
            return $this->result(false, [
                'Nepodařilo se načíst fotky z databáze. Zkontrolujte DB konfiguraci a existenci tabulek photos/chickens.',
                $e->getMessage(),
            ], $stats);
        }

        $items = [];
        foreach ($galleryFiles as $filename) {
            $items[$filename]['gallery'] = true;
        }
        foreach ($chickenFiles as $filename) {
            $items[$filename]['chicken'] = true;
        }

        foreach ($items as $filename => $sourceTypes) {
            $this->processFile($filename, $sourceTypes, $uploadDir, $thumbDir, $maxLongSide, $dryRun, $backup, $lines, $stats);
        }

        $lines[] = '';
        $lines[] = 'Hotovo.';
        $lines[] = "Zkontrolováno: {$stats['checked']}";
        $lines[] = "Zmenšeno: {$stats['resized']}";
        $lines[] = "Už v pořádku: {$stats['already_ok']}";
        $lines[] = "Náhledy galerie: {$stats['thumbs']}";
        $lines[] = "Chybí soubor: {$stats['missing']}";
        $lines[] = "Chyby: {$stats['failed']}";
        if ($backup && !$dryRun) {
            $lines[] = 'Původní zmenšené soubory jsou uložené jako .bak soubory vedle originálů.';
        }

        return $this->result($stats['failed'] === 0, $lines, $stats);
    }

    private function processFile(
        string $filename,
        array $sourceTypes,
        string $uploadDir,
        string $thumbDir,
        int $maxLongSide,
        bool $dryRun,
        bool $backup,
        array &$lines,
        array &$stats
    ): void {
        $label = implode('+', array_keys($sourceTypes));
        $stats['checked']++;

        if ($filename !== basename($filename)) {
            $stats['failed']++;
            $lines[] = "[skip] {$filename} ({$label}) - neplatný název souboru";
            return;
        }

        $path = $uploadDir . '/' . $filename;
        if (!is_file($path)) {
            $stats['missing']++;
            $lines[] = "[missing] {$filename} ({$label})";
            return;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if (!in_array($ext, self::ALLOWED_EXTS, true)) {
            $stats['failed']++;
            $lines[] = "[skip] {$filename} ({$label}) - nepodporovaná přípona";
            return;
        }

        $size = @getimagesize($path);
        if (!$size) {
            $stats['failed']++;
            $lines[] = "[fail] {$filename} ({$label}) - soubor nejde načíst jako obrázek";
            return;
        }

        [$width, $height] = $size;
        $longSide = max($width, $height);
        $needsResize = $longSide > $maxLongSide;
        $needsThumb = isset($sourceTypes['gallery']);

        if ($dryRun) {
            $action = $needsResize ? "resize {$width}x{$height}" : "ok {$width}x{$height}";
            $thumbText = $needsThumb ? ', thumbnail' : '';
            $lines[] = "[dry-run] {$filename} ({$label}) - {$action}{$thumbText}";
            $stats[$needsResize ? 'resized' : 'already_ok']++;
            if ($needsThumb) {
                $stats['thumbs']++;
            }
            return;
        }

        if ($needsResize) {
            $tmp = $path . '.resize.tmp';
            if (!ImageUploadService::saveResized($path, $tmp, $ext, $maxLongSide)) {
                @unlink($tmp);
                $stats['failed']++;
                $lines[] = "[fail] {$filename} ({$label}) - zmenšení selhalo";
                return;
            }

            if ($backup) {
                $backupPath = $this->uniqueBackupPath($path);
                if (!copy($path, $backupPath)) {
                    @unlink($tmp);
                    $stats['failed']++;
                    $lines[] = "[fail] {$filename} ({$label}) - nepodařilo se vytvořit zálohu";
                    return;
                }
            }

            if (!rename($tmp, $path)) {
                @unlink($tmp);
                $stats['failed']++;
                $lines[] = "[fail] {$filename} ({$label}) - nepodařilo se nahradit soubor";
                return;
            }

            $newSize = @getimagesize($path);
            $newLabel = $newSize ? "{$newSize[0]}x{$newSize[1]}" : 'neznámá velikost';
            $stats['resized']++;
            $lines[] = "[resize] {$filename} ({$label}) - {$width}x{$height} -> {$newLabel}";
        } else {
            $stats['already_ok']++;
            $lines[] = "[ok] {$filename} ({$label}) - {$width}x{$height}";
        }

        if ($needsThumb) {
            $thumbPath = $thumbDir . '/' . $filename;
            if (ImageUploadService::createThumbnail($path, $thumbPath, $ext, 300)) {
                $stats['thumbs']++;
                $lines[] = "[thumb] {$filename}";
            } else {
                $stats['failed']++;
                $lines[] = "[fail] {$filename} - vytvoření náhledu selhalo";
            }
        }
    }

    private function fetchColumn(PDO $db, string $sql): array
    {
        return array_values(array_filter($db->query($sql)->fetchAll(PDO::FETCH_COLUMN), 'strlen'));
    }

    private function uniqueBackupPath(string $path): string
    {
        $backup = $path . '.bak';
        if (!file_exists($backup)) {
            return $backup;
        }

        $i = 2;
        do {
            $candidate = $path . '.bak' . $i;
            $i++;
        } while (file_exists($candidate));

        return $candidate;
    }

    private function result(bool $success, array $lines, array $stats): array
    {
        return [
            'success' => $success,
            'lines' => $lines,
            'stats' => $stats,
        ];
    }
}

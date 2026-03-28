#!/usr/bin/env php
<?php

/**
 * Generátor demo dat za poslední rok.
 *
 * - climate_records: hodinové záznamy teploty a vlhkosti (coop + outdoor)
 * - egg_records:     denní snůška vajec
 *
 * Spuštění:  php database/seed_demo_year.php
 */

declare(strict_types=1);

// ── Bootstrap ────────────────────────────────────────────────────────────────

require __DIR__ . '/../src/Core/autoload.php';

$config = require __DIR__ . '/../config/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Prague');

$pdo = \App\Core\Database::connect($config['db']);

// ── Pomocné funkce ──────────────────────────────────────────────────────────

/**
 * Vrátí venkovní teplotu pro daný den a hodinu.
 * Modeluje sezónní i denní průběh typický pro střední Moravu.
 */
function outdoorTemperature(int $dayOfYear, int $hour, float $noise): float
{
    // Sezónní složka – sinusoida s minimem kolem 15. ledna (den 15),
    // maximem kolem 15. července (den 196).
    $yearPhase = 2 * M_PI * ($dayOfYear - 15) / 365;
    $seasonalBase = 9.0 + 12.0 * sin($yearPhase - M_PI / 2);
    // → min ≈ -3 °C (leden), max ≈ 21 °C (červenec)

    // Denní složka – sinusoida s minimem ve 4:00, maximem ve 15:00.
    $dailyAmplitude = 4.0 + 3.0 * sin($yearPhase - M_PI / 2);
    // Větší denní rozpětí v létě (7 °C), menší v zimě (1 °C).
    $hourPhase = 2 * M_PI * ($hour - 4) / 24;
    $dailyVariation = $dailyAmplitude * sin($hourPhase - M_PI / 2);

    return round($seasonalBase + $dailyVariation + $noise, 1);
}

/**
 * Vrátí venkovní vlhkost.
 * Vlhkost je inverzně korelovaná s teplotou + sezónní offset.
 */
function outdoorHumidity(float $temperature, int $dayOfYear, float $noise): float
{
    // Základní inverzní vztah: čím teplejší, tím sušší
    $base = 75.0 - $temperature * 1.2;
    // Zimní měsíce mají vyšší vlhkost
    $yearPhase = 2 * M_PI * ($dayOfYear - 15) / 365;
    $seasonalOffset = -5.0 * sin($yearPhase - M_PI / 2);

    return round(max(30.0, min(98.0, $base + $seasonalOffset + $noise)), 1);
}

/**
 * Vrátí teplotu v kurníku.
 * Kurník je teplejší díky izolaci a tělesnému teplu slepic.
 */
function coopTemperature(float $outdoorTemp, int $dayOfYear, float $noise): float
{
    // V zimě je rozdíl větší (slepice hřejí, kurník izoluje),
    // v létě menší.
    $yearPhase = 2 * M_PI * ($dayOfYear - 15) / 365;
    $offset = 8.0 - 4.0 * sin($yearPhase - M_PI / 2);
    // → zima: +12 °C, léto: +4 °C

    return round($outdoorTemp + $offset + $noise, 1);
}

/**
 * Vrátí vlhkost v kurníku.
 * Obecně o něco vyšší než venku kvůli dýchání slepic a napáječkám.
 */
function coopHumidity(float $outdoorHumidity, float $noise): float
{
    return round(max(35.0, min(95.0, $outdoorHumidity + 5.0 + $noise)), 1);
}

/**
 * Vrátí denní snůšku vajec.
 * Závisí na délce dne (fotoperiodě) – v létě vyšší, v zimě nižší.
 * Máme 7 slepic (6 aktivních, 1 nemocná).
 */
function dailyEggCount(int $dayOfYear, float $noise): int
{
    // Fotoperioda – max snůška kolem letního slunovratu.
    $yearPhase = 2 * M_PI * ($dayOfYear - 15) / 365;
    $base = 4.0 + 2.5 * sin($yearPhase - M_PI / 2);
    // → zima: ~1.5, léto: ~6.5

    $count = (int) round($base + $noise);

    return max(0, min(7, $count));
}

/**
 * Vrátí příležitostnou poznámku ke snůšce (nebo null).
 */
function eggNote(int $eggCount, int $dayIndex): ?string
{
    // Jen občas přidáme poznámku (~15 % dní).
    $hash = crc32("note-{$dayIndex}");
    if ($hash % 100 > 15) {
        return null;
    }

    $notes = [
        'jedno vejce dvojžloutkové',
        'dvě slepice nenesly',
        'všechna velká',
        'jedno vejce měkké',
        'menší vejce',
        'slepice neklidné',
        'velmi teplý den',
        'silný vítr',
        'slepice byly venku celý den',
        'doplněno krmivo',
        'nová sláma v hnízdech',
        'kontrola zdraví – vše OK',
        'jedno vejce nalezeno mimo hnízdo',
        'dva žloutky v jednom vejci',
    ];

    return $notes[$hash % count($notes)];
}

// ── Smazání starých demo dat (volitelné) ────────────────────────────────────

echo "Mažu stará klimatická data...\n";
$pdo->exec('DELETE FROM climate_records');

echo "Mažu stará data o snůšce...\n";
$pdo->exec('DELETE FROM egg_records');

// ── Generování klimatických dat ─────────────────────────────────────────────

echo "Generuji klimatická data (2 lokace × 8 760 hodin)...\n";

$now = new DateTimeImmutable('now');
$startDate = $now->modify('-1 year');

// Připravíme prepared statement pro dávkové vkládání.
$insertClimate = $pdo->prepare(
    'INSERT INTO climate_records (recorded_at, location, temperature, humidity)
     VALUES (:recorded_at, :location, :temperature, :humidity)'
);

// Pro lepší výkon zabalíme inserty do transakcí po 1 000 záznamech.
$batchSize = 1000;
$count = 0;

$pdo->beginTransaction();

$current = $startDate;
while ($current <= $now) {
    $dayOfYear = (int) $current->format('z'); // 0–365
    $hour = (int) $current->format('G');       // 0–23

    // Deterministický šum z data (opakovatelné výsledky).
    $dayKey = $current->format('Y-m-d-H');
    $noiseOutT = (crc32("ot-{$dayKey}") % 200 - 100) / 100.0 * 2.0; // ±2 °C
    $noiseOutH = (crc32("oh-{$dayKey}") % 200 - 100) / 100.0 * 5.0; // ±5 %
    $noiseCoopT = (crc32("ct-{$dayKey}") % 200 - 100) / 100.0 * 1.5; // ±1.5 °C
    $noiseCoopH = (crc32("ch-{$dayKey}") % 200 - 100) / 100.0 * 4.0; // ±4 %

    $outTemp = outdoorTemperature($dayOfYear, $hour, $noiseOutT);
    $outHum = outdoorHumidity($outTemp, $dayOfYear, $noiseOutH);
    $cpTemp = coopTemperature($outTemp, $dayOfYear, $noiseCoopT);
    $cpHum = coopHumidity($outHum, $noiseCoopH);

    $recordedAt = $current->format('Y-m-d H:i:s');

    // Outdoor
    $insertClimate->execute([
        ':recorded_at' => $recordedAt,
        ':location' => 'outdoor',
        ':temperature' => $outTemp,
        ':humidity' => $outHum,
    ]);
    $count++;

    // Coop
    $insertClimate->execute([
        ':recorded_at' => $recordedAt,
        ':location' => 'coop',
        ':temperature' => $cpTemp,
        ':humidity' => $cpHum,
    ]);
    $count++;

    if ($count % $batchSize === 0) {
        $pdo->commit();
        $pdo->beginTransaction();
    }

    $current = $current->modify('+1 hour');
}

$pdo->commit();
echo "  Vloženo {$count} klimatických záznamů.\n";

// ── Generování dat o snůšce vajec ───────────────────────────────────────────

echo "Generuji záznamy o snůšce vajec (365 dní)...\n";

$insertEgg = $pdo->prepare(
    'INSERT INTO egg_records (record_date, egg_count, note)
     VALUES (:record_date, :egg_count, :note)
     ON DUPLICATE KEY UPDATE egg_count = VALUES(egg_count), note = VALUES(note)'
);

$eggCount = 0;
$totalEggs = 0;

$pdo->beginTransaction();

$currentDay = $startDate;
$dayIndex = 0;
while ($currentDay->format('Y-m-d') <= $now->format('Y-m-d')) {
    $dayOfYear = (int) $currentDay->format('z');
    $noiseEgg = (crc32("egg-{$dayIndex}") % 200 - 100) / 100.0 * 1.5;

    $eggs = dailyEggCount($dayOfYear, $noiseEgg);
    $note = eggNote($eggs, $dayIndex);
    $totalEggs += $eggs;

    $insertEgg->execute([
        ':record_date' => $currentDay->format('Y-m-d'),
        ':egg_count' => $eggs,
        ':note' => $note,
    ]);

    $eggCount++;
    $dayIndex++;
    $currentDay = $currentDay->modify('+1 day');
}

$pdo->commit();
echo "  Vloženo {$eggCount} denních záznamů ({$totalEggs} vajec celkem).\n";

echo "\nHotovo!\n";

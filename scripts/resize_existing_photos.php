<?php

declare(strict_types=1);

use App\Core\Database;
use App\Services\ExistingPhotoResizeService;

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    fwrite(STDERR, "Chybí vendor/autoload.php. Spusťte composer install.\n");
    exit(1);
}

require $autoload;

$options = getopt('', ['dry-run', 'no-backup', 'max::', 'help']);
if (isset($options['help'])) {
    echo <<<TXT
Jednorázové zmenšení existujících fotek.

Použití:
  php scripts/resize_existing_photos.php [--dry-run] [--no-backup] [--max=1080]

Volby:
  --dry-run    Jen vypíše, co by se změnilo, nic nezapisuje.
  --no-backup  Nevytváří .bak zálohy původních souborů.
  --max=1080   Maximální velikost delší strany v pixelech.

TXT;
    exit(0);
}

$config = require $root . '/config/config.php';
applyDbEnvOverrides($config);

$result = (new ExistingPhotoResizeService())->run(
    Database::connect($config['db']),
    $root,
    [
        'dry_run' => isset($options['dry-run']),
        'backup' => !isset($options['no-backup']),
        'max' => (int) ($options['max'] ?? 1080),
    ]
);

echo implode(PHP_EOL, $result['lines']), PHP_EOL;
exit($result['success'] ? 0 : 1);

function applyDbEnvOverrides(array &$config): void
{
    foreach ([
        'DB_DRIVER' => 'driver',
        'DB_HOST' => 'host',
        'DB_PORT' => 'port',
        'DB_DATABASE' => 'database',
        'DB_USERNAME' => 'username',
        'DB_PASSWORD' => 'password',
        'DB_CHARSET' => 'charset',
    ] as $env => $key) {
        $value = getenv($env);
        if ($value === false || $value === '') {
            continue;
        }

        $config['db'][$key] = $key === 'port' ? (int) $value : $value;
    }
}

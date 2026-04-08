<?php

$config = require __DIR__ . '/../config/config.php';
$apiConfig = $config['api'] ?? [];
$expectedToken = (string) ($apiConfig['tts_debug_token'] ?? '');
$providedToken = (string) ($_GET['token'] ?? '');

if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "TTS diagnostika je vypnutá nebo chybí správný token.\n";
    exit;
}

$results = [];

$addResult = static function (string $label, bool $ok, string $detail) use (&$results): void {
    $results[] = [
        'label' => $label,
        'ok' => $ok,
        'detail' => $detail,
    ];
};

$maskSecret = static function (string $value): string {
    if ($value === '') {
        return '(nenastaveno)';
    }
    if (strlen($value) <= 10) {
        return str_repeat('*', strlen($value));
    }
    return substr($value, 0, 6) . '...' . substr($value, -4);
};

$apiKey = (string) ($apiConfig['openai_api_key'] ?? '');
$model = (string) ($apiConfig['tts_model'] ?? '');
$voice = (string) ($apiConfig['tts_voice'] ?? '');
$format = (string) ($apiConfig['tts_format'] ?? '');
$instructions = (string) ($apiConfig['tts_instructions'] ?? '');

$addResult('OpenAI API klíč', $apiKey !== '', $maskSecret($apiKey));
$addResult('PHP cURL rozšíření', extension_loaded('curl'), extension_loaded('curl') ? 'Načteno' : 'Chybí rozšíření curl');
$addResult('TTS model', $model !== '', $model !== '' ? $model : '(nenastaveno)');
$addResult('TTS voice', $voice !== '', $voice !== '' ? $voice : '(nenastaveno)');
$addResult('TTS formát', $format !== '', $format !== '' ? $format : '(nenastaveno)');
$addResult('TTS instructions', $instructions !== '', $instructions !== '' ? $instructions : '(nenastaveno)');

$resolvedHost = gethostbyname('api.openai.com');
$dnsOk = $resolvedHost !== 'api.openai.com';
$addResult('DNS api.openai.com', $dnsOk, $dnsOk ? $resolvedHost : 'Nepodařilo se přeložit hostname');

$httpsOk = false;
$httpsDetail = 'Kontrola neproběhla';
$ttsOk = false;
$ttsDetail = 'TTS test neproběhl';

if (extension_loaded('curl')) {
    $head = curl_init('https://api.openai.com/v1/models');
    curl_setopt_array($head, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($head);
    $headError = curl_error($head);
    $headStatus = (int) curl_getinfo($head, CURLINFO_HTTP_CODE);
    $headUrl = (string) curl_getinfo($head, CURLINFO_EFFECTIVE_URL);

    if ($headError !== '') {
        $httpsDetail = $headError;
    } else {
        $httpsOk = $headStatus > 0;
        $httpsDetail = sprintf('HTTP %d, %s', $headStatus, $headUrl !== '' ? $headUrl : 'bez redirectu');
    }

    $payload = [
        'model' => $model !== '' ? $model : 'gpt-4o-mini-tts',
        'voice' => $voice !== '' ? $voice : 'cedar',
        'input' => 'Test českého předčítání.',
        'response_format' => $format !== '' ? $format : 'mp3',
    ];
    if ($instructions !== '') {
        $payload['instructions'] = $instructions;
    }

    if ($apiKey !== '') {
        $tts = curl_init('https://api.openai.com/v1/audio/speech');
        curl_setopt_array($tts, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $ttsResponse = curl_exec($tts);
        $ttsError = curl_error($tts);
        $ttsStatus = (int) curl_getinfo($tts, CURLINFO_HTTP_CODE);

        if ($ttsError !== '') {
            $ttsDetail = $ttsError;
        } elseif ($ttsStatus !== 200 || !is_string($ttsResponse) || $ttsResponse === '') {
            $json = is_string($ttsResponse) ? json_decode($ttsResponse, true) : null;
            $message = is_array($json) && isset($json['error']['message'])
                ? (string) $json['error']['message']
                : 'Prázdná nebo neplatná odpověď';
            $ttsDetail = sprintf('HTTP %d, %s', $ttsStatus, $message);
        } else {
            $ttsOk = true;
            $ttsDetail = sprintf('Audio vygenerováno, %d bajtů', strlen($ttsResponse));
        }
    } else {
        $ttsDetail = 'Přeskakuji, protože chybí API klíč';
    }
}

$addResult('HTTPS api.openai.com', $httpsOk, $httpsDetail);
$addResult('OpenAI TTS request', $ttsOk, $ttsDetail);

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TTS diagnostika</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 2rem; color: #222; background: #f6f7f9; }
        .card { max-width: 900px; margin: 0 auto; background: #fff; border: 1px solid #d9dde3; border-radius: 14px; padding: 1.5rem; }
        h1 { margin-bottom: 0.5rem; }
        p { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 0.8rem; border-bottom: 1px solid #eceff3; vertical-align: top; }
        th { width: 240px; }
        .ok { color: #1b7f3b; font-weight: 700; }
        .fail { color: #b42318; font-weight: 700; }
        code { background: #f1f3f5; padding: 0.1rem 0.35rem; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>TTS diagnostika</h1>
        <p>Stránka ověřuje konfiguraci, DNS, HTTPS spojení a reálný požadavek na OpenAI TTS.</p>
        <table>
            <thead>
                <tr>
                    <th>Kontrola</th>
                    <th>Stav</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['label']) ?></td>
                    <td class="<?= $row['ok'] ? 'ok' : 'fail' ?>"><?= $row['ok'] ? 'OK' : 'CHYBA' ?></td>
                    <td><code><?= htmlspecialchars($row['detail']) ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

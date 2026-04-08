<?php

namespace App\Services;

use RuntimeException;

class TextToSpeechService
{
    private const API_URL = 'https://api.openai.com/v1/audio/speech';
    private const CACHE_DIR = __DIR__ . '/../../storage/cache/tts';
    private const MAX_INPUT_LENGTH = 4096;

    private array $config;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $api = $config['api'] ?? [];

        $this->config = [
            'api_key' => getenv('OPENAI_API_KEY') ?: ($api['openai_api_key'] ?? ''),
            'model' => getenv('OPENAI_TTS_MODEL') ?: ($api['tts_model'] ?? 'gpt-4o-mini-tts'),
            'voice' => getenv('OPENAI_TTS_VOICE') ?: ($api['tts_voice'] ?? 'cedar'),
            'format' => getenv('OPENAI_TTS_FORMAT') ?: ($api['tts_format'] ?? 'mp3'),
            'instructions' => getenv('OPENAI_TTS_INSTRUCTIONS') ?: ($api['tts_instructions'] ?? ''),
        ];
    }

    public function synthesizeNote(int $noteId, string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            throw new RuntimeException('Poznámka je prázdná.');
        }
        if (mb_strlen($text) > self::MAX_INPUT_LENGTH) {
            throw new RuntimeException('Poznámka je příliš dlouhá pro předčítání.');
        }

        $apiKey = trim((string) $this->config['api_key']);
        if ($apiKey === '') {
            throw new RuntimeException('Kvalitní předčítání ještě není nastavené. Doplňte OpenAI API klíč.');
        }

        $format = $this->normalizeFormat((string) $this->config['format']);
        $cacheFile = $this->getCacheFile($noteId, $text, $format);
        if (is_file($cacheFile)) {
            $binary = file_get_contents($cacheFile);
            if ($binary !== false && $binary !== '') {
                return [
                    'mime_type' => $this->mimeTypeForFormat($format),
                    'binary' => $binary,
                ];
            }
        }

        $payload = [
            'model' => (string) $this->config['model'],
            'voice' => (string) $this->config['voice'],
            'input' => $text,
            'response_format' => $format,
        ];

        $instructions = trim((string) $this->config['instructions']);
        if ($instructions !== '') {
            $payload['instructions'] = $instructions;
        }

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
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

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $response === '' || $httpCode !== 200) {
            $message = 'Nepodařilo se vygenerovat audio pro předčítání.';
            $errorPayload = is_string($response) ? json_decode($response, true) : null;
            if (is_array($errorPayload) && isset($errorPayload['error']['message'])) {
                $message = (string) $errorPayload['error']['message'];
            } elseif ($curlError !== '') {
                $message = 'Služba pro předčítání není dostupná: ' . $curlError;
            }

            throw new RuntimeException($message);
        }

        $this->ensureCacheDirectory();
        file_put_contents($cacheFile, $response);

        return [
            'mime_type' => $this->mimeTypeForFormat($format),
            'binary' => $response,
        ];
    }

    private function ensureCacheDirectory(): void
    {
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
    }

    private function getCacheFile(int $noteId, string $text, string $format): string
    {
        $fingerprint = sha1(json_encode([
            'id' => $noteId,
            'text' => $text,
            'model' => $this->config['model'],
            'voice' => $this->config['voice'],
            'instructions' => $this->config['instructions'],
            'format' => $format,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::CACHE_DIR . '/' . $fingerprint . '.' . $format;
    }

    private function normalizeFormat(string $format): string
    {
        $format = strtolower(trim($format));
        return in_array($format, ['mp3', 'wav', 'opus', 'aac', 'flac', 'pcm'], true) ? $format : 'mp3';
    }

    private function mimeTypeForFormat(string $format): string
    {
        return match ($format) {
            'wav' => 'audio/wav',
            'opus' => 'audio/opus',
            'aac' => 'audio/aac',
            'flac' => 'audio/flac',
            'pcm' => 'audio/pcm',
            default => 'audio/mpeg',
        };
    }
}

<?php

namespace App\Models;

class VisitStat extends Model
{
    protected static string $table = 'visit_stats';

    public const HOME_PAGE = '/';
    private const COOKIE_NAME = 'stastne_slepice_visitor';

    public static function cookieName(): string
    {
        return self::COOKIE_NAME;
    }

    public static function resolveVisitorId(): string
    {
        $visitorId = $_COOKIE[self::COOKIE_NAME] ?? '';

        if (!is_string($visitorId) || !preg_match('/^[a-f0-9]{32}$/', $visitorId)) {
            $visitorId = bin2hex(random_bytes(16));
            self::setVisitorCookie($visitorId);
        }

        return $visitorId;
    }

    public static function recordHomeVisit(?string $visitorId = null): void
    {
        self::recordVisit(self::HOME_PAGE, $visitorId ?? self::resolveVisitorId());
    }

    public static function recordVisit(string $page, string $visitorId, ?string $visitedAt = null): void
    {
        $data = [
            'page' => $page,
            'visitor_id' => $visitorId,
        ];

        if ($visitedAt !== null) {
            $data['visited_at'] = $visitedAt;
        }

        static::insert($data);
    }

    public static function getHomeSummary(): array
    {
        return self::getSummary(self::HOME_PAGE);
    }

    public static function getSummary(string $page): array
    {
        $lastDayCondition = static::isSqlite()
            ? "visited_at >= datetime('now', '-1 day')"
            : 'visited_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)';

        return [
            'visits_total' => (int) static::queryValue(
                'SELECT COUNT(*) FROM visit_stats WHERE page = ?',
                [$page]
            ),
            'visits_last_day' => (int) static::queryValue(
                "SELECT COUNT(*) FROM visit_stats WHERE page = ? AND {$lastDayCondition}",
                [$page]
            ),
            'unique_total' => (int) static::queryValue(
                'SELECT COUNT(DISTINCT visitor_id) FROM visit_stats WHERE page = ?',
                [$page]
            ),
            'unique_last_day' => (int) static::queryValue(
                "SELECT COUNT(DISTINCT visitor_id) FROM visit_stats WHERE page = ? AND {$lastDayCondition}",
                [$page]
            ),
        ];
    }

    private static function setVisitorCookie(string $visitorId): void
    {
        if (headers_sent()) {
            return;
        }

        setcookie(self::COOKIE_NAME, $visitorId, [
            'expires' => time() + 365 * 24 * 60 * 60,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $_COOKIE[self::COOKIE_NAME] = $visitorId;
    }
}

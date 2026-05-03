<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\VisitStat;
use Tests\Support\DatabaseTestCase;

final class VisitStatModelTest extends DatabaseTestCase
{
    public function testHomeSummaryCountsVisitsAndUniqueVisitors(): void
    {
        VisitStat::recordVisit('/', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', date('Y-m-d H:i:s', strtotime('-2 hours')));
        VisitStat::recordVisit('/', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', date('Y-m-d H:i:s', strtotime('-1 hour')));
        VisitStat::recordVisit('/', 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', date('Y-m-d H:i:s', strtotime('-2 days')));
        VisitStat::recordVisit('/slepice', 'cccccccccccccccccccccccccccccccc', date('Y-m-d H:i:s', strtotime('-1 hour')));

        self::assertSame(
            [
                'visits_total' => 3,
                'visits_last_day' => 2,
                'unique_total' => 2,
                'unique_last_day' => 1,
            ],
            VisitStat::getHomeSummary()
        );
    }

    public function testVisitorIdCookieIsReusedWhenPresent(): void
    {
        $_COOKIE[VisitStat::cookieName()] = 'dddddddddddddddddddddddddddddddd';

        self::assertSame('dddddddddddddddddddddddddddddddd', VisitStat::resolveVisitorId());
    }
}

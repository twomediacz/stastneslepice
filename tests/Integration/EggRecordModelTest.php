<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\EggRecord;
use Tests\Support\DatabaseTestCase;

final class EggRecordModelTest extends DatabaseTestCase
{
    public function testUpsertRecentTotalsAndMonthlyAggregationWork(): void
    {
        $today = date('Y-m-d');
        $tenDaysAgo = date('Y-m-d', strtotime('-10 days'));
        $fortyDaysAgo = date('Y-m-d', strtotime('-40 days'));

        EggRecord::upsert($today, 5, 'Dnes');
        EggRecord::upsert($tenDaysAgo, 3, 'Minuly tyden');
        EggRecord::upsert($fortyDaysAgo, 7, 'Starsi');
        EggRecord::upsert($today, 6, 'Opraveno');

        $todayRecord = EggRecord::getByDate($today);
        $recent = EggRecord::getRecent(14);
        $monthly = EggRecord::getMonthlyAggregated(2);

        self::assertSame(6, (int) $todayRecord['egg_count']);
        self::assertSame('Opraveno', $todayRecord['note']);
        self::assertCount(2, $recent);
        self::assertSame(16, EggRecord::getTotalEggs());
        self::assertSame(5.3, EggRecord::getDailyAverage());
        self::assertNotEmpty($monthly);
        self::assertGreaterThanOrEqual(9, array_sum(array_map(
            static fn(array $row): int => (int) $row['egg_count'],
            $monthly
        )));
    }
}

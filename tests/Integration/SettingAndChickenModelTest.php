<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\Chicken;
use App\Models\Setting;
use Tests\Support\DatabaseTestCase;

final class SettingAndChickenModelTest extends DatabaseTestCase
{
    public function testSettingCanBeUpdatedAndFetchedAsMap(): void
    {
        Setting::set('locale_name', 'Kurníkov');
        Setting::set('new_setting', '42');

        self::assertSame('Kurníkov', Setting::get('locale_name'));
        self::assertSame('42', Setting::get('new_setting'));
        self::assertArrayHasKey('egg_market_price', Setting::getAll());
    }

    public function testChickenCountsAndLabelsReflectStoredStatuses(): void
    {
        Chicken::insert(['name' => 'Běla', 'status' => 'active']);
        Chicken::insert(['name' => 'Kropenka', 'status' => 'sick']);
        Chicken::insert(['name' => 'Róza', 'status' => 'given_away']);

        self::assertSame(
            ['total' => 3, 'active' => 1, 'sick' => 1],
            Chicken::getCount()
        );
        self::assertSame('Nemocná', Chicken::statusLabel('sick'));
        self::assertSame('#60c7ff', Chicken::statusColor('given_away'));
    }
}

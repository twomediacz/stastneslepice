<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\BeddingChange;
use App\Models\Note;
use App\Models\Repair;
use App\Models\TextSnippet;
use Tests\Support\DatabaseTestCase;

final class ContentAndMaintenanceModelTest extends DatabaseTestCase
{
    public function testNotesAreReturnedInNewestFirstOrder(): void
    {
        Note::add(date('Y-m-d', strtotime('-2 days')), 'Starší');
        Note::add(date('Y-m-d'), 'Nejnovější');

        $notes = Note::getRecent(10);

        self::assertCount(2, $notes);
        self::assertSame('Nejnovější', $notes[0]['content']);
        self::assertSame('Starší', $notes[1]['content']);
    }

    public function testTextSnippetsCanBeListedAndRandomSnippetRespectsType(): void
    {
        TextSnippet::add('joke', 'Vtip jedna');
        TextSnippet::add('tip', 'Tip jedna');

        $jokes = TextSnippet::getAllByType('joke', 10);
        $randomTip = TextSnippet::getRandom('tip');

        self::assertCount(1, $jokes);
        self::assertSame('Vtip jedna', $jokes[0]['content']);
        self::assertSame('tip', $randomTip['type']);
        self::assertSame('Tip jedna', $randomTip['content']);
    }

    public function testMaintenanceModelsReturnLatestAndOrderedRecords(): void
    {
        BeddingChange::insert(['changed_at' => date('Y-m-d H:i:s', strtotime('-7 days')), 'note' => 'Minule']);
        BeddingChange::insert(['changed_at' => date('Y-m-d H:i:s'), 'note' => 'Dnes']);
        Repair::insert(['repaired_at' => date('Y-m-d H:i:s', strtotime('-1 day')), 'note' => 'Plot']);
        Repair::insert(['repaired_at' => date('Y-m-d H:i:s'), 'note' => 'Dvířka']);

        $latestBedding = BeddingChange::getLatest();
        $repairs = Repair::getAll();

        self::assertSame('Dnes', $latestBedding['note']);
        self::assertCount(2, BeddingChange::getAll());
        self::assertCount(2, $repairs);
        self::assertSame('Dvířka', $repairs[0]['note']);
    }
}

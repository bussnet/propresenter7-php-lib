<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\Group;
use ProPresenter\Parser\Song;
use Rv\Data\Presentation;

class SongTest extends TestCase
{
    private static ?Presentation $presentation = null;

    public static function setUpBeforeClass(): void
    {
        $data = file_get_contents(dirname(__DIR__) . '/doc/reference_samples/Test.pro');
        self::$presentation = new Presentation();
        self::$presentation->mergeFromString($data);
    }

    #[Test]
    public function getUuidAndGetPresentationExposeUnderlyingPresentation(): void
    {
        $song = new Song(self::$presentation);

        $this->assertNotSame('', $song->getUuid());
        $this->assertSame(self::$presentation, $song->getPresentation());
    }

    #[Test]
    public function getNameAndSetNameReadAndMutatePresentationName(): void
    {
        $presentation = clone self::$presentation;
        $song = new Song($presentation);

        $this->assertSame('Test', $song->getName());

        $song->setName('Renamed Song');

        $this->assertSame('Renamed Song', $song->getName());
        $this->assertSame('Renamed Song', $presentation->getName());
    }

    #[Test]
    public function getGroupsAndGetGroupByNameReturnExpectedGroups(): void
    {
        $song = new Song(self::$presentation);

        $groups = $song->getGroups();
        $this->assertCount(4, $groups);
        $this->assertSame(['Verse 1', 'Verse 2', 'Chorus', 'Ending'], array_map(fn (Group $group) => $group->getName(), $groups));

        $this->assertNull($song->getGroupByName('Missing Group'));
        $this->assertSame('Verse 1', $song->getGroupByName('Verse 1')?->getName());
    }

    #[Test]
    public function getSlidesAndGetSlideByUuidReturnExpectedSlides(): void
    {
        $song = new Song(self::$presentation);

        $slides = $song->getSlides();
        $this->assertCount(5, $slides);
        $this->assertSame('Vers1.1' . "\n" . 'Vers1.2', $song->getSlideByUuid('5A6AF946-30B0-4F40-BE7A-C6429C32868A')?->getPlainText());
        $this->assertNull($song->getSlideByUuid('00000000-0000-0000-0000-000000000000'));
    }

    #[Test]
    public function getArrangementsAndGetArrangementByNameReturnExpectedArrangements(): void
    {
        $song = new Song(self::$presentation);

        $arrangements = $song->getArrangements();
        $this->assertCount(2, $arrangements);
        $this->assertSame(['normal', 'test2'], array_map(fn ($arrangement) => $arrangement->getName(), $arrangements));

        $this->assertNull($song->getArrangementByName('missing'));
        $this->assertSame('normal', $song->getArrangementByName('normal')?->getName());
    }

    #[Test]
    public function getSlidesForGroupResolvesSlideUuidsInOrder(): void
    {
        $song = new Song(self::$presentation);
        $verse1 = $song->getGroupByName('Verse 1');

        $this->assertNotNull($verse1);

        $slides = $song->getSlidesForGroup($verse1);

        $this->assertCount(2, $slides);
        $this->assertSame('5A6AF946-30B0-4F40-BE7A-C6429C32868A', $slides[0]->getUuid());
        $this->assertSame('A18EF896-F83A-44CE-AEFB-5AE8969A9653', $slides[1]->getUuid());
        $this->assertSame('Vers1.1' . "\n" . 'Vers1.2', $slides[0]->getPlainText());
        $this->assertSame('Vers1.3' . "\n" . 'Vers1.4', $slides[1]->getPlainText());
    }

    #[Test]
    public function getGroupsForArrangementResolvesGroupUuidsIncludingDuplicates(): void
    {
        $song = new Song(self::$presentation);
        $normal = $song->getArrangementByName('normal');

        $this->assertNotNull($normal);

        $groups = $song->getGroupsForArrangement($normal);

        $this->assertSame(
            ['Chorus', 'Verse 1', 'Chorus', 'Verse 2', 'Chorus'],
            array_map(fn (Group $group) => $group->getName(), $groups)
        );
    }
}

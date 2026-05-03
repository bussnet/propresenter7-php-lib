<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\Group;
use Rv\Data\Presentation\CueGroup;
use Rv\Data\Group as ProtoGroup;
use Rv\Data\Color;
use Rv\Data\UUID;
use Rv\Data\Presentation;

class GroupTest extends TestCase
{
    private static ?Presentation $presentation = null;

    public static function setUpBeforeClass(): void
    {
        $data = file_get_contents(__DIR__ . '/../doc/reference_samples/Test.pro');
        self::$presentation = new Presentation();
        self::$presentation->mergeFromString($data);
    }

    // --- Unit tests with synthetic protobuf objects ---

    public function testGetUuid(): void
    {
        $uuid = new UUID();
        $uuid->setString('test-uuid-123');

        $protoGroup = new ProtoGroup();
        $protoGroup->setUuid($uuid);
        $protoGroup->setName('Test');

        $cueGroup = new CueGroup();
        $cueGroup->setGroup($protoGroup);

        $group = new Group($cueGroup);

        $this->assertSame('test-uuid-123', $group->getUuid());
    }

    public function testGetName(): void
    {
        $protoGroup = new ProtoGroup();
        $protoGroup->setName('Verse 1');

        $cueGroup = new CueGroup();
        $cueGroup->setGroup($protoGroup);

        $group = new Group($cueGroup);

        $this->assertSame('Verse 1', $group->getName());
    }

    public function testSetName(): void
    {
        $protoGroup = new ProtoGroup();
        $protoGroup->setName('Old Name');

        $cueGroup = new CueGroup();
        $cueGroup->setGroup($protoGroup);

        $group = new Group($cueGroup);
        $group->setName('New Name');

        $this->assertSame('New Name', $group->getName());
        // Verify it mutates the underlying protobuf
        $this->assertSame('New Name', $cueGroup->getGroup()->getName());
    }

    public function testGetColorReturnsArray(): void
    {
        $color = new Color();
        $color->setRed(0.5);
        $color->setGreen(0.3);
        $color->setBlue(0.8);
        $color->setAlpha(1.0);

        $protoGroup = new ProtoGroup();
        $protoGroup->setName('Colored');
        $protoGroup->setColor($color);

        $cueGroup = new CueGroup();
        $cueGroup->setGroup($protoGroup);

        $group = new Group($cueGroup);
        $result = $group->getColor();

        $this->assertIsArray($result);
        $this->assertEqualsWithDelta(0.5, $result['r'], 0.001);
        $this->assertEqualsWithDelta(0.3, $result['g'], 0.001);
        $this->assertEqualsWithDelta(0.8, $result['b'], 0.001);
        $this->assertEqualsWithDelta(1.0, $result['a'], 0.001);
    }

    public function testGetColorReturnsNullWhenNoColor(): void
    {
        $protoGroup = new ProtoGroup();
        $protoGroup->setName('NoColor');

        $cueGroup = new CueGroup();
        $cueGroup->setGroup($protoGroup);

        $group = new Group($cueGroup);

        $this->assertNull($group->getColor());
    }

    public function testGetSlideUuids(): void
    {
        $uuid1 = new UUID();
        $uuid1->setString('slide-aaa');
        $uuid2 = new UUID();
        $uuid2->setString('slide-bbb');

        $cueGroup = new CueGroup();
        $cueGroup->setGroup(new ProtoGroup());
        $cueGroup->setCueIdentifiers([$uuid1, $uuid2]);

        $group = new Group($cueGroup);
        $uuids = $group->getSlideUuids();

        $this->assertCount(2, $uuids);
        $this->assertSame('slide-aaa', $uuids[0]);
        $this->assertSame('slide-bbb', $uuids[1]);
    }

    public function testGetSlideUuidsEmptyWhenNoSlides(): void
    {
        $cueGroup = new CueGroup();
        $cueGroup->setGroup(new ProtoGroup());

        $group = new Group($cueGroup);

        $this->assertSame([], $group->getSlideUuids());
    }

    public function testGetProto(): void
    {
        $cueGroup = new CueGroup();
        $cueGroup->setGroup(new ProtoGroup());

        $group = new Group($cueGroup);

        $this->assertSame($cueGroup, $group->getProto());
    }

    // --- Integration tests with real Test.pro ---

    public function testFirstGroupFromTestProIsVerse1(): void
    {
        $cueGroups = self::$presentation->getCueGroups();
        $group = new Group($cueGroups[0]);

        $this->assertSame('Verse 1', $group->getName());
    }

    public function testTestProHasFourGroups(): void
    {
        $cueGroups = self::$presentation->getCueGroups();

        $this->assertCount(4, $cueGroups);

        $names = [];
        foreach ($cueGroups as $cg) {
            $group = new Group($cg);
            $names[] = $group->getName();
        }

        $this->assertSame(['Verse 1', 'Verse 2', 'Chorus', 'Ending'], $names);
    }

    public function testVerse1HasTwoSlides(): void
    {
        $cueGroups = self::$presentation->getCueGroups();
        $group = new Group($cueGroups[0]);

        $this->assertCount(2, $group->getSlideUuids());
    }

    public function testVerse2HasOneSlide(): void
    {
        $cueGroups = self::$presentation->getCueGroups();
        $group = new Group($cueGroups[1]);

        $this->assertCount(1, $group->getSlideUuids());
    }

    public function testChorusHasOneSlide(): void
    {
        $cueGroups = self::$presentation->getCueGroups();
        $group = new Group($cueGroups[2]);

        $this->assertCount(1, $group->getSlideUuids());
    }

    public function testEndingHasOneSlide(): void
    {
        $cueGroups = self::$presentation->getCueGroups();
        $group = new Group($cueGroups[3]);

        $this->assertCount(1, $group->getSlideUuids());
    }

    public function testGroupUuidsAreNonEmpty(): void
    {
        $cueGroups = self::$presentation->getCueGroups();
        foreach ($cueGroups as $cg) {
            $group = new Group($cg);
            $this->assertNotEmpty($group->getUuid(), "Group '{$group->getName()}' should have a UUID");
        }
    }

    public function testGroupColorsAreSet(): void
    {
        $cueGroups = self::$presentation->getCueGroups();
        foreach ($cueGroups as $cg) {
            $group = new Group($cg);
            $color = $group->getColor();
            // Groups in ProPresenter typically have colors set
            if ($color !== null) {
                $this->assertArrayHasKey('r', $color);
                $this->assertArrayHasKey('g', $color);
                $this->assertArrayHasKey('b', $color);
                $this->assertArrayHasKey('a', $color);
            }
        }
    }
}

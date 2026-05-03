<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\Arrangement;
use Rv\Data\Presentation\Arrangement as ProtoArrangement;
use Rv\Data\UUID;
use Rv\Data\Presentation;

class ArrangementTest extends TestCase
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
        $uuid->setString('arr-uuid-456');

        $proto = new ProtoArrangement();
        $proto->setUuid($uuid);
        $proto->setName('Test Arrangement');

        $arrangement = new Arrangement($proto);

        $this->assertSame('arr-uuid-456', $arrangement->getUuid());
    }

    public function testGetName(): void
    {
        $proto = new ProtoArrangement();
        $proto->setName('normal');

        $arrangement = new Arrangement($proto);

        $this->assertSame('normal', $arrangement->getName());
    }

    public function testSetName(): void
    {
        $proto = new ProtoArrangement();
        $proto->setName('old');

        $arrangement = new Arrangement($proto);
        $arrangement->setName('renamed');

        $this->assertSame('renamed', $arrangement->getName());
        // Verify mutation on underlying protobuf
        $this->assertSame('renamed', $proto->getName());
    }

    public function testGetGroupUuids(): void
    {
        $uuid1 = new UUID();
        $uuid1->setString('group-aaa');
        $uuid2 = new UUID();
        $uuid2->setString('group-bbb');
        $uuid3 = new UUID();
        $uuid3->setString('group-ccc');

        $proto = new ProtoArrangement();
        $proto->setName('test');
        $proto->setGroupIdentifiers([$uuid1, $uuid2, $uuid3]);

        $arrangement = new Arrangement($proto);
        $uuids = $arrangement->getGroupUuids();

        $this->assertCount(3, $uuids);
        $this->assertSame('group-aaa', $uuids[0]);
        $this->assertSame('group-bbb', $uuids[1]);
        $this->assertSame('group-ccc', $uuids[2]);
    }

    public function testGetGroupUuidsEmptyWhenNone(): void
    {
        $proto = new ProtoArrangement();
        $proto->setName('empty');

        $arrangement = new Arrangement($proto);

        $this->assertSame([], $arrangement->getGroupUuids());
    }

    public function testSetGroupUuids(): void
    {
        $proto = new ProtoArrangement();
        $proto->setName('test');

        $arrangement = new Arrangement($proto);
        $arrangement->setGroupUuids(['uuid-1', 'uuid-2']);

        $result = $arrangement->getGroupUuids();
        $this->assertCount(2, $result);
        $this->assertSame('uuid-1', $result[0]);
        $this->assertSame('uuid-2', $result[1]);

        // Verify underlying protobuf was updated
        $identifiers = $proto->getGroupIdentifiers();
        $this->assertCount(2, $identifiers);
        $this->assertSame('uuid-1', $identifiers[0]->getString());
        $this->assertSame('uuid-2', $identifiers[1]->getString());
    }

    public function testSetGroupUuidsReplacesExisting(): void
    {
        $uuid1 = new UUID();
        $uuid1->setString('old-1');

        $proto = new ProtoArrangement();
        $proto->setName('test');
        $proto->setGroupIdentifiers([$uuid1]);

        $arrangement = new Arrangement($proto);
        $arrangement->setGroupUuids(['new-1', 'new-2', 'new-3']);

        $this->assertCount(3, $arrangement->getGroupUuids());
        $this->assertSame('new-1', $arrangement->getGroupUuids()[0]);
    }

    public function testGetProto(): void
    {
        $proto = new ProtoArrangement();
        $proto->setName('test');

        $arrangement = new Arrangement($proto);

        $this->assertSame($proto, $arrangement->getProto());
    }

    // --- Integration tests with real Test.pro ---

    public function testTestProHasTwoArrangements(): void
    {
        $arrangements = self::$presentation->getArrangements();

        $this->assertCount(2, $arrangements);
    }

    public function testNormalArrangementExists(): void
    {
        $arrangements = self::$presentation->getArrangements();
        $names = [];
        foreach ($arrangements as $arr) {
            $arrangement = new Arrangement($arr);
            $names[] = $arrangement->getName();
        }

        $this->assertContains('normal', $names);
        $this->assertContains('test2', $names);
    }

    public function testNormalArrangementHasFiveGroupRefs(): void
    {
        $arrangements = self::$presentation->getArrangements();

        // Find "normal" arrangement
        $normalArr = null;
        foreach ($arrangements as $arr) {
            $arrangement = new Arrangement($arr);
            if ($arrangement->getName() === 'normal') {
                $normalArr = $arrangement;
                break;
            }
        }

        $this->assertNotNull($normalArr, 'Normal arrangement should exist');
        $this->assertCount(5, $normalArr->getGroupUuids(), 'Normal arrangement should have 5 group refs');
    }

    public function testTest2ArrangementHasFourGroupRefs(): void
    {
        $arrangements = self::$presentation->getArrangements();

        $test2Arr = null;
        foreach ($arrangements as $arr) {
            $arrangement = new Arrangement($arr);
            if ($arrangement->getName() === 'test2') {
                $test2Arr = $arrangement;
                break;
            }
        }

        $this->assertNotNull($test2Arr, 'test2 arrangement should exist');
        $this->assertCount(4, $test2Arr->getGroupUuids(), 'test2 arrangement should have 4 group refs');
    }

    public function testArrangementUuidsAreNonEmpty(): void
    {
        $arrangements = self::$presentation->getArrangements();
        foreach ($arrangements as $arr) {
            $arrangement = new Arrangement($arr);
            $this->assertNotEmpty($arrangement->getUuid(), "Arrangement '{$arrangement->getName()}' should have a UUID");
        }
    }

    public function testNormalArrangementGroupUuidsMatchKnownGroups(): void
    {
        // Get all group UUIDs from cue_groups
        $groupUuidSet = [];
        foreach (self::$presentation->getCueGroups() as $cg) {
            $groupUuidSet[] = $cg->getGroup()->getUuid()->getString();
        }

        // Get normal arrangement
        $arrangements = self::$presentation->getArrangements();
        $normalArr = null;
        foreach ($arrangements as $arr) {
            $arrangement = new Arrangement($arr);
            if ($arrangement->getName() === 'normal') {
                $normalArr = $arrangement;
                break;
            }
        }

        // Every group UUID in the arrangement should reference an existing group
        foreach ($normalArr->getGroupUuids() as $uuid) {
            $this->assertContains($uuid, $groupUuidSet, "Arrangement group UUID '$uuid' should reference an existing group");
        }
    }
}

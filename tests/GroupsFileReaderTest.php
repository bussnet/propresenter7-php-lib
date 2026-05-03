<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\GroupDefinition;
use ProPresenter\Parser\GroupLibrary;
use ProPresenter\Parser\GroupsFileReader;
use ProPresenter\Parser\GroupsFileWriter;

class GroupsFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/Groups';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GroupsFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-groups');
    }

    #[Test]
    public function readReturnsLibraryWithExpectedCount(): void
    {
        $library = GroupsFileReader::read(self::REFERENCE_PATH);

        $this->assertInstanceOf(GroupLibrary::class, $library);
        $this->assertCount(29, $library->getGroups());
        $this->assertSame(29, $library->count());
    }

    #[Test]
    public function groupsExposeNameAndUuid(): void
    {
        $library = GroupsFileReader::read(self::REFERENCE_PATH);
        $first = $library->getGroups()[0];

        $this->assertInstanceOf(GroupDefinition::class, $first);
        $this->assertSame('Vers', $first->getName());
        $this->assertSame('4E9D56A2-7E96-4975-97CC-44982257EF8A', $first->getUuid());
    }

    #[Test]
    public function lookupByUuidIsCaseInsensitive(): void
    {
        $library = GroupsFileReader::read(self::REFERENCE_PATH);

        $upper = $library->getGroupByUuid('4E9D56A2-7E96-4975-97CC-44982257EF8A');
        $lower = $library->getGroupByUuid('4e9d56a2-7e96-4975-97cc-44982257ef8a');

        $this->assertNotNull($upper);
        $this->assertSame($upper, $lower);
        $this->assertSame('Vers', $upper->getName());
    }

    #[Test]
    public function lookupByNameSucceeds(): void
    {
        $library = GroupsFileReader::read(self::REFERENCE_PATH);

        $verse1 = $library->getGroupByName('Verse 1');
        $this->assertNotNull($verse1);
        $this->assertSame('1D85C82C-EC82-44D8-8ED0-7742D46242C0', $verse1->getUuid());
    }

    #[Test]
    public function colorIsExposedAsHex(): void
    {
        $library = GroupsFileReader::read(self::REFERENCE_PATH);

        $vers = $library->getGroupByName('Vers');
        $this->assertNotNull($vers);
        $this->assertSame('#0077CC', $vers->getColorHex());

        $color = $vers->getColor();
        $this->assertNotNull($color);
        $this->assertEqualsWithDelta(1.0, $color['a'], 0.001);
    }

    #[Test]
    public function setColorHexUpdatesProto(): void
    {
        $library = GroupsFileReader::read(self::REFERENCE_PATH);

        $vers = $library->getGroupByName('Vers');
        $this->assertNotNull($vers);

        $vers->setColor(['r' => 1.0, 'g' => 0.0, 'b' => 0.0]);
        $this->assertSame('#FF0000', $vers->getColorHex());
    }

    #[Test]
    public function addAndRemoveGroupRoundTrip(): void
    {
        $library = GroupsFileReader::read(self::REFERENCE_PATH);

        $library->addGroup('Test Group', '11111111-1111-1111-1111-111111111111');
        $this->assertNotNull($library->getGroupByUuid('11111111-1111-1111-1111-111111111111'));
        $this->assertSame(30, $library->count());

        $this->assertTrue($library->removeGroup('11111111-1111-1111-1111-111111111111'));
        $this->assertNull($library->getGroupByUuid('11111111-1111-1111-1111-111111111111'));
        $this->assertSame(29, $library->count());
    }

    #[Test]
    public function writerProducesByteIdenticalRoundTrip(): void
    {
        $original = file_get_contents(self::REFERENCE_PATH);
        $library = GroupsFileReader::read(self::REFERENCE_PATH);

        $tmp = tempnam(sys_get_temp_dir(), 'groups_');
        try {
            GroupsFileWriter::write($library, $tmp);
            $this->assertSame($original, file_get_contents($tmp));
        } finally {
            @unlink($tmp);
        }
    }
}

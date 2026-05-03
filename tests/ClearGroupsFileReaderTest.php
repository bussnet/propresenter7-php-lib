<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ClearGroupDefinition;
use ProPresenter\Parser\ClearGroupsFileReader;
use ProPresenter\Parser\ClearGroupsFileWriter;
use ProPresenter\Parser\ClearGroupsLibrary;

class ClearGroupsFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/ClearGroups';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ClearGroupsFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-clear-groups');
    }

    #[Test]
    public function readReturnsLibraryWithExpectedCount(): void
    {
        $library = ClearGroupsFileReader::read(self::REFERENCE_PATH);

        $this->assertInstanceOf(ClearGroupsLibrary::class, $library);
        $this->assertCount(1, $library->getGroups());
        $this->assertSame(1, $library->count());
    }

    #[Test]
    public function clearGroupsExposeNameAndUuid(): void
    {
        $library = ClearGroupsFileReader::read(self::REFERENCE_PATH);
        $first = $library->getGroups()[0];

        $this->assertInstanceOf(ClearGroupDefinition::class, $first);
        $this->assertSame('Alles ausblenden', $first->getName());
        $this->assertSame('A91C6AFE-098F-4559-B2CF-D8373C589589', $first->getUuid());
    }

    #[Test]
    public function lookupByUuidIsCaseInsensitive(): void
    {
        $library = ClearGroupsFileReader::read(self::REFERENCE_PATH);

        $upper = $library->getClearGroupByUuid('A91C6AFE-098F-4559-B2CF-D8373C589589');
        $lower = $library->getClearGroupByUuid('a91c6afe-098f-4559-b2cf-d8373c589589');

        $this->assertNotNull($upper);
        $this->assertSame($upper, $lower);
        $this->assertSame('Alles ausblenden', $upper->getName());
    }

    #[Test]
    public function lookupByNameSucceeds(): void
    {
        $library = ClearGroupsFileReader::read(self::REFERENCE_PATH);

        $group = $library->getClearGroupByName('Alles ausblenden');
        $this->assertNotNull($group);
        $this->assertSame('A91C6AFE-098F-4559-B2CF-D8373C589589', $group->getUuid());
    }

    #[Test]
    public function colorIsExposedAsHex(): void
    {
        $library = ClearGroupsFileReader::read(self::REFERENCE_PATH);
        $group = $library->getClearGroupByName('Alles ausblenden');

        $this->assertNotNull($group);
        $this->assertSame('#FFFFFF', $group->getColorHex());
        $this->assertFalse($group->isIconTinted());
    }

    #[Test]
    public function setColorHexUpdatesProto(): void
    {
        $library = ClearGroupsFileReader::read(self::REFERENCE_PATH);
        $group = $library->getGroups()[0];

        $group->setColor(['r' => 1.0, 'g' => 0.0, 'b' => 0.0]);
        $this->assertSame('#FF0000', $group->getColorHex());
    }

    #[Test]
    public function addAndRemoveClearGroupRoundTrip(): void
    {
        $library = ClearGroupsFileReader::read(self::REFERENCE_PATH);

        $library->addClearGroup('Test Clear', '11111111-1111-1111-1111-111111111111');
        $this->assertNotNull($library->getClearGroupByUuid('11111111-1111-1111-1111-111111111111'));
        $this->assertSame(2, $library->count());

        $this->assertTrue($library->removeClearGroup('11111111-1111-1111-1111-111111111111'));
        $this->assertNull($library->getClearGroupByUuid('11111111-1111-1111-1111-111111111111'));
        $this->assertSame(1, $library->count());
    }

    #[Test]
    public function writerProducesByteIdenticalRoundTrip(): void
    {
        $original = file_get_contents(self::REFERENCE_PATH);
        $library = ClearGroupsFileReader::read(self::REFERENCE_PATH);

        $tmp = tempnam(sys_get_temp_dir(), 'clear_groups_');
        try {
            ClearGroupsFileWriter::write($library, $tmp);
            $this->assertSame($original, file_get_contents($tmp));
        } finally {
            @unlink($tmp);
        }
    }
}

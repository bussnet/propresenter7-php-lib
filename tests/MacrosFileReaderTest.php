<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\Macro;
use ProPresenter\Parser\MacroCollection;
use ProPresenter\Parser\MacroLibrary;
use ProPresenter\Parser\MacrosFileReader;

class MacrosFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/Macros';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MacrosFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-macros');
    }

    #[Test]
    public function readReturnsMacroLibraryWithExpectedCounts(): void
    {
        $library = MacrosFileReader::read(self::REFERENCE_PATH);

        $this->assertInstanceOf(MacroLibrary::class, $library);
        $this->assertCount(24, $library->getMacros());
        $this->assertCount(3, $library->getCollections());
    }

    #[Test]
    public function macrosExposeNameAndUuid(): void
    {
        $library = MacrosFileReader::read(self::REFERENCE_PATH);

        $first = $library->getMacros()[0];
        $this->assertInstanceOf(Macro::class, $first);
        $this->assertSame('Gottesdienst START', $first->getName());
        $this->assertSame('FA0602E4-EDA2-4457-BB62-68AA17184217', $first->getUuid());
    }

    #[Test]
    public function macroLookupByUuidIsCaseInsensitive(): void
    {
        $library = MacrosFileReader::read(self::REFERENCE_PATH);

        $upper = $library->getMacroByUuid('FA0602E4-EDA2-4457-BB62-68AA17184217');
        $lower = $library->getMacroByUuid('fa0602e4-eda2-4457-bb62-68aa17184217');

        $this->assertNotNull($upper);
        $this->assertSame($upper, $lower);
        $this->assertSame('Gottesdienst START', $upper->getName());
    }

    #[Test]
    public function macroLookupByNameSucceeds(): void
    {
        $library = MacrosFileReader::read(self::REFERENCE_PATH);

        $macro = $library->getMacroByName('Predigt - Text Lang');
        $this->assertNotNull($macro);
        $this->assertSame('0A1543A9-7881-4537-982C-2933AA5472F8', $macro->getUuid());
    }

    #[Test]
    public function collectionsExposeNameUuidAndOrderedMacroUuids(): void
    {
        $library = MacrosFileReader::read(self::REFERENCE_PATH);

        $ablauf = $library->getCollectionByName('Ablauf');
        $this->assertInstanceOf(MacroCollection::class, $ablauf);
        $this->assertSame('8D02FC57-83F8-4042-9B90-81C229728426', $ablauf->getUuid());

        $uuids = $ablauf->getMacroUuids();
        $this->assertCount(12, $uuids);
        $this->assertSame('FA0602E4-EDA2-4457-BB62-68AA17184217', $uuids[0]);
        $this->assertSame('5ADFBB7A-1529-42B9-A9C6-77B7D01C4715', $uuids[11]);
    }

    #[Test]
    public function collectionLookupByUuidIsCaseInsensitive(): void
    {
        $library = MacrosFileReader::read(self::REFERENCE_PATH);

        $upper = $library->getCollectionByUuid('AD18A4F6-135F-4A52-B92B-CA6619A55A9B');
        $lower = $library->getCollectionByUuid('ad18a4f6-135f-4a52-b92b-ca6619a55a9b');

        $this->assertNotNull($upper);
        $this->assertSame($upper, $lower);
        $this->assertSame('AbsoluteTimer', $upper->getName());
    }

    #[Test]
    public function getMacrosForCollectionResolvesReferences(): void
    {
        $library = MacrosFileReader::read(self::REFERENCE_PATH);

        $absoluteTimer = $library->getCollectionByName('AbsoluteTimer');
        $this->assertNotNull($absoluteTimer);

        $macros = $library->getMacrosForCollection($absoluteTimer);
        $this->assertCount(2, $macros);
        $this->assertSame('Doors Open - 9:45', $macros[0]->getName());
        $this->assertSame('Godi START - 10:02', $macros[1]->getName());
    }

    #[Test]
    public function getCollectionsForMacroReturnsMembership(): void
    {
        $library = MacrosFileReader::read(self::REFERENCE_PATH);

        $macro = $library->getMacroByUuid('FA0602E4-EDA2-4457-BB62-68AA17184217');
        $this->assertNotNull($macro);

        $collections = $library->getCollectionsForMacro($macro);
        $this->assertCount(1, $collections);
        $this->assertSame('Ablauf', $collections[0]->getName());
    }

    #[Test]
    public function startupMacroFlagSurfaces(): void
    {
        $library = MacrosFileReader::read(self::REFERENCE_PATH);

        $startup = array_values(array_filter(
            $library->getMacros(),
            fn (Macro $m) => $m->getTriggerOnStartup(),
        ));

        $this->assertCount(1, $startup);
        $this->assertSame('Doors Open - 9:45', $startup[0]->getName());
    }
}

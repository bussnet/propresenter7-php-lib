<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\Label;
use ProPresenter\Parser\LabelLibrary;
use ProPresenter\Parser\LabelsFileReader;

class LabelsFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/Labels';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LabelsFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-labels');
    }

    #[Test]
    public function readReturnsLabelLibraryWithExpectedCount(): void
    {
        $library = LabelsFileReader::read(self::REFERENCE_PATH);

        $this->assertInstanceOf(LabelLibrary::class, $library);
        $this->assertCount(15, $library->getLabels());
        $this->assertSame(15, $library->count());
    }

    #[Test]
    public function labelsExposeNames(): void
    {
        $library = LabelsFileReader::read(self::REFERENCE_PATH);
        $labels = $library->getLabels();

        $this->assertInstanceOf(Label::class, $labels[0]);
        $this->assertSame('Leere Folie', $labels[0]->getName());
        $this->assertSame('Instrumental', $labels[1]->getName());
        $this->assertSame('KeyVisual Stream & Beamer mit Countdown', $labels[4]->getName());
        $this->assertSame('Szene 3', $labels[14]->getName());
    }

    #[Test]
    public function labelsWithoutColorReportNullColor(): void
    {
        $library = LabelsFileReader::read(self::REFERENCE_PATH);

        $leereFolie = $library->getLabelByName('Leere Folie');
        $this->assertNotNull($leereFolie);
        $this->assertFalse($leereFolie->hasColor());
        $this->assertNull($leereFolie->getColor());
        $this->assertNull($leereFolie->getColorHex());
    }

    #[Test]
    public function labelsWithColorReturnFloatChannels(): void
    {
        $library = LabelsFileReader::read(self::REFERENCE_PATH);

        $beamer = $library->getLabelByName('KeyVisual Beamer');
        $this->assertNotNull($beamer);
        $this->assertTrue($beamer->hasColor());

        $color = $beamer->getColor();
        $this->assertIsArray($color);
        $this->assertEqualsWithDelta(0.0, $color['r'], 0.001);
        $this->assertEqualsWithDelta(0.4078, $color['g'], 0.001);
        $this->assertEqualsWithDelta(0.7020, $color['b'], 0.001);
        $this->assertEqualsWithDelta(1.0, $color['a'], 0.001);
    }

    #[Test]
    public function colorHexIsSixDigitUppercase(): void
    {
        $library = LabelsFileReader::read(self::REFERENCE_PATH);

        $beamer = $library->getLabelByName('KeyVisual Beamer');
        $this->assertNotNull($beamer);
        $this->assertSame('#0068B3', $beamer->getColorHex());

        $countdown = $library->getLabelByName('KeyVisual Stream & Beamer mit Countdown');
        $this->assertNotNull($countdown);
        $this->assertSame('#CC298B', $countdown->getColorHex());

        $kill = $library->getLabelByName('Namenseinblender Kill');
        $this->assertNotNull($kill);
        $this->assertSame('#000000', $kill->getColorHex());
    }

    #[Test]
    public function getLabelByNameIsCaseSensitive(): void
    {
        $library = LabelsFileReader::read(self::REFERENCE_PATH);

        $this->assertNotNull($library->getLabelByName('Szene 1'));
        $this->assertNull($library->getLabelByName('szene 1'));
    }

    #[Test]
    public function findLabelByNameIsCaseInsensitive(): void
    {
        $library = LabelsFileReader::read(self::REFERENCE_PATH);

        $exact = $library->findLabelByName('Szene 1');
        $lower = $library->findLabelByName('szene 1');
        $upper = $library->findLabelByName('SZENE 1');

        $this->assertNotNull($exact);
        $this->assertSame($exact, $lower);
        $this->assertSame($exact, $upper);
        $this->assertSame('Szene 1', $exact->getName());
    }

    #[Test]
    public function unknownNameReturnsNull(): void
    {
        $library = LabelsFileReader::read(self::REFERENCE_PATH);

        $this->assertNull($library->getLabelByName('does-not-exist'));
        $this->assertNull($library->findLabelByName('does-not-exist'));
    }

    #[Test]
    public function documentIsExposedForRawAccess(): void
    {
        $library = LabelsFileReader::read(self::REFERENCE_PATH);

        $document = $library->getDocument();
        $this->assertCount(15, $document->getLabels());
    }
}

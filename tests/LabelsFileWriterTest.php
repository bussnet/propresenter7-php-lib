<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\LabelsFileReader;
use ProPresenter\Parser\LabelsFileWriter;

class LabelsFileWriterTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/Labels';

    #[Test]
    public function writeProducesByteIdenticalRoundTrip(): void
    {
        $original = file_get_contents(self::REFERENCE_PATH);
        $library = LabelsFileReader::read(self::REFERENCE_PATH);

        $tmp = tempnam(sys_get_temp_dir(), 'labels_');
        try {
            LabelsFileWriter::write($library, $tmp);
            $this->assertSame($original, file_get_contents($tmp));
        } finally {
            @unlink($tmp);
        }
    }

    #[Test]
    public function addLabelPersistsThroughWriteRead(): void
    {
        $library = LabelsFileReader::read(self::REFERENCE_PATH);
        $library->addLabel('CustomLabel', ['r' => 0.5, 'g' => 0.5, 'b' => 0.5, 'a' => 1.0]);

        $tmp = tempnam(sys_get_temp_dir(), 'labels_');
        try {
            LabelsFileWriter::write($library, $tmp);

            $reload = LabelsFileReader::read($tmp);
            $custom = $reload->getLabelByName('CustomLabel');
            $this->assertNotNull($custom);
            $this->assertSame('#808080', $custom->getColorHex());
        } finally {
            @unlink($tmp);
        }
    }

    #[Test]
    public function setColorHexAcceptsRrggbb(): void
    {
        $library = LabelsFileReader::read(self::REFERENCE_PATH);
        $beamer = $library->getLabelByName('KeyVisual Beamer');
        $this->assertNotNull($beamer);

        $beamer->setColorHex('#FF8800');
        $this->assertSame('#FF8800', $beamer->getColorHex());
    }

    #[Test]
    public function removeLabelRemovesFromDocument(): void
    {
        $library = LabelsFileReader::read(self::REFERENCE_PATH);
        $countBefore = $library->count();

        $this->assertTrue($library->removeLabel('Leere Folie'));
        $this->assertSame($countBefore - 1, $library->count());
        $this->assertNull($library->getLabelByName('Leere Folie'));
    }
}

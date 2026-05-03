<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\MacrosFileReader;
use ProPresenter\Parser\MacrosFileWriter;

class MacrosFileWriterTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/Macros';

    #[Test]
    public function writeProducesSameByteCount(): void
    {
        $original = file_get_contents(self::REFERENCE_PATH);
        $library = MacrosFileReader::read(self::REFERENCE_PATH);

        $tmp = tempnam(sys_get_temp_dir(), 'macros_');
        try {
            MacrosFileWriter::write($library, $tmp);
            $written = file_get_contents($tmp);
            $this->assertSame(strlen($original), strlen($written));
        } finally {
            @unlink($tmp);
        }
    }

    #[Test]
    public function readBackPreservesMacrosAndCollections(): void
    {
        $library = MacrosFileReader::read(self::REFERENCE_PATH);

        $tmp = tempnam(sys_get_temp_dir(), 'macros_');
        try {
            MacrosFileWriter::write($library, $tmp);
            $reload = MacrosFileReader::read($tmp);

            $this->assertCount(24, $reload->getMacros());
            $this->assertCount(3, $reload->getCollections());
            $this->assertNotNull($reload->getMacroByName('Gottesdienst START'));
        } finally {
            @unlink($tmp);
        }
    }

    #[Test]
    public function addMacroPersistsThroughWriteRead(): void
    {
        $library = MacrosFileReader::read(self::REFERENCE_PATH);
        $library->addMacro('TestMacro', '11111111-1111-1111-1111-111111111111');

        $tmp = tempnam(sys_get_temp_dir(), 'macros_');
        try {
            MacrosFileWriter::write($library, $tmp);
            $reload = MacrosFileReader::read($tmp);

            $found = $reload->getMacroByUuid('11111111-1111-1111-1111-111111111111');
            $this->assertNotNull($found);
            $this->assertSame('TestMacro', $found->getName());
        } finally {
            @unlink($tmp);
        }
    }

    #[Test]
    public function setColorHexUpdatesMacro(): void
    {
        $library = MacrosFileReader::read(self::REFERENCE_PATH);
        $macro = $library->getMacroByName('Gottesdienst START');
        $this->assertNotNull($macro);

        $macro->setColor(['r' => 0.5, 'g' => 0.0, 'b' => 1.0]);
        $color = $macro->getColor();
        $this->assertNotNull($color);
        $this->assertEqualsWithDelta(0.5, $color['r'], 0.001);
        $this->assertEqualsWithDelta(0.0, $color['g'], 0.001);
        $this->assertEqualsWithDelta(1.0, $color['b'], 0.001);
    }
}

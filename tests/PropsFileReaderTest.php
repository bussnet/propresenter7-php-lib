<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\Prop;
use ProPresenter\Parser\PropLibrary;
use ProPresenter\Parser\PropsFileReader;
use ProPresenter\Parser\PropsFileWriter;
use Rv\Data\Cue;

class PropsFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/Props';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PropsFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-props');
    }

    #[Test]
    public function readReturnsLibraryWithExpectedCount(): void
    {
        $library = PropsFileReader::read(self::REFERENCE_PATH);
        $this->assertInstanceOf(PropLibrary::class, $library);
        $this->assertCount(13, $library->getProps());
        $this->assertSame(13, $library->count());
    }

    #[Test]
    public function propExposesNameAndUuid(): void
    {
        $prop = PropsFileReader::read(self::REFERENCE_PATH)->getProps()[0];
        $this->assertInstanceOf(Prop::class, $prop);
        $this->assertSame('Props #1', $prop->getName());
        $this->assertSame('1FB23674-4341-4257-A376-E7E7318E84EF', $prop->getUuid());
        $this->assertTrue($prop->isEnabled());
    }

    #[Test]
    public function lookupByUuidIsCaseInsensitive(): void
    {
        $library = PropsFileReader::read(self::REFERENCE_PATH);
        $upper = $library->getPropByUuid('1FB23674-4341-4257-A376-E7E7318E84EF');
        $lower = $library->getPropByUuid('1fb23674-4341-4257-a376-e7e7318e84ef');
        $this->assertNotNull($upper);
        $this->assertSame($upper, $lower);
    }

    #[Test]
    public function writerProducesStableRoundTrip(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'props_');
        $second = tempnam(sys_get_temp_dir(), 'props_');
        try {
            PropsFileWriter::write(PropsFileReader::read(self::REFERENCE_PATH), $tmp);
            PropsFileWriter::write(PropsFileReader::read($tmp), $second);
            $this->assertSame(file_get_contents($tmp), file_get_contents($second));
        } finally {
            @unlink($tmp);
            @unlink($second);
        }
    }

    #[Test]
    public function addAndRemovePropRoundTrip(): void
    {
        $library = PropsFileReader::read(self::REFERENCE_PATH);
        $prop = new Prop(new Cue());
        $prop->setName('Test Prop')->setUuid('11111111-1111-1111-1111-111111111111')->setEnabled(true);
        $library->addProp($prop);
        $this->assertSame(14, $library->count());
        $this->assertSame('Test Prop', $library->getPropByUuid('11111111-1111-1111-1111-111111111111')?->getName());
        $this->assertTrue($library->removeProp('11111111-1111-1111-1111-111111111111'));
        $this->assertSame(13, $library->count());
    }
}

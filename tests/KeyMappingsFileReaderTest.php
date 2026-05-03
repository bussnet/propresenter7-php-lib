<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\KeyMapping;
use ProPresenter\Parser\KeyMappingsFileReader;
use ProPresenter\Parser\KeyMappingsFileWriter;
use ProPresenter\Parser\KeyMappingsLibrary;

class KeyMappingsFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/KeyMappings';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        KeyMappingsFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-key-mappings');
    }

    #[Test]
    public function readReturnsLibraryWithExpectedCount(): void
    {
        $library = KeyMappingsFileReader::read(self::REFERENCE_PATH);

        $this->assertInstanceOf(KeyMappingsLibrary::class, $library);
        $this->assertCount(0, $library->getMappings());
        $this->assertSame(0, $library->count());
        $this->assertNotNull($library->getApplicationInfo());
    }

    #[Test]
    public function mappingsExposeNameAndUuid(): void
    {
        $library = KeyMappingsFileReader::read(self::REFERENCE_PATH);
        $mapping = $library->addMapping('Test Mapping', 'ABCDEFAB-1111-1111-1111-111111111111', 'target');

        $this->assertInstanceOf(KeyMapping::class, $mapping);
        $this->assertSame('Test Mapping', $mapping->getName());
        $this->assertSame('ABCDEFAB-1111-1111-1111-111111111111', $mapping->getUuid());
        $this->assertSame('target', $mapping->getTarget());
    }

    #[Test]
    public function lookupByUuidIsCaseInsensitive(): void
    {
        $library = KeyMappingsFileReader::read(self::REFERENCE_PATH);

        $library->addMapping('Test Mapping', 'ABCDEFAB-1111-1111-1111-111111111111');
        $upper = $library->getMappingByUuid('ABCDEFAB-1111-1111-1111-111111111111');
        $lower = $library->getMappingByUuid('abcdefab-1111-1111-1111-111111111111');

        $this->assertNotNull($upper);
        $this->assertSame($upper, $lower);
        $this->assertSame('Test Mapping', $upper->getName());
    }

    #[Test]
    public function lookupByNameSucceeds(): void
    {
        $library = KeyMappingsFileReader::read(self::REFERENCE_PATH);

        $library->addMapping('Test Mapping', 'ABCDEFAB-1111-1111-1111-111111111111');
        $mapping = $library->getMappingByName('Test Mapping');

        $this->assertNotNull($mapping);
        $this->assertSame('ABCDEFAB-1111-1111-1111-111111111111', $mapping->getUuid());
    }

    #[Test]
    public function addAndRemoveMappingRoundTrip(): void
    {
        $library = KeyMappingsFileReader::read(self::REFERENCE_PATH);

        $library->addMapping('Test Mapping', 'ABCDEFAB-1111-1111-1111-111111111111');
        $this->assertNotNull($library->getMappingByUuid('ABCDEFAB-1111-1111-1111-111111111111'));
        $this->assertSame(1, $library->count());

        $this->assertTrue($library->removeMapping('abcdefab-1111-1111-1111-111111111111'));
        $this->assertNull($library->getMappingByUuid('ABCDEFAB-1111-1111-1111-111111111111'));
        $this->assertSame(0, $library->count());
    }

    #[Test]
    public function writerProducesByteIdenticalRoundTrip(): void
    {
        $original = file_get_contents(self::REFERENCE_PATH);
        $library = KeyMappingsFileReader::read(self::REFERENCE_PATH);

        $tmp = tempnam(sys_get_temp_dir(), 'key_mappings_');
        try {
            KeyMappingsFileWriter::write($library, $tmp);
            $this->assertSame($original, file_get_contents($tmp));
        } finally {
            @unlink($tmp);
        }
    }
}

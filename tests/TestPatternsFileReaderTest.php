<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\TestPatternsFileReader;
use ProPresenter\Parser\TestPatternsFileWriter;
use ProPresenter\Parser\TestPatternsLibrary;

class TestPatternsFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/TestPatterns';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TestPatternsFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-test-patterns');
    }

    #[Test]
    public function readReturnsLibraryWithExpectedCount(): void
    {
        $library = TestPatternsFileReader::read(self::REFERENCE_PATH);

        $this->assertInstanceOf(TestPatternsLibrary::class, $library);
        $this->assertCount(0, $library->getPatterns());
        $this->assertSame(0, $library->count());
    }

    #[Test]
    public function stateExposesDisplayLocationAndSpecificScreenUuid(): void
    {
        $library = TestPatternsFileReader::read(self::REFERENCE_PATH);

        $this->assertNotNull($library->getState());
        $this->assertSame(3, $library->getDisplayLocation());
        $this->assertSame('BCDE1115-AD40-4BA4-A33A-BFFE3E87223B', $library->getSpecificScreenUuid());
    }

    #[Test]
    public function lookupByUuidIsCaseInsensitive(): void
    {
        $library = TestPatternsFileReader::read(self::REFERENCE_PATH);

        $library->addPattern('Test Pattern', 'ABCDEFAB-1111-1111-1111-111111111111');
        $upper = $library->getPatternByUuid('ABCDEFAB-1111-1111-1111-111111111111');
        $lower = $library->getPatternByUuid('abcdefab-1111-1111-1111-111111111111');

        $this->assertNotNull($upper);
        $this->assertSame($upper, $lower);
        $this->assertSame('Test Pattern', $upper->getNameLocalizationKey());
    }

    #[Test]
    public function lookupByNameSucceeds(): void
    {
        $library = TestPatternsFileReader::read(self::REFERENCE_PATH);

        $library->addPattern('Test Pattern', '11111111-1111-1111-1111-111111111111');
        $pattern = $library->getPatternByName('Test Pattern');

        $this->assertNotNull($pattern);
        $this->assertSame('11111111-1111-1111-1111-111111111111', $pattern->getUuid()?->getString());
    }

    #[Test]
    public function addAndRemovePatternRoundTrip(): void
    {
        $library = TestPatternsFileReader::read(self::REFERENCE_PATH);

        $library->addPattern('Test Pattern', '11111111-1111-1111-1111-111111111111');
        $this->assertNotNull($library->getPatternByUuid('11111111-1111-1111-1111-111111111111'));
        $this->assertSame(1, $library->count());

        $this->assertTrue($library->removePattern('11111111-1111-1111-1111-111111111111'));
        $this->assertNull($library->getPatternByUuid('11111111-1111-1111-1111-111111111111'));
        $this->assertSame(0, $library->count());
    }

    #[Test]
    public function writerProducesByteIdenticalRoundTrip(): void
    {
        $original = file_get_contents(self::REFERENCE_PATH);
        $library = TestPatternsFileReader::read(self::REFERENCE_PATH);

        $tmp = tempnam(sys_get_temp_dir(), 'test_patterns_');
        try {
            TestPatternsFileWriter::write($library, $tmp);
            $this->assertSame($original, file_get_contents($tmp));
        } finally {
            @unlink($tmp);
        }
    }
}

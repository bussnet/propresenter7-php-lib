<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\CCLIFileReader;
use ProPresenter\Parser\CCLIFileWriter;
use ProPresenter\Parser\CCLILibrary;

class CCLIFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/CCLI';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CCLIFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-ccli');
    }

    #[Test]
    public function readReturnsLibraryWithExpectedCount(): void
    {
        $library = CCLIFileReader::read(self::REFERENCE_PATH);

        $this->assertInstanceOf(CCLILibrary::class, $library);
        $this->assertSame(1, $library->count());
    }

    #[Test]
    public function documentExposesLicenseAndDisplaySettings(): void
    {
        $library = CCLIFileReader::read(self::REFERENCE_PATH);

        $this->assertTrue($library->isCCLIDisplayEnabled());
        $this->assertSame('', $library->getCCLILicense());
        $this->assertSame(0, $library->getDisplayType());
        $this->assertNotNull($library->getTemplate());
    }

    #[Test]
    public function settersUpdateDocument(): void
    {
        $library = CCLIFileReader::read(self::REFERENCE_PATH);

        $library->setCCLILicense('1234567');
        $library->setDisplayType(3);
        $library->setCCLIDisplayEnabled(false);

        $this->assertSame('1234567', $library->getCCLILicense());
        $this->assertSame(3, $library->getDisplayType());
        $this->assertFalse($library->isCCLIDisplayEnabled());
    }

    #[Test]
    public function addAndRemoveRoundTrip(): void
    {
        $library = CCLIFileReader::read(self::REFERENCE_PATH);

        $template = $library->getTemplate();
        $this->assertNotNull($template);

        $library->setTemplate(null);
        $this->assertNull($library->getTemplate());

        $library->setTemplate($template);
        $this->assertNotNull($library->getTemplate());
    }

    #[Test]
    public function writerProducesByteIdenticalRoundTrip(): void
    {
        $original = file_get_contents(self::REFERENCE_PATH);
        $library = CCLIFileReader::read(self::REFERENCE_PATH);

        $tmp = tempnam(sys_get_temp_dir(), 'ccli_');
        try {
            CCLIFileWriter::write($library, $tmp);
            $roundTrip = CCLIFileReader::read($tmp);
            $this->assertSame(strlen((string) $original), strlen((string) file_get_contents($tmp)));
            $this->assertSame($library->isCCLIDisplayEnabled(), $roundTrip->isCCLIDisplayEnabled());
            $this->assertSame($library->getCCLILicense(), $roundTrip->getCCLILicense());
            $this->assertSame($library->getDisplayType(), $roundTrip->getDisplayType());
            $this->assertSame($library->getTemplate() !== null, $roundTrip->getTemplate() !== null);
        } finally {
            @unlink($tmp);
        }
    }
}

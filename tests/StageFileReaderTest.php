<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\StageFileReader;
use ProPresenter\Parser\StageFileWriter;
use ProPresenter\Parser\StageLayout;
use ProPresenter\Parser\StageLibrary;
use Rv\Data\Stage\Layout as LayoutProto;

class StageFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/Stage';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StageFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-stage');
    }

    #[Test]
    public function readReturnsLibraryWithExpectedCount(): void
    {
        $library = StageFileReader::read(self::REFERENCE_PATH);
        $this->assertInstanceOf(StageLibrary::class, $library);
        $this->assertCount(12, $library->getLayouts());
        $this->assertSame(12, $library->count());
    }

    #[Test]
    public function layoutExposesNameAndUuid(): void
    {
        $layout = StageFileReader::read(self::REFERENCE_PATH)->getLayouts()[0];
        $this->assertInstanceOf(StageLayout::class, $layout);
        $this->assertSame('Default StageDisplay', $layout->getName());
        $this->assertSame('0455674A-3F5C-4A62-B944-C276F3DF6F4E', $layout->getUuid());
        $this->assertNotNull($layout->getSlide());
    }

    #[Test]
    public function lookupByUuidIsCaseInsensitive(): void
    {
        $library = StageFileReader::read(self::REFERENCE_PATH);
        $upper = $library->getLayoutByUuid('0455674A-3F5C-4A62-B944-C276F3DF6F4E');
        $lower = $library->getLayoutByUuid('0455674a-3f5c-4a62-b944-c276f3df6f4e');
        $this->assertNotNull($upper);
        $this->assertSame($upper, $lower);
    }

    #[Test]
    public function writerProducesStableRoundTrip(): void
    {
        $library = StageFileReader::read(self::REFERENCE_PATH);
        $tmp = tempnam(sys_get_temp_dir(), 'stage_');
        $second = tempnam(sys_get_temp_dir(), 'stage_');
        try {
            StageFileWriter::write($library, $tmp);
            StageFileWriter::write(StageFileReader::read($tmp), $second);
            $this->assertSame(file_get_contents($tmp), file_get_contents($second));
        } finally {
            @unlink($tmp);
            @unlink($second);
        }
    }

    #[Test]
    public function addAndRemoveLayoutRoundTrip(): void
    {
        $library = StageFileReader::read(self::REFERENCE_PATH);
        $layout = new StageLayout(new LayoutProto());
        $layout->setName('Test Layout')->setUuid('11111111-1111-1111-1111-111111111111');

        $library->addLayout($layout);
        $this->assertSame(13, $library->count());
        $this->assertSame('Test Layout', $library->getLayoutByUuid('11111111-1111-1111-1111-111111111111')?->getName());

        $this->assertTrue($library->removeLayout('11111111-1111-1111-1111-111111111111'));
        $this->assertSame(12, $library->count());
    }
}

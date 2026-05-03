<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\Screen;
use ProPresenter\Parser\WorkspaceFileReader;
use ProPresenter\Parser\WorkspaceFileWriter;
use ProPresenter\Parser\WorkspaceLibrary;
use Rv\Data\ProPresenterScreen;

class WorkspaceFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/Workspace';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        WorkspaceFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-workspace');
    }

    #[Test]
    public function readReturnsLibraryWithExpectedCount(): void
    {
        $library = WorkspaceFileReader::read(self::REFERENCE_PATH);
        $this->assertInstanceOf(WorkspaceLibrary::class, $library);
        $this->assertCount(5, $library->getScreens());
        $this->assertSame(5, $library->count());
    }

    #[Test]
    public function screenExposesNameAndUuid(): void
    {
        $screen = WorkspaceFileReader::read(self::REFERENCE_PATH)->getScreens()[0];
        $this->assertInstanceOf(Screen::class, $screen);
        $this->assertSame('StageDisplay', $screen->getName());
        $this->assertSame('C86D614D-9441-4F78-A177-03E6E5FFEDF8', $screen->getUuid());
        $this->assertSame(2, $screen->getScreenType());
    }

    #[Test]
    public function lookupByUuidIsCaseInsensitive(): void
    {
        $library = WorkspaceFileReader::read(self::REFERENCE_PATH);
        $upper = $library->getScreenByUuid('C86D614D-9441-4F78-A177-03E6E5FFEDF8');
        $lower = $library->getScreenByUuid('c86d614d-9441-4f78-a177-03e6e5ffedf8');
        $this->assertNotNull($upper);
        $this->assertSame($upper, $lower);
    }

    #[Test]
    public function writerProducesStableRoundTrip(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'workspace_');
        $second = tempnam(sys_get_temp_dir(), 'workspace_');
        try {
            WorkspaceFileWriter::write(WorkspaceFileReader::read(self::REFERENCE_PATH), $tmp);
            WorkspaceFileWriter::write(WorkspaceFileReader::read($tmp), $second);
            $this->assertSame(file_get_contents($tmp), file_get_contents($second));
        } finally {
            @unlink($tmp);
            @unlink($second);
        }
    }

    #[Test]
    public function addAndRemoveScreenRoundTrip(): void
    {
        $library = WorkspaceFileReader::read(self::REFERENCE_PATH);
        $screen = new Screen(new ProPresenterScreen());
        $screen->setName('Test Screen')->setUuid('11111111-1111-1111-1111-111111111111');
        $library->addScreen($screen);
        $this->assertSame(6, $library->count());
        $this->assertSame('Test Screen', $library->getScreenByUuid('11111111-1111-1111-1111-111111111111')?->getName());
        $this->assertTrue($library->removeScreen('11111111-1111-1111-1111-111111111111'));
        $this->assertSame(5, $library->count());
    }
}

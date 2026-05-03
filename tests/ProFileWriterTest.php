<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileReader;
use ProPresenter\Parser\ProFileWriter;

class ProFileWriterTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/propresenter-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->tmpDir)) {
            return;
        }

        foreach (scandir($this->tmpDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            @unlink($this->tmpDir . '/' . $entry);
        }

        @rmdir($this->tmpDir);
    }

    #[Test]
    public function writeCreatesValidProFile(): void
    {
        $song = ProFileReader::read(self::testProPath());
        $targetPath = $this->tmpDir . '/written.pro';

        ProFileWriter::write($song, $targetPath);

        $this->assertFileExists($targetPath);
        $this->assertGreaterThan(0, filesize($targetPath));

        $roundTripSong = ProFileReader::read($targetPath);
        $this->assertSame($song->getName(), $roundTripSong->getName());
    }

    #[Test]
    public function writeThrowsWhenDirectoryDoesNotExist(): void
    {
        $song = ProFileReader::read(self::testProPath());

        $this->expectException(InvalidArgumentException::class);
        ProFileWriter::write($song, $this->tmpDir . '/missing/fail.pro');
    }

    #[Test]
    public function roundTripPersistsModifiedSongName(): void
    {
        $song = ProFileReader::read(self::testProPath());
        $song->setName('Modified Song Name');

        $targetPath = $this->tmpDir . '/name-roundtrip.pro';
        ProFileWriter::write($song, $targetPath);

        $roundTripSong = ProFileReader::read($targetPath);
        $this->assertSame('Modified Song Name', $roundTripSong->getName());
    }

    #[Test]
    public function roundTripPersistsModifiedSlideText(): void
    {
        $song = ProFileReader::read(self::testProPath());
        $slide = $song->getSlideByUuid('5A6AF946-30B0-4F40-BE7A-C6429C32868A');

        $this->assertNotNull($slide);
        $slide->setPlainText("Roundtrip line 1\nRoundtrip line 2");

        $targetPath = $this->tmpDir . '/text-roundtrip.pro';
        ProFileWriter::write($song, $targetPath);

        $roundTripSong = ProFileReader::read($targetPath);
        $roundTripSlide = $roundTripSong->getSlideByUuid('5A6AF946-30B0-4F40-BE7A-C6429C32868A');

        $this->assertNotNull($roundTripSlide);
        $this->assertSame("Roundtrip line 1\nRoundtrip line 2", $roundTripSlide->getPlainText());
    }

    #[Test]
    public function writePreservesUnmodifiedSongStructure(): void
    {
        $song = ProFileReader::read(self::testProPath());

        $targetPath = $this->tmpDir . '/preserve.pro';
        ProFileWriter::write($song, $targetPath);
        $roundTripSong = ProFileReader::read($targetPath);

        $this->assertSame($song->getUuid(), $roundTripSong->getUuid());
        $this->assertSame(
            array_map(fn ($group) => $group->getName(), $song->getGroups()),
            array_map(fn ($group) => $group->getName(), $roundTripSong->getGroups())
        );
        $this->assertSame(
            array_map(fn ($arrangement) => $arrangement->getName(), $song->getArrangements()),
            array_map(fn ($arrangement) => $arrangement->getName(), $roundTripSong->getArrangements())
        );
        $this->assertSame(
            array_map(fn ($slide) => $slide->getUuid(), $song->getSlides()),
            array_map(fn ($slide) => $slide->getUuid(), $roundTripSong->getSlides())
        );
    }

    private static function testProPath(): string
    {
        return dirname(__DIR__) . '/doc/reference_samples/Test.pro';
    }
}

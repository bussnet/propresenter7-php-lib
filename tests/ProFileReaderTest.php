<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileReader;
use RuntimeException;

class ProFileReaderTest extends TestCase
{
    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ProFileReader::read(dirname(__DIR__) . '/doc/reference_samples/does-not-exist.pro');
    }

    #[Test]
    public function readThrowsOnEmptyFile(): void
    {
        $this->expectException(RuntimeException::class);
        ProFileReader::read(dirname(__DIR__) . '/doc/reference_samples/all-songs/_empty.pro');
    }

    #[Test]
    public function readLoadsTestProAndReturnsSong(): void
    {
        $song = ProFileReader::read(dirname(__DIR__) . '/doc/reference_samples/Test.pro');

        $this->assertSame('Test', $song->getName());
        $this->assertCount(4, $song->getGroups());
        $this->assertCount(2, $song->getArrangements());
    }

    #[Test]
    public function readHandlesUtf8Filename(): void
    {
        // Synthetic fixture with non-ASCII characters in filename (umlaut, accent).
        $matches = glob(dirname(__DIR__) . '/doc/reference_samples/all-songs/Caf* *ber Test.pro');
        $this->assertNotFalse($matches);
        $this->assertNotEmpty($matches);

        $song = ProFileReader::read($matches[0]);

        $this->assertNotSame('', $song->getName());
        $this->assertGreaterThanOrEqual(0, count($song->getGroups()));
    }

    #[Test]
    public function readLoadsDiverseReferenceFilesSuccessfully(): void
    {
        $repoRoot = dirname(__DIR__);
        $files = [
            $repoRoot . '/doc/reference_samples/Test.pro',
            $repoRoot . '/doc/reference_samples/all-songs/Cornerstone.pro',
            $repoRoot . '/doc/reference_samples/all-songs/Amazing Grace.pro',
            $repoRoot . '/doc/reference_samples/all-songs/-- MODERATION --.pro',
        ];

        $translation = glob($repoRoot . '/doc/reference_samples/all-songs/*[TRANS]*.pro');
        $announcements = glob($repoRoot . '/doc/reference_samples/all-songs/-- ANNOUNCEMENTS --.pro');

        $this->assertNotFalse($translation);
        $this->assertNotFalse($announcements);
        $this->assertNotEmpty($translation);
        $this->assertNotEmpty($announcements);

        $files[] = $translation[0];
        $files[] = $announcements[0];

        foreach ($files as $file) {
            $song = ProFileReader::read($file);
            $this->assertNotSame('', $song->getUuid(), sprintf('Song UUID should not be empty for %s', basename($file)));
        }
    }
}

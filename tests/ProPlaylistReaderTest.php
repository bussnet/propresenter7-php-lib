<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\PlaylistArchive;
use ProPresenter\Parser\ProPlaylistReader;
use RuntimeException;

class ProPlaylistReaderTest extends TestCase
{
    private function repoRoot(): string
    {
        return dirname(__DIR__);
    }

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $filePath = $this->repoRoot() . '/doc/reference_samples/does-not-exist.proplaylist';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Playlist file not found: %s', $filePath));
        ProPlaylistReader::read($filePath);
    }

    #[Test]
    public function readThrowsOnEmptyFile(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'proplaylist-empty-');
        if ($filePath === false) {
            self::fail('Unable to create temporary test file.');
        }

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage(sprintf('Playlist file is empty: %s', $filePath));
            ProPlaylistReader::read($filePath);
        } finally {
            @unlink($filePath);
        }
    }

    #[Test]
    public function readThrowsOnInvalidZipFormat(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'proplaylist-invalid-');
        if ($filePath === false) {
            self::fail('Unable to create temporary test file.');
        }

        try {
            file_put_contents($filePath, 'not-a-zip-archive');

            $this->expectException(RuntimeException::class);
            ProPlaylistReader::read($filePath);
        } finally {
            @unlink($filePath);
        }
    }

    #[Test]
    public function readReturnsPlaylistArchiveForTestPlaylist(): void
    {
        $archive = ProPlaylistReader::read($this->repoRoot() . '/doc/reference_samples/TestPlaylist.proplaylist');

        $this->assertInstanceOf(PlaylistArchive::class, $archive);
        $this->assertSame('TestPlaylist', $archive->getName());
        $this->assertGreaterThan(0, $archive->getEntryCount());
    }

    #[Test]
    public function readExtractsEmbeddedFilesFromTestPlaylist(): void
    {
        $archive = ProPlaylistReader::read($this->repoRoot() . '/doc/reference_samples/TestPlaylist.proplaylist');
        $embeddedFiles = $archive->getEmbeddedFiles();

        $this->assertNotEmpty($embeddedFiles);
        $this->assertArrayNotHasKey('data', $embeddedFiles);
        $this->assertGreaterThanOrEqual(2, count($archive->getEmbeddedProFiles()));
        $this->assertGreaterThanOrEqual(1, count($archive->getEmbeddedMediaFiles()));
    }

    #[Test]
    public function readParsesEmbeddedSongsLazilyFromTestPlaylist(): void
    {
        $archive = ProPlaylistReader::read($this->repoRoot() . '/doc/reference_samples/TestPlaylist.proplaylist');
        $embeddedProFiles = $archive->getEmbeddedProFiles();

        $this->assertNotEmpty($embeddedProFiles);
        $firstProFilename = array_key_first($embeddedProFiles);
        $this->assertNotNull($firstProFilename);

        $song = $archive->getEmbeddedSong((string) $firstProFilename);
        $this->assertNotNull($song);
        $this->assertNotSame('', $song->getName());
    }

    #[Test]
    public function readHandlesSampleServicePlaylist(): void
    {
        $archive = ProPlaylistReader::read($this->repoRoot() . '/doc/reference_samples/ExamplePlaylists/SampleService.proplaylist');

        $this->assertNotSame('', $archive->getName());
        $this->assertGreaterThan(0, $archive->getEntryCount());
        $this->assertNotEmpty($archive->getEmbeddedFiles());
    }

    #[Test]
    public function readHandlesEmptyPlaylist(): void
    {
        $archive = ProPlaylistReader::read($this->repoRoot() . '/doc/reference_samples/ExamplePlaylists/EmptyPlaylist.proplaylist');

        // EmptyPlaylist has no entries but must still parse cleanly.
        $this->assertSame(0, $archive->getEntryCount());
        $this->assertSame([], $archive->getEmbeddedFiles());
    }

    #[Test]
    public function readCleansUpTempFileWhenZipOpenFails(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'proplaylist-badzip-');
        if ($filePath === false) {
            self::fail('Unable to create temporary test file.');
        }

        $before = glob(sys_get_temp_dir() . '/proplaylist-*');
        if ($before === false) {
            $before = [];
        }

        try {
            file_put_contents($filePath, str_repeat('x', 128));

            try {
                ProPlaylistReader::read($filePath);
                self::fail('Expected RuntimeException was not thrown.');
            } catch (RuntimeException) {
            }

            $after = glob(sys_get_temp_dir() . '/proplaylist-*');
            if ($after === false) {
                $after = [];
            }

            sort($before);
            sort($after);
            $this->assertSame($before, $after);
        } finally {
            @unlink($filePath);
        }
    }

    #[Test]
    public function readThrowsWhenDataEntryIsMissing(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'proplaylist-nodata-');
        if ($filePath === false) {
            self::fail('Unable to create temporary test file.');
        }

        $zip = new \ZipArchive();

        try {
            $openResult = $zip->open($filePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $this->assertTrue($openResult === true, sprintf('Unable to open zip for test, code: %s', (string) $openResult));

            $zip->addFromString('song1.pro', 'dummy-song');
            $zip->close();

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage(sprintf('Missing data entry in playlist archive: %s', $filePath));
            ProPlaylistReader::read($filePath);
        } finally {
            @unlink($filePath);
        }
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\PlaylistArchive;
use ProPresenter\Parser\ProPlaylistReader;
use ProPresenter\Parser\ProPlaylistWriter;
use RuntimeException;
use ZipArchive;

class ProPlaylistWriterTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/propresenter-playlist-writer-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            $this->removeDirectoryRecursively($this->tmpDir);
        }
    }

    #[Test]
    public function writeThrowsWhenTargetDirectoryDoesNotExist(): void
    {
        $archive = $this->readReferenceArchive();
        $targetPath = $this->tmpDir . '/missing/out.proplaylist';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Target directory does not exist: %s', dirname($targetPath)));

        ProPlaylistWriter::write($archive, $targetPath);
    }

    #[Test]
    public function writeCreatesArchiveFile(): void
    {
        $archive = $this->readReferenceArchive();
        $targetPath = $this->tmpDir . '/written.proplaylist';

        ProPlaylistWriter::write($archive, $targetPath);

        $this->assertFileExists($targetPath);
        $this->assertGreaterThan(0, filesize($targetPath));
    }

    #[Test]
    public function writeAddsDataEntryToZip(): void
    {
        $archive = $this->readReferenceArchive();
        $targetPath = $this->tmpDir . '/with-data.proplaylist';

        ProPlaylistWriter::write($archive, $targetPath);

        $zip = new ZipArchive();
        try {
            $openResult = $zip->open($targetPath);
            $this->assertTrue($openResult === true, sprintf('Unable to open written playlist zip, code: %s', (string) $openResult));
            $this->assertNotFalse($zip->getFromName('data'));
        } finally {
            if ($zip->status === ZipArchive::ER_OK) {
                $zip->close();
            }
        }
    }

    #[Test]
    public function writeUsesStoreCompressionForAllEntries(): void
    {
        $archive = $this->readReferenceArchive();
        $targetPath = $this->tmpDir . '/store-only.proplaylist';

        ProPlaylistWriter::write($archive, $targetPath);

        $zip = new ZipArchive();
        try {
            $openResult = $zip->open($targetPath);
            $this->assertTrue($openResult === true, sprintf('Unable to open written playlist zip, code: %s', (string) $openResult));

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $this->assertIsArray($stat);
                $this->assertSame(ZipArchive::CM_STORE, $stat['comp_method']);
            }
        } finally {
            if ($zip->status === ZipArchive::ER_OK) {
                $zip->close();
            }
        }
    }

    #[Test]
    public function writeIncludesEmbeddedProFilesAtRootLevel(): void
    {
        $archive = $this->readReferenceArchive();
        $targetPath = $this->tmpDir . '/embedded-pro.proplaylist';

        ProPlaylistWriter::write($archive, $targetPath);

        $zip = new ZipArchive();
        try {
            $openResult = $zip->open($targetPath);
            $this->assertTrue($openResult === true, sprintf('Unable to open written playlist zip, code: %s', (string) $openResult));

            foreach (array_keys($archive->getEmbeddedProFiles()) as $proPath) {
                $this->assertStringNotContainsString('/', $proPath);
                $this->assertNotFalse($zip->locateName($proPath));
            }
        } finally {
            if ($zip->status === ZipArchive::ER_OK) {
                $zip->close();
            }
        }
    }

    #[Test]
    public function writeIncludesEmbeddedMediaFilesAtOriginalPaths(): void
    {
        $archive = $this->readReferenceArchive();
        $targetPath = $this->tmpDir . '/embedded-media.proplaylist';

        ProPlaylistWriter::write($archive, $targetPath);

        $zip = new ZipArchive();
        try {
            $openResult = $zip->open($targetPath);
            $this->assertTrue($openResult === true, sprintf('Unable to open written playlist zip, code: %s', (string) $openResult));

            foreach (array_keys($archive->getEmbeddedMediaFiles()) as $mediaPath) {
                $this->assertNotFalse($zip->locateName($mediaPath));
            }
        } finally {
            if ($zip->status === ZipArchive::ER_OK) {
                $zip->close();
            }
        }
    }

    #[Test]
    public function writeSupportsRoundTripWithReader(): void
    {
        $archive = $this->readReferenceArchive();
        $targetPath = $this->tmpDir . '/roundtrip.proplaylist';

        ProPlaylistWriter::write($archive, $targetPath);
        $roundTripArchive = ProPlaylistReader::read($targetPath);

        $this->assertSame($archive->getName(), $roundTripArchive->getName());
        $this->assertSame($archive->getEntryCount(), $roundTripArchive->getEntryCount());
        $this->assertSame(
            array_keys($archive->getEmbeddedFiles()),
            array_keys($roundTripArchive->getEmbeddedFiles()),
        );
    }

    #[Test]
    public function writeCleansUpTempFileWhenTargetPathIsDirectory(): void
    {
        $archive = $this->readReferenceArchive();
        $before = glob(sys_get_temp_dir() . '/proplaylist-*');
        if ($before === false) {
            $before = [];
        }

        $this->expectException(RuntimeException::class);
        try {
            ProPlaylistWriter::write($archive, $this->tmpDir);
        } finally {
            $after = glob(sys_get_temp_dir() . '/proplaylist-*');
            if ($after === false) {
                $after = [];
            }

            sort($before);
            sort($after);
            $this->assertSame($before, $after);
        }
    }

    private function readReferenceArchive(): PlaylistArchive
    {
        return ProPlaylistReader::read(dirname(__DIR__) . '/doc/reference_samples/TestPlaylist.proplaylist');
    }

    private function removeDirectoryRecursively(string $path): void
    {
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $path . '/' . $entry;
            if (is_dir($entryPath)) {
                $this->removeDirectoryRecursively($entryPath);
                continue;
            }

            @unlink($entryPath);
        }

        @rmdir($path);
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\Zip64Fixer;
use RuntimeException;
use ZipArchive;

class Zip64FixerTest extends TestCase
{
    #[Test]
    public function fixReturnsValidZipForProPresenterExport(): void
    {
        $data = $this->readFixture('doc/reference_samples/TestPlaylist.proplaylist');
        $fixed = Zip64Fixer::fix($data);

        $zip = $this->openZipFromBytes($fixed);
        $this->assertGreaterThan(0, $zip->numFiles);
        $zip->close();
    }

    #[Test]
    public function fixReturnsValidZipForSampleServicePlaylist(): void
    {
        $data = $this->readFixture('doc/reference_samples/ExamplePlaylists/SampleService.proplaylist');
        $fixed = Zip64Fixer::fix($data);

        $zip = $this->openZipFromBytes($fixed);
        $this->assertGreaterThan(0, $zip->numFiles);
        $zip->close();
    }

    #[Test]
    public function fixReturnsValidZipForAllSamplePlaylists(): void
    {
        $files = [
            'doc/reference_samples/TestPlaylist.proplaylist',
            'doc/reference_samples/ExamplePlaylists/SampleService.proplaylist',
        ];

        foreach ($files as $fixture) {
            $fixed = Zip64Fixer::fix($this->readFixture($fixture));
            $zip = $this->openZipFromBytes($fixed);
            $this->assertGreaterThan(0, $zip->numFiles, $fixture);
            $zip->close();
        }
    }

    #[Test]
    public function fixThrowsOnNonZipData(): void
    {
        $this->expectException(RuntimeException::class);
        Zip64Fixer::fix(random_bytes(256));
    }

    #[Test]
    public function fixThrowsOnTooSmallData(): void
    {
        $this->expectException(RuntimeException::class);
        Zip64Fixer::fix(str_repeat('x', 10));
    }

    #[Test]
    public function fixPreservesAllEntries(): void
    {
        $raw = $this->readFixture('doc/reference_samples/TestPlaylist.proplaylist');
        $fixed = Zip64Fixer::fix($raw);

        $expectedEntries = $this->listEntriesWithUnzip($fixed);
        $actualEntries = $this->listEntriesWithZipArchive($fixed);

        $expectedEntries = array_map([$this, 'canonicalizeEntryName'], $expectedEntries);
        $actualEntries = array_map([$this, 'canonicalizeEntryName'], $actualEntries);

        sort($expectedEntries, SORT_STRING);
        sort($actualEntries, SORT_STRING);

        $this->assertSame($expectedEntries, $actualEntries);
    }

    #[Test]
    public function fixIdempotent(): void
    {
        $raw = $this->readFixture('doc/reference_samples/TestPlaylist.proplaylist');
        $once = Zip64Fixer::fix($raw);
        $twice = Zip64Fixer::fix($once);

        $this->assertSame($once, $twice);
    }

    private function readFixture(string $relativePath): string
    {
        $path = dirname(__DIR__) . '/' . $relativePath;
        $data = file_get_contents($path);
        $this->assertNotFalse($data, sprintf('Failed to read fixture: %s', $relativePath));

        return $data;
    }

    private function listEntriesWithUnzip(string $zipData): array
    {
        $path = $this->writeTempZip($zipData);

        try {
            $output = shell_exec(sprintf('unzip -l %s 2>&1', escapeshellarg($path)));
            $this->assertNotNull($output, 'Failed to execute unzip');

            $entries = [];
            foreach (explode("\n", $output) as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }

                $parts = preg_split('/\s+/', $trimmed, 4);
                if ($parts === false || count($parts) !== 4) {
                    continue;
                }

                if (!ctype_digit($parts[0])) {
                    continue;
                }

                if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $parts[1])) {
                    continue;
                }

                if (!preg_match('/^\d{2}:\d{2}$/', $parts[2])) {
                    continue;
                }

                $entries[] = $parts[3];
            }

            return array_values($entries);
        } finally {
            @unlink($path);
        }
    }

    private function listEntriesWithZipArchive(string $zipData): array
    {
        $zip = $this->openZipFromBytes($zipData);
        $entries = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name !== false) {
                $entries[] = $name;
            }
        }

        $zip->close();

        return $entries;
    }

    private function openZipFromBytes(string $zipData): ZipArchive
    {
        $path = $this->writeTempZip($zipData);
        $zip = new ZipArchive();
        $status = $zip->open($path);
        @unlink($path);

        $this->assertTrue($status === true, sprintf('ZipArchive open failed with status: %s', (string) $status));

        return $zip;
    }

    private function writeTempZip(string $zipData): string
    {
        $path = tempnam(sys_get_temp_dir(), 'zip64-fixer-');
        $this->assertNotFalse($path);

        $result = file_put_contents($path, $zipData);
        $this->assertNotFalse($result);

        return $path;
    }

    private function canonicalizeEntryName(string $entry): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $entry);
        if ($ascii === false) {
            $ascii = preg_replace('/[^\x20-\x7E]/', '', $entry);
            if ($ascii === null) {
                return $entry;
            }
        }

        return str_replace('?', '', $ascii);
    }
}

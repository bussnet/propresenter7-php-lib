<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProPlaylistGenerator;
use ProPresenter\Parser\ProPlaylistReader;
use ProPresenter\Parser\ProPlaylistWriter;

class ProPlaylistIntegrationTest extends TestCase
{
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->tempFiles = [];
    }

    #[Test]
    public function roundTripPreservesPlaylistName(): void
    {
        [$original, $roundTripped] = $this->readWriteReadReference();

        $this->assertSame($original->getName(), $roundTripped->getName());
    }

    #[Test]
    public function roundTripPreservesEntryCount(): void
    {
        [$original, $roundTripped] = $this->readWriteReadReference();

        $this->assertSame($original->getEntryCount(), $roundTripped->getEntryCount());
    }

    #[Test]
    public function roundTripPreservesEntryTypes(): void
    {
        [$original, $roundTripped] = $this->readWriteReadReference();

        $this->assertSame(
            array_map(static fn ($entry): string => $entry->getType(), $original->getEntries()),
            array_map(static fn ($entry): string => $entry->getType(), $roundTripped->getEntries()),
        );
    }

    #[Test]
    public function roundTripPreservesArrangementNames(): void
    {
        [$original, $roundTripped] = $this->readWriteReadReference();

        $originalArrangementNames = $this->collectArrangementNames($original->getEntries());
        $roundTrippedArrangementNames = $this->collectArrangementNames($roundTripped->getEntries());

        $this->assertNotEmpty($originalArrangementNames);
        $this->assertSame($originalArrangementNames, $roundTrippedArrangementNames);
    }

    #[Test]
    public function roundTripPreservesEmbeddedFileCount(): void
    {
        [$original, $roundTripped] = $this->readWriteReadReference();

        $this->assertSame(count($original->getEmbeddedFiles()), count($roundTripped->getEmbeddedFiles()));
    }

    #[Test]
    public function roundTripPreservesDocumentPaths(): void
    {
        [$original, $roundTripped] = $this->readWriteReadReference();

        $originalDocumentPaths = $this->collectDocumentPaths($original->getEntries());
        $roundTrippedDocumentPaths = $this->collectDocumentPaths($roundTripped->getEntries());

        $this->assertNotEmpty($originalDocumentPaths);
        $this->assertSame($originalDocumentPaths, $roundTrippedDocumentPaths);
    }

    #[Test]
    public function roundTripPreservesHeaderColors(): void
    {
        [$original, $roundTripped] = $this->readWriteReadReference();

        $originalHeaderColors = $this->collectHeaderColors($original->getEntries());
        $roundTrippedHeaderColors = $this->collectHeaderColors($roundTripped->getEntries());

        $this->assertNotEmpty($originalHeaderColors);
        $this->assertSame($originalHeaderColors, $roundTrippedHeaderColors);
    }

    #[Test]
    public function generatedPlaylistReadableByReader(): void
    {
        $generated = ProPlaylistGenerator::generate(
            'Integration Generated Playlist',
            [
                ['type' => 'header', 'name' => 'Songs', 'color' => [0.10, 0.20, 0.30, 0.90]],
                [
                    'type' => 'presentation',
                    'name' => 'Song One',
                    'path' => 'file:///Library/Application%20Support/RenewedVision/ProPresenter/Songs/Song%20One.pro',
                    'arrangement_uuid' => '11111111-2222-3333-4444-555555555555',
                    'arrangement_name' => 'normal',
                ],
                ['type' => 'placeholder', 'name' => 'Spacer'],
                [
                    'type' => 'presentation',
                    'name' => 'Song Two',
                    'path' => 'file:///Library/Application%20Support/RenewedVision/ProPresenter/Songs/Song%20Two.pro',
                    'arrangement_uuid' => '66666666-7777-8888-9999-AAAAAAAAAAAA',
                    'arrangement_name' => 'test2',
                ],
            ],
            [
                'Song One.pro' => 'embedded-song-one',
                'media/background.jpg' => 'embedded-image',
            ],
        );

        $tempPath = $this->createTempPlaylistPath();
        ProPlaylistWriter::write($generated, $tempPath);
        $readBack = ProPlaylistReader::read($tempPath);

        $this->assertSame('Integration Generated Playlist', $readBack->getName());
        $this->assertSame(4, $readBack->getEntryCount());
        $this->assertSame(
            ['header', 'presentation', 'placeholder', 'presentation'],
            array_map(static fn ($entry): string => $entry->getType(), $readBack->getEntries()),
        );
        $this->assertSame(['normal', 'test2'], $this->collectArrangementNames($readBack->getEntries()));
        $this->assertSame(
            [
                'file:///Library/Application%20Support/RenewedVision/ProPresenter/Songs/Song%20One.pro',
                'file:///Library/Application%20Support/RenewedVision/ProPresenter/Songs/Song%20Two.pro',
            ],
            $this->collectDocumentPaths($readBack->getEntries()),
        );
        $headerColors = $this->collectHeaderColors($readBack->getEntries());
        $this->assertCount(1, $headerColors);
        $this->assertEqualsWithDelta(0.1, $headerColors[0][0], 0.000001);
        $this->assertEqualsWithDelta(0.2, $headerColors[0][1], 0.000001);
        $this->assertEqualsWithDelta(0.3, $headerColors[0][2], 0.000001);
        $this->assertEqualsWithDelta(0.9, $headerColors[0][3], 0.000001);
        $this->assertSame(2, count($readBack->getEmbeddedFiles()));
    }

    private function readWriteReadReference(): array
    {
        $original = ProPlaylistReader::read($this->referencePlaylistPath());
        $tempPath = $this->createTempPlaylistPath();

        ProPlaylistWriter::write($original, $tempPath);

        return [$original, ProPlaylistReader::read($tempPath)];
    }

    private function createTempPlaylistPath(): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'playlist-test-');
        if ($tempPath === false) {
            self::fail('Unable to create temporary playlist test file.');
        }

        $this->tempFiles[] = $tempPath;

        return $tempPath;
    }

    private function referencePlaylistPath(): string
    {
        return dirname(__DIR__) . '/doc/reference_samples/TestPlaylist.proplaylist';
    }

    private function collectArrangementNames(array $entries): array
    {
        $arrangementNames = [];

        foreach ($entries as $entry) {
            $name = $entry->getArrangementName();
            if ($name !== null) {
                $arrangementNames[] = $name;
            }
        }

        return $arrangementNames;
    }

    private function collectDocumentPaths(array $entries): array
    {
        $paths = [];

        foreach ($entries as $entry) {
            $path = $entry->getDocumentPath();
            if ($path !== null) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    private function collectHeaderColors(array $entries): array
    {
        $colors = [];

        foreach ($entries as $entry) {
            $color = $entry->getHeaderColor();
            if ($color !== null) {
                $colors[] = array_map(static fn ($component): float => (float) $component, $color);
            }
        }

        return $colors;
    }
}

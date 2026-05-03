<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\PlaylistArchive;
use ProPresenter\Parser\ProPlaylistGenerator;
use ProPresenter\Parser\ProPlaylistReader;
use Rv\Data\MusicKeyScale\MusicKey;
use Rv\Data\Playlist\Type as PlaylistType;
use Rv\Data\PlaylistDocument\Type as PlaylistDocumentType;

class ProPlaylistGeneratorTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/propresenter-playlist-generator-test-' . uniqid();
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
    public function testGenerateBuildsNestedPlaylistStructure(): void
    {
        $archive = ProPlaylistGenerator::generate('Sunday Service', []);

        $this->assertInstanceOf(PlaylistArchive::class, $archive);
        $this->assertSame('Sunday Service', $archive->getName());
        $this->assertSame(PlaylistDocumentType::TYPE_PRESENTATION, $archive->getType());

        $root = $archive->getRootNode();
        $this->assertSame('PLAYLIST', $root->getName());
        $this->assertSame(PlaylistType::TYPE_PLAYLIST, $root->getType());
        $this->assertTrue($root->isContainer());

        $playlist = $archive->getPlaylistNode();
        $this->assertNotNull($playlist);
        $this->assertSame('Sunday Service', $playlist->getName());
        $this->assertSame(PlaylistType::TYPE_PLAYLIST, $playlist->getType());
        $this->assertTrue($playlist->isLeaf());
    }

    #[Test]
    public function testGenerateBuildsHeaderItem(): void
    {
        $archive = ProPlaylistGenerator::generate('Service', [
            ['type' => 'header', 'name' => 'Welcome', 'color' => [0.1, 0.2, 0.3, 1.0]],
        ]);

        $entry = $archive->getEntries()[0];
        $this->assertSame('header', $entry->getType());
        $this->assertSame('Welcome', $entry->getName());
        $headerColor = $entry->getHeaderColor();
        $this->assertNotNull($headerColor);
        $this->assertEqualsWithDelta(0.1, $headerColor[0], 0.00001);
        $this->assertEqualsWithDelta(0.2, $headerColor[1], 0.00001);
        $this->assertEqualsWithDelta(0.3, $headerColor[2], 0.00001);
        $this->assertEqualsWithDelta(1.0, $headerColor[3], 0.00001);
    }

    #[Test]
    public function testGenerateBuildsPresentationItemWithDefaultMusicKey(): void
    {
        $archive = ProPlaylistGenerator::generate('Service', [
            ['type' => 'presentation', 'name' => 'Amazing Grace', 'path' => 'file:///songs/amazing-grace.pro'],
        ]);

        $entry = $archive->getEntries()[0];
        $this->assertSame('presentation', $entry->getType());
        $this->assertSame('Amazing Grace', $entry->getName());
        $this->assertSame('file:///songs/amazing-grace.pro', $entry->getDocumentPath());

        $musicKey = $entry->getPlaylistItem()->getPresentation()?->getUserMusicKey();
        $this->assertNotNull($musicKey);
        $this->assertSame(MusicKey::MUSIC_KEY_C, $musicKey->getMusicKey());
    }

    #[Test]
    public function testGenerateBuildsPresentationItemWithArrangementData(): void
    {
        $archive = ProPlaylistGenerator::generate('Service', [
            [
                'type' => 'presentation',
                'name' => 'Song A',
                'path' => 'file:///songs/song-a.pro',
                'arrangement_uuid' => '11111111-2222-3333-4444-555555555555',
                'arrangement_name' => 'normal',
            ],
        ]);

        $entry = $archive->getEntries()[0];
        $this->assertTrue($entry->hasArrangement());
        $this->assertSame('11111111-2222-3333-4444-555555555555', $entry->getArrangementUuid());
        $this->assertSame('normal', $entry->getArrangementName());
    }

    #[Test]
    public function testGenerateBuildsPlaceholderItem(): void
    {
        $archive = ProPlaylistGenerator::generate('Service', [
            ['type' => 'placeholder', 'name' => 'Slot1'],
        ]);

        $entry = $archive->getEntries()[0];
        $this->assertSame('placeholder', $entry->getType());
        $this->assertSame('Slot1', $entry->getName());
    }

    #[Test]
    public function testGenerateBuildsMixedItemOrder(): void
    {
        $archive = ProPlaylistGenerator::generate('Service', [
            ['type' => 'header', 'name' => 'Welcome', 'color' => [0.0, 0.5, 0.8, 1.0]],
            ['type' => 'presentation', 'name' => 'Song', 'path' => 'file:///songs/song.pro'],
            ['type' => 'placeholder', 'name' => 'Slot1'],
        ]);

        $this->assertSame(['header', 'presentation', 'placeholder'], array_map(
            static fn ($entry) => $entry->getType(),
            $archive->getEntries(),
        ));
    }

    #[Test]
    public function testGenerateKeepsEmbeddedFiles(): void
    {
        $archive = ProPlaylistGenerator::generate('Service', [], [
            'song-a.pro' => 'song-bytes',
            'background.jpg' => 'image-bytes',
        ]);

        $this->assertSame(
            ['song-a.pro' => 'song-bytes', 'background.jpg' => 'image-bytes'],
            $archive->getEmbeddedFiles(),
        );
    }

    #[Test]
    public function testGenerateAndWriteCreatesReadablePlaylistFile(): void
    {
        $filePath = $this->tmpDir . '/generated.proplaylist';

        ProPlaylistGenerator::generateAndWrite(
            $filePath,
            'Service',
            [
                ['type' => 'header', 'name' => 'Welcome', 'color' => [0.1, 0.2, 0.3, 1.0]],
                ['type' => 'presentation', 'name' => 'Song', 'path' => 'file:///songs/song.pro'],
                ['type' => 'placeholder', 'name' => 'Slot1'],
            ],
            ['song.pro' => 'dummy-song-bytes'],
        );

        $this->assertFileExists($filePath);

        $archive = ProPlaylistReader::read($filePath);
        $this->assertSame('Service', $archive->getName());
        $this->assertSame(3, $archive->getEntryCount());
        $this->assertArrayHasKey('song.pro', $archive->getEmbeddedFiles());
    }

    #[Test]
    public function testGenerateThrowsForUnsupportedItemType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported playlist item type: cue');

        ProPlaylistGenerator::generate('Service', [
            ['type' => 'cue', 'name' => 'Not supported in generator'],
        ]);
    }
}

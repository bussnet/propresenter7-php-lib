<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\PlaylistArchive;
use ProPresenter\Parser\PlaylistEntry;
use ProPresenter\Parser\PlaylistNode;
use ProPresenter\Parser\Song;
use Rv\Data\Playlist;
use Rv\Data\Playlist\PlaylistArray;
use Rv\Data\Playlist\PlaylistItems;
use Rv\Data\PlaylistDocument;
use Rv\Data\PlaylistItem;
use Rv\Data\PlaylistItem\Presentation as PlaylistItemPresentation;
use Rv\Data\Presentation;
use Rv\Data\UUID;

class PlaylistArchiveTest extends TestCase
{
    // ─── Helpers ───

    private function makePlaylistItem(string $name, string $uuid = ''): PlaylistItem
    {
        $item = new PlaylistItem();
        $item->setName($name);

        if ($uuid !== '') {
            $uuidObj = new UUID();
            $uuidObj->setString($uuid);
            $item->setUuid($uuidObj);
        }

        $pres = new PlaylistItemPresentation();
        $item->setPresentation($pres);

        return $item;
    }

    private function makeRootWithChild(
        string $childName = 'TestPlaylist',
        array $items = [],
    ): Playlist {
        // Child playlist (leaf with items)
        $child = new Playlist();
        $child->setName($childName);

        if (!empty($items)) {
            $playlistItems = new PlaylistItems();
            $playlistItems->setItems($items);
            $child->setItems($playlistItems);
        }

        // Root playlist (container with child playlists)
        $root = new Playlist();
        $root->setName('PLAYLIST');

        $playlistArray = new PlaylistArray();
        $playlistArray->setPlaylists([$child]);
        $root->setPlaylists($playlistArray);

        return $root;
    }

    private function makeSimpleArchive(
        string $childName = 'TestPlaylist',
        array $items = [],
        array $embeddedFiles = [],
        int $type = 0,
    ): PlaylistArchive {
        $doc = new PlaylistDocument();
        $doc->setRootNode($this->makeRootWithChild($childName, $items));
        $doc->setType($type);

        return new PlaylistArchive($doc, $embeddedFiles);
    }

    // ─── getName() ───

    #[Test]
    public function getNameReturnsChildPlaylistName(): void
    {
        $archive = $this->makeSimpleArchive(childName: 'Sunday Service');

        $this->assertSame('Sunday Service', $archive->getName());
    }

    #[Test]
    public function getNameReturnsEmptyStringWhenNoChildren(): void
    {
        $root = new Playlist();
        $root->setName('PLAYLIST');

        $doc = new PlaylistDocument();
        $doc->setRootNode($root);

        $archive = new PlaylistArchive($doc);

        $this->assertSame('', $archive->getName());
    }

    // ─── getRootNode() ───

    #[Test]
    public function getRootNodeReturnsPlaylistNodeWrappingRoot(): void
    {
        $archive = $this->makeSimpleArchive();

        $rootNode = $archive->getRootNode();
        $this->assertInstanceOf(PlaylistNode::class, $rootNode);
        $this->assertSame('PLAYLIST', $rootNode->getName());
    }

    // ─── getPlaylistNode() ───

    #[Test]
    public function getPlaylistNodeReturnsFirstChildNode(): void
    {
        $archive = $this->makeSimpleArchive(childName: 'Gottesdienst');

        $playlistNode = $archive->getPlaylistNode();
        $this->assertInstanceOf(PlaylistNode::class, $playlistNode);
        $this->assertSame('Gottesdienst', $playlistNode->getName());
    }

    #[Test]
    public function getPlaylistNodeReturnsNullWhenNoChildren(): void
    {
        $root = new Playlist();
        $root->setName('PLAYLIST');

        $doc = new PlaylistDocument();
        $doc->setRootNode($root);

        $archive = new PlaylistArchive($doc);

        $this->assertNull($archive->getPlaylistNode());
    }

    // ─── getEntries() / getEntryCount() ───

    #[Test]
    public function getEntriesReturnsEntriesFromPlaylistNode(): void
    {
        $items = [
            $this->makePlaylistItem('Song A', 'uuid-a'),
            $this->makePlaylistItem('Song B', 'uuid-b'),
            $this->makePlaylistItem('Song C', 'uuid-c'),
        ];

        $archive = $this->makeSimpleArchive(items: $items);

        $entries = $archive->getEntries();
        $this->assertCount(3, $entries);
        $this->assertContainsOnlyInstancesOf(PlaylistEntry::class, $entries);
        $this->assertSame('Song A', $entries[0]->getName());
        $this->assertSame('Song B', $entries[1]->getName());
        $this->assertSame('Song C', $entries[2]->getName());
    }

    #[Test]
    public function getEntryCountReturnsTotalItemCount(): void
    {
        $items = [
            $this->makePlaylistItem('Song 1'),
            $this->makePlaylistItem('Song 2'),
        ];

        $archive = $this->makeSimpleArchive(items: $items);

        $this->assertSame(2, $archive->getEntryCount());
    }

    #[Test]
    public function getEntryCountReturnsZeroWhenNoPlaylistNode(): void
    {
        $root = new Playlist();
        $root->setName('PLAYLIST');

        $doc = new PlaylistDocument();
        $doc->setRootNode($root);

        $archive = new PlaylistArchive($doc);

        $this->assertSame(0, $archive->getEntryCount());
    }

    // ─── getType() ───

    #[Test]
    public function getTypeReturnsDocumentType(): void
    {
        $archive = $this->makeSimpleArchive(type: 1);

        $this->assertSame(1, $archive->getType());
    }

    // ─── getDocument() ───

    #[Test]
    public function getDocumentReturnsUnderlyingProto(): void
    {
        $doc = new PlaylistDocument();
        $doc->setRootNode($this->makeRootWithChild());
        $doc->setType(2);

        $archive = new PlaylistArchive($doc);

        $this->assertSame($doc, $archive->getDocument());
    }

    // ─── Embedded file partitioning ───

    #[Test]
    public function getEmbeddedFilesReturnsAllEmbeddedEntries(): void
    {
        $files = [
            'Song.pro' => 'prodata',
            'Another.pro' => 'prodata2',
            'Users/path/image.jpg' => 'imgdata',
        ];

        $archive = $this->makeSimpleArchive(embeddedFiles: $files);

        $embedded = $archive->getEmbeddedFiles();
        $this->assertCount(3, $embedded);
        $this->assertArrayHasKey('Song.pro', $embedded);
        $this->assertArrayHasKey('Another.pro', $embedded);
        $this->assertArrayHasKey('Users/path/image.jpg', $embedded);
    }

    #[Test]
    public function getEmbeddedProFilesReturnsOnlyProFiles(): void
    {
        $files = [
            'Song.pro' => 'prodata',
            'Another Song.pro' => 'prodata2',
            'Users/path/image.jpg' => 'imgdata',
            'Users/path/video.mp4' => 'viddata',
        ];

        $archive = $this->makeSimpleArchive(embeddedFiles: $files);

        $proFiles = $archive->getEmbeddedProFiles();
        $this->assertCount(2, $proFiles);
        $this->assertArrayHasKey('Song.pro', $proFiles);
        $this->assertArrayHasKey('Another Song.pro', $proFiles);
        $this->assertSame('prodata', $proFiles['Song.pro']);
        $this->assertSame('prodata2', $proFiles['Another Song.pro']);
    }

    #[Test]
    public function getEmbeddedMediaFilesReturnsNonProNonDataFiles(): void
    {
        $files = [
            'Song.pro' => 'prodata',
            'Users/path/image.jpg' => 'imgdata',
            'Users/path/video.mp4' => 'viddata',
        ];

        $archive = $this->makeSimpleArchive(embeddedFiles: $files);

        $mediaFiles = $archive->getEmbeddedMediaFiles();
        $this->assertCount(2, $mediaFiles);
        $this->assertArrayHasKey('Users/path/image.jpg', $mediaFiles);
        $this->assertArrayHasKey('Users/path/video.mp4', $mediaFiles);
        $this->assertArrayNotHasKey('Song.pro', $mediaFiles);
    }

    #[Test]
    public function embeddedFilesEmptyByDefault(): void
    {
        $archive = $this->makeSimpleArchive();

        $this->assertSame([], $archive->getEmbeddedFiles());
        $this->assertSame([], $archive->getEmbeddedProFiles());
        $this->assertSame([], $archive->getEmbeddedMediaFiles());
    }

    // ─── Lazy .pro parsing ───

    #[Test]
    public function getEmbeddedSongLazilyParsesProFile(): void
    {
        // Create minimal Presentation proto bytes
        $presentation = new Presentation();
        $presentation->setName('Amazing Grace');
        $proBytes = $presentation->serializeToString();

        $archive = $this->makeSimpleArchive(embeddedFiles: [
            'Amazing Grace.pro' => $proBytes,
        ]);

        $song = $archive->getEmbeddedSong('Amazing Grace.pro');
        $this->assertInstanceOf(Song::class, $song);
        $this->assertSame('Amazing Grace', $song->getName());
    }

    #[Test]
    public function getEmbeddedSongCachesParsedResult(): void
    {
        $presentation = new Presentation();
        $presentation->setName('Cached Song');
        $proBytes = $presentation->serializeToString();

        $archive = $this->makeSimpleArchive(embeddedFiles: [
            'Cached.pro' => $proBytes,
        ]);

        $song1 = $archive->getEmbeddedSong('Cached.pro');
        $song2 = $archive->getEmbeddedSong('Cached.pro');

        $this->assertSame($song1, $song2, 'Lazy parsing should cache and return same Song instance');
    }

    #[Test]
    public function getEmbeddedSongReturnsNullForUnknownFile(): void
    {
        $archive = $this->makeSimpleArchive(embeddedFiles: [
            'Song.pro' => 'data',
        ]);

        $this->assertNull($archive->getEmbeddedSong('NonExistent.pro'));
    }

    #[Test]
    public function getEmbeddedSongReturnsNullForMediaFile(): void
    {
        $archive = $this->makeSimpleArchive(embeddedFiles: [
            'image.jpg' => 'imgdata',
        ]);

        $this->assertNull($archive->getEmbeddedSong('image.jpg'));
    }
}

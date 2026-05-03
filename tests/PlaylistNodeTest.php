<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\PlaylistEntry;
use ProPresenter\Parser\PlaylistNode;
use Rv\Data\Playlist;
use Rv\Data\Playlist\PlaylistArray;
use Rv\Data\Playlist\PlaylistItems;
use Rv\Data\Playlist\Type;
use Rv\Data\PlaylistItem;
use Rv\Data\UUID;

class PlaylistNodeTest extends TestCase
{
    private function makeUuid(string $value): UUID
    {
        $uuid = new UUID();
        $uuid->setString($value);

        return $uuid;
    }

    private function makeLeafPlaylist(string $name, string $uuid, array $itemNames = []): Playlist
    {
        $playlist = new Playlist();
        $playlist->setName($name);
        $playlist->setUuid($this->makeUuid($uuid));
        $playlist->setType(Type::TYPE_PLAYLIST);

        if ($itemNames !== []) {
            $items = [];
            foreach ($itemNames as $i => $itemName) {
                $item = new PlaylistItem();
                $item->setName($itemName);
                $item->setUuid($this->makeUuid("item-uuid-{$i}"));
                $items[] = $item;
            }
            $playlistItems = new PlaylistItems();
            $playlistItems->setItems($items);
            $playlist->setItems($playlistItems);
        }

        return $playlist;
    }

    private function makeContainerPlaylist(string $name, string $uuid, array $children): Playlist
    {
        $playlist = new Playlist();
        $playlist->setName($name);
        $playlist->setUuid($this->makeUuid($uuid));
        $playlist->setType(Type::TYPE_GROUP);

        $playlistArray = new PlaylistArray();
        $playlistArray->setPlaylists($children);
        $playlist->setPlaylists($playlistArray);

        return $playlist;
    }

    #[Test]
    public function getUuidReturnsPlaylistUuid(): void
    {
        $proto = $this->makeLeafPlaylist('Test', 'abc-123');
        $node = new PlaylistNode($proto);

        $this->assertSame('abc-123', $node->getUuid());
    }

    #[Test]
    public function getNameReturnsPlaylistName(): void
    {
        $proto = $this->makeLeafPlaylist('My Playlist', 'uuid-1');
        $node = new PlaylistNode($proto);

        $this->assertSame('My Playlist', $node->getName());
    }

    #[Test]
    public function getTypeReturnsPlaylistType(): void
    {
        $proto = $this->makeLeafPlaylist('Test', 'uuid-1');
        $node = new PlaylistNode($proto);

        $this->assertSame(Type::TYPE_PLAYLIST, $node->getType());
    }

    #[Test]
    public function containerNodeIsContainerAndNotLeaf(): void
    {
        $child = $this->makeLeafPlaylist('Child', 'child-uuid');
        $proto = $this->makeContainerPlaylist('Container', 'container-uuid', [$child]);
        $node = new PlaylistNode($proto);

        $this->assertTrue($node->isContainer());
        $this->assertFalse($node->isLeaf());
    }

    #[Test]
    public function leafNodeIsLeafAndNotContainer(): void
    {
        $proto = $this->makeLeafPlaylist('Leaf', 'leaf-uuid', ['Song A', 'Song B']);
        $node = new PlaylistNode($proto);

        $this->assertTrue($node->isLeaf());
        $this->assertFalse($node->isContainer());
    }

    #[Test]
    public function containerNodeReturnsChildPlaylistNodes(): void
    {
        $child1 = $this->makeLeafPlaylist('Worship', 'child-1');
        $child2 = $this->makeLeafPlaylist('Hymns', 'child-2');
        $proto = $this->makeContainerPlaylist('Root', 'root-uuid', [$child1, $child2]);
        $node = new PlaylistNode($proto);

        $children = $node->getChildNodes();

        $this->assertCount(2, $children);
        $this->assertInstanceOf(PlaylistNode::class, $children[0]);
        $this->assertInstanceOf(PlaylistNode::class, $children[1]);
        $this->assertSame('Worship', $children[0]->getName());
        $this->assertSame('Hymns', $children[1]->getName());
    }

    #[Test]
    public function leafNodeReturnsPlaylistEntries(): void
    {
        $proto = $this->makeLeafPlaylist('Service', 'leaf-uuid', ['Song 1', 'Song 2', 'Song 3']);
        $node = new PlaylistNode($proto);

        $entries = $node->getEntries();

        $this->assertCount(3, $entries);
        $this->assertInstanceOf(PlaylistEntry::class, $entries[0]);
        $this->assertInstanceOf(PlaylistEntry::class, $entries[1]);
        $this->assertInstanceOf(PlaylistEntry::class, $entries[2]);
        $this->assertSame('Song 1', $entries[0]->getName());
        $this->assertSame('Song 2', $entries[1]->getName());
        $this->assertSame('Song 3', $entries[2]->getName());
    }

    #[Test]
    public function getEntryCountReturnsItemCountForLeaf(): void
    {
        $proto = $this->makeLeafPlaylist('Service', 'leaf-uuid', ['A', 'B']);
        $node = new PlaylistNode($proto);

        $this->assertSame(2, $node->getEntryCount());
    }

    #[Test]
    public function getEntryCountReturnsZeroForContainer(): void
    {
        $child = $this->makeLeafPlaylist('Child', 'child-uuid');
        $proto = $this->makeContainerPlaylist('Container', 'c-uuid', [$child]);
        $node = new PlaylistNode($proto);

        $this->assertSame(0, $node->getEntryCount());
    }

    #[Test]
    public function containerNodeReturnsEmptyEntries(): void
    {
        $child = $this->makeLeafPlaylist('Child', 'child-uuid');
        $proto = $this->makeContainerPlaylist('Container', 'c-uuid', [$child]);
        $node = new PlaylistNode($proto);

        $this->assertSame([], $node->getEntries());
    }

    #[Test]
    public function leafNodeReturnsEmptyChildNodes(): void
    {
        $proto = $this->makeLeafPlaylist('Leaf', 'leaf-uuid', ['Song']);
        $node = new PlaylistNode($proto);

        $this->assertSame([], $node->getChildNodes());
    }

    #[Test]
    public function getPlaylistReturnsUnderlyingProto(): void
    {
        $proto = $this->makeLeafPlaylist('Test', 'uuid-1');
        $node = new PlaylistNode($proto);

        $this->assertSame($proto, $node->getPlaylist());
    }

    #[Test]
    public function recursiveWrappingOfNestedContainers(): void
    {
        $grandchild = $this->makeLeafPlaylist('Songs', 'gc-uuid', ['Amazing Grace']);
        $child = $this->makeContainerPlaylist('Folder', 'c-uuid', [$grandchild]);
        $root = $this->makeContainerPlaylist('Root', 'r-uuid', [$child]);
        $node = new PlaylistNode($root);

        $children = $node->getChildNodes();
        $this->assertCount(1, $children);
        $this->assertTrue($children[0]->isContainer());

        $grandchildren = $children[0]->getChildNodes();
        $this->assertCount(1, $grandchildren);
        $this->assertTrue($grandchildren[0]->isLeaf());
        $this->assertSame('Songs', $grandchildren[0]->getName());

        $entries = $grandchildren[0]->getEntries();
        $this->assertCount(1, $entries);
        $this->assertSame('Amazing Grace', $entries[0]->getName());
    }

    #[Test]
    public function emptyPlaylistWithNoChildrenType(): void
    {
        $playlist = new Playlist();
        $playlist->setName('Empty');
        $playlist->setUuid($this->makeUuid('empty-uuid'));
        $playlist->setType(Type::TYPE_PLAYLIST);
        // No items or playlists set — ChildrenType is null

        $node = new PlaylistNode($playlist);

        $this->assertFalse($node->isContainer());
        $this->assertFalse($node->isLeaf());
        $this->assertSame([], $node->getChildNodes());
        $this->assertSame([], $node->getEntries());
        $this->assertSame(0, $node->getEntryCount());
    }

    #[Test]
    public function getTypeReturnsGroupTypeForContainer(): void
    {
        $child = $this->makeLeafPlaylist('Child', 'child-uuid');
        $proto = $this->makeContainerPlaylist('Group', 'g-uuid', [$child]);
        $node = new PlaylistNode($proto);

        $this->assertSame(Type::TYPE_GROUP, $node->getType());
    }
}

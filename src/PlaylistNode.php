<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Playlist;

/**
 * Wraps a protobuf Playlist message, providing convenient access to playlist
 * structure as either a container node (with child playlists) or a leaf node
 * (with playlist items).
 *
 * Uses the oneof ChildrenType to determine node kind:
 * - "playlists" → container node → child PlaylistNode[]
 * - "items" → leaf node → PlaylistEntry[]
 */
class PlaylistNode
{
    /** @var PlaylistNode[] */
    private array $childNodes = [];

    /** @var PlaylistEntry[] */
    private array $entries = [];

    public function __construct(
        private readonly Playlist $playlist,
    ) {
        $childrenType = $this->playlist->getChildrenType();

        if ($childrenType === 'playlists') {
            $playlistArray = $this->playlist->getPlaylists();
            foreach ($playlistArray->getPlaylists() as $childPlaylist) {
                $this->childNodes[] = new self($childPlaylist);
            }
        } elseif ($childrenType === 'items') {
            $playlistItems = $this->playlist->getItems();
            foreach ($playlistItems->getItems() as $item) {
                $this->entries[] = new PlaylistEntry($item);
            }
        }
    }

    /**
     * UUID string of this playlist node.
     */
    public function getUuid(): string
    {
        return $this->playlist->getUuid()?->getString() ?? '';
    }

    /**
     * Display name of this playlist node.
     */
    public function getName(): string
    {
        return $this->playlist->getName();
    }

    /**
     * Playlist type enum value (TYPE_PLAYLIST, TYPE_GROUP, TYPE_SMART, etc.).
     */
    public function getType(): int
    {
        return $this->playlist->getType();
    }

    /**
     * Whether this node is a container (has child playlists).
     */
    public function isContainer(): bool
    {
        return $this->playlist->getChildrenType() === 'playlists';
    }

    /**
     * Whether this node is a leaf (has playlist items).
     */
    public function isLeaf(): bool
    {
        return $this->playlist->getChildrenType() === 'items';
    }

    /**
     * Get child PlaylistNode objects (empty for leaf nodes).
     *
     * @return PlaylistNode[]
     */
    public function getChildNodes(): array
    {
        return $this->childNodes;
    }

    /**
     * Get PlaylistEntry objects (empty for container nodes).
     *
     * @return PlaylistEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Number of items in this leaf node (0 for containers).
     */
    public function getEntryCount(): int
    {
        return count($this->entries);
    }

    /**
     * Access the underlying protobuf Playlist message.
     */
    public function getPlaylist(): Playlist
    {
        return $this->playlist;
    }
}

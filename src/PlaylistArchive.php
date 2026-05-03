<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\PlaylistDocument;
use Rv\Data\Presentation;

/**
 * Top-level wrapper for a ProPresenter playlist archive (.proplaylist).
 *
 * Integrates the protobuf PlaylistDocument with embedded files extracted from
 * the ZIP archive. Provides convenient access to the playlist tree structure,
 * entries, and lazy parsing of embedded .pro files into Song objects.
 *
 * Root structure:
 *   PlaylistDocument → root_node ("PLAYLIST") → first child (actual named playlist)
 */
class PlaylistArchive
{
    private PlaylistNode $rootNode;
    private ?PlaylistNode $playlistNode = null;

    /** @var array<string, string> filename => raw bytes */
    private array $embeddedFiles;

    /** @var array<string, Song> filename => parsed Song (lazy cache) */
    private array $parsedSongs = [];

    public function __construct(
        private readonly PlaylistDocument $document,
        array $embeddedFiles = [],
    ) {
        $this->embeddedFiles = $embeddedFiles;

        $rootPlaylist = $this->document->getRootNode();
        $this->rootNode = new PlaylistNode($rootPlaylist);

        // First child node is the actual named playlist
        $childNodes = $this->rootNode->getChildNodes();
        if (!empty($childNodes)) {
            $this->playlistNode = $childNodes[0];
        }
    }

    /**
     * Name of the actual playlist (first child, not the root "PLAYLIST").
     */
    public function getName(): string
    {
        return $this->playlistNode?->getName() ?? '';
    }

    /**
     * Root PlaylistNode (always named "PLAYLIST").
     */
    public function getRootNode(): PlaylistNode
    {
        return $this->rootNode;
    }

    /**
     * First child node — the actual named playlist.
     */
    public function getPlaylistNode(): ?PlaylistNode
    {
        return $this->playlistNode;
    }

    /**
     * Shortcut: all entries from the playlist node.
     *
     * @return PlaylistEntry[]
     */
    public function getEntries(): array
    {
        return $this->playlistNode?->getEntries() ?? [];
    }

    /**
     * Total number of entries in the playlist.
     */
    public function getEntryCount(): int
    {
        return $this->playlistNode?->getEntryCount() ?? 0;
    }

    /**
     * Document type enum value.
     */
    public function getType(): int
    {
        return $this->document->getType();
    }

    /**
     * Access the underlying protobuf PlaylistDocument.
     */
    public function getDocument(): PlaylistDocument
    {
        return $this->document;
    }

    // ─── Embedded files ───

    /**
     * All embedded files (excluding the `data` proto file).
     *
     * @return array<string, string> filename => raw bytes
     */
    public function getEmbeddedFiles(): array
    {
        return $this->embeddedFiles;
    }

    /**
     * Only .pro song files from embedded entries.
     *
     * @return array<string, string> filename => raw bytes
     */
    public function getEmbeddedProFiles(): array
    {
        return array_filter(
            $this->embeddedFiles,
            static fn (string $_, string $filename): bool => str_ends_with(strtolower($filename), '.pro'),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Only media files (non-.pro, non-data) from embedded entries.
     *
     * @return array<string, string> filename => raw bytes
     */
    public function getEmbeddedMediaFiles(): array
    {
        return array_filter(
            $this->embeddedFiles,
            static fn (string $_, string $filename): bool => !str_ends_with(strtolower($filename), '.pro'),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Lazily parse an embedded .pro file into a Song object.
     *
     * Returns null if the file doesn't exist or isn't a .pro file.
     * Caches the parsed Song for subsequent calls with the same filename.
     */
    public function getEmbeddedSong(string $filename): ?Song
    {
        if (!isset($this->embeddedFiles[$filename])) {
            return null;
        }

        if (!str_ends_with(strtolower($filename), '.pro')) {
            return null;
        }

        if (!isset($this->parsedSongs[$filename])) {
            $presentation = new Presentation();
            $presentation->mergeFromString($this->embeddedFiles[$filename]);
            $this->parsedSongs[$filename] = new Song($presentation);
        }

        return $this->parsedSongs[$filename];
    }
}

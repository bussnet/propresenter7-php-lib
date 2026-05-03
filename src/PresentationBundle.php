<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Presentation;

/**
 * Top-level wrapper for a ProPresenter presentation bundle (.probundle).
 *
 * A .probundle is a ZIP archive containing a single .pro presentation file
 * together with all its referenced media assets (images, videos, audio).
 * This is the Pro7 successor to the Pro6 .pro6x format.
 *
 * Archive layout (flat — no directories):
 *   image.jpg             ← Media files (basename only)
 *   video.mp4
 *   SongName.pro          ← Protobuf-encoded presentation
 */
class PresentationBundle
{
    private Song $song;

    /** @var array<string, string> filename => raw bytes */
    private array $mediaFiles;

    private string $proFilename;

    public function __construct(
        Song $song,
        string $proFilename,
        array $mediaFiles = [],
    ) {
        $this->song = $song;
        $this->proFilename = $proFilename;
        $this->mediaFiles = $mediaFiles;
    }

    /**
     * The embedded presentation/song.
     */
    public function getSong(): Song
    {
        return $this->song;
    }

    /**
     * Filename of the .pro file inside the archive.
     */
    public function getProFilename(): string
    {
        return $this->proFilename;
    }

    /**
     * Name of the presentation (from the embedded Song).
     */
    public function getName(): string
    {
        return $this->song->getName();
    }

    /**
     * Access the underlying protobuf Presentation.
     */
    public function getPresentation(): Presentation
    {
        return $this->song->getPresentation();
    }

    // ─── Media files ───

    /**
     * All media files in the bundle.
     *
     * @return array<string, string> filename => raw bytes
     */
    public function getMediaFiles(): array
    {
        return $this->mediaFiles;
    }

    /**
     * Number of media files in the bundle.
     */
    public function getMediaFileCount(): int
    {
        return count($this->mediaFiles);
    }

    /**
     * Check if a specific media file exists in the bundle.
     */
    public function hasMediaFile(string $path): bool
    {
        return isset($this->mediaFiles[$path]);
    }

    /**
     * Get a specific media file's raw bytes.
     */
    public function getMediaFile(string $path): ?string
    {
        return $this->mediaFiles[$path] ?? null;
    }
}

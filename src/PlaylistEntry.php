<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\PlaylistItem;

/**
 * Read wrapper around a protobuf PlaylistItem representing a playlist entry.
 *
 * Supports all 4 item types: header, presentation, placeholder, cue.
 * Provides typed getters for type-specific data (color, document path, arrangement).
 * Returns null for type-specific getters when the wrong type is accessed.
 */
class PlaylistEntry
{
    public function __construct(
        private readonly PlaylistItem $item,
    ) {
    }

    /**
     * UUID string of this playlist item.
     */
    public function getUuid(): string
    {
        return $this->item->getUuid()?->getString() ?? '';
    }

    /**
     * Display name of this playlist item.
     */
    public function getName(): string
    {
        return $this->item->getName();
    }

    /**
     * Item type as string: "header", "presentation", "cue", "placeholder", "planning_center".
     * Returns empty string if no type is set.
     */
    public function getType(): string
    {
        return $this->item->getItemType() ?? '';
    }

    // ─── Type checks ───

    public function isHeader(): bool
    {
        return $this->getType() === 'header';
    }

    public function isPresentation(): bool
    {
        return $this->getType() === 'presentation';
    }

    public function isCue(): bool
    {
        return $this->getType() === 'cue';
    }

    public function isPlaceholder(): bool
    {
        return $this->getType() === 'placeholder';
    }

    // ─── Header-specific ───

    /**
     * Header color as [r, g, b, a] array (floats 0.0–1.0).
     * Returns null for non-header items or when no color is set.
     *
     * @return float[]|null
     */
    public function getHeaderColor(): ?array
    {
        if (!$this->isHeader()) {
            return null;
        }

        $header = $this->item->getHeader();
        if ($header === null || !$header->hasColor()) {
            return null;
        }

        $color = $header->getColor();

        return [
            $color->getRed(),
            $color->getGreen(),
            $color->getBlue(),
            $color->getAlpha(),
        ];
    }

    // ─── Presentation-specific ───

    /**
     * Full document path URL string for presentation items.
     * Returns null for non-presentation items.
     */
    public function getDocumentPath(): ?string
    {
        if (!$this->isPresentation()) {
            return null;
        }

        $pres = $this->item->getPresentation();

        return $pres?->getDocumentPath()?->getAbsoluteString();
    }

    /**
     * Extract just the filename from the document path URL.
     * Decodes URL-encoded characters (e.g., %20 → space).
     * Returns null for non-presentation items or when no path is set.
     */
    public function getDocumentFilename(): ?string
    {
        $path = $this->getDocumentPath();
        if ($path === null || $path === '') {
            return null;
        }

        $basename = basename(parse_url($path, PHP_URL_PATH) ?? '');

        return urldecode($basename);
    }

    /**
     * Arrangement UUID string for presentation items.
     * Returns null for non-presentation items or when no arrangement is set.
     */
    public function getArrangementUuid(): ?string
    {
        if (!$this->isPresentation()) {
            return null;
        }

        $pres = $this->item->getPresentation();

        return $pres?->getArrangement()?->getString();
    }

    /**
     * Arrangement name (field 5) for presentation items.
     * Returns null for non-presentation items.
     */
    public function getArrangementName(): ?string
    {
        if (!$this->isPresentation()) {
            return null;
        }

        $pres = $this->item->getPresentation();
        $name = $pres?->getArrangementName();

        return ($name !== null && $name !== '') ? $name : null;
    }

    /**
     * Whether a specific arrangement is assigned to this presentation item.
     */
    public function hasArrangement(): bool
    {
        if (!$this->isPresentation()) {
            return false;
        }

        $pres = $this->item->getPresentation();

        return $pres !== null && $pres->hasArrangement();
    }

    // ─── Raw proto access ───

    /**
     * Access the underlying protobuf PlaylistItem.
     */
    public function getPlaylistItem(): PlaylistItem
    {
        return $this->item;
    }
}

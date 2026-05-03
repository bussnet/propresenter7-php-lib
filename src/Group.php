<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Presentation\CueGroup;

/**
 * Wraps a protobuf CueGroup, providing convenient access to group metadata and slide UUIDs.
 *
 * A CueGroup links a Group definition (uuid, name, color) with its slide references (cue_identifiers).
 * This wrapper exposes both through a clean interface without resolving slides — that's Song's job.
 */
class Group
{
    public function __construct(
        private readonly CueGroup $cueGroup,
    ) {
    }

    /**
     * Get the group's UUID as a string.
     */
    public function getUuid(): string
    {
        $group = $this->cueGroup->getGroup();
        if ($group === null || $group->getUuid() === null) {
            return '';
        }

        return $group->getUuid()->getString();
    }

    /**
     * Get the group's display name (e.g. "Verse 1", "Chorus").
     */
    public function getName(): string
    {
        $group = $this->cueGroup->getGroup();
        if ($group === null) {
            return '';
        }

        return $group->getName();
    }

    /**
     * Set the group's display name.
     */
    public function setName(string $name): void
    {
        $group = $this->cueGroup->getGroup();
        if ($group !== null) {
            $group->setName($name);
        }
    }

    /**
     * Get the group's color as an associative array, or null if no color is set.
     *
     * @return array{r: float, g: float, b: float, a: float}|null
     */
    public function getColor(): ?array
    {
        $group = $this->cueGroup->getGroup();
        if ($group === null || !$group->hasColor()) {
            return null;
        }

        $color = $group->getColor();

        return [
            'r' => $color->getRed(),
            'g' => $color->getGreen(),
            'b' => $color->getBlue(),
            'a' => $color->getAlpha(),
        ];
    }

    /**
     * Get the UUIDs of slides in this group.
     *
     * @return string[]
     */
    public function getSlideUuids(): array
    {
        $uuids = [];
        foreach ($this->cueGroup->getCueIdentifiers() as $uuid) {
            $uuids[] = $uuid->getString();
        }

        return $uuids;
    }

    /**
     * Get the group's hotKey.
     */
    public function getHotKey(): ?\Rv\Data\HotKey
    {
        $group = $this->cueGroup->getGroup();
        if ($group === null) {
            return null;
        }

        return $group->getHotKey();
    }

    /**
     * Get the underlying protobuf CueGroup object.
     */
    public function getProto(): CueGroup
    {
        return $this->cueGroup;
    }
}

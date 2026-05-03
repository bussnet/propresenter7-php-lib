<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Presentation\Arrangement as ProtoArrangement;
use Rv\Data\UUID;

/**
 * Wraps a protobuf Presentation\Arrangement, providing access to arrangement metadata
 * and the ordered list of group UUIDs that define the arrangement's sequence.
 *
 * An arrangement defines which groups appear and in what order during a presentation.
 * The same group UUID can appear multiple times (e.g. Chorus repeated).
 */
class Arrangement
{
    public function __construct(
        private readonly ProtoArrangement $arrangement,
    ) {
    }

    /**
     * Get the arrangement's UUID as a string.
     */
    public function getUuid(): string
    {
        $uuid = $this->arrangement->getUuid();
        if ($uuid === null) {
            return '';
        }

        return $uuid->getString();
    }

    /**
     * Get the arrangement's name (e.g. "normal", "test2").
     */
    public function getName(): string
    {
        return $this->arrangement->getName();
    }

    /**
     * Set the arrangement's name.
     */
    public function setName(string $name): void
    {
        $this->arrangement->setName($name);
    }

    /**
     * Get the ordered list of group UUIDs in this arrangement.
     * The same group UUID may appear multiple times.
     *
     * @return string[]
     */
    public function getGroupUuids(): array
    {
        $uuids = [];
        foreach ($this->arrangement->getGroupIdentifiers() as $uuid) {
            $uuids[] = $uuid->getString();
        }

        return $uuids;
    }

    /**
     * Set the ordered list of group UUIDs for this arrangement.
     *
     * @param string[] $uuids
     */
    public function setGroupUuids(array $uuids): void
    {
        $identifiers = [];
        foreach ($uuids as $uuidStr) {
            $uuid = new UUID();
            $uuid->setString($uuidStr);
            $identifiers[] = $uuid;
        }

        $this->arrangement->setGroupIdentifiers($identifiers);
    }

    /**
     * Get the underlying protobuf Arrangement object.
     */
    public function getProto(): ProtoArrangement
    {
        return $this->arrangement;
    }
}

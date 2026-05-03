<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\ClearGroupsDocument;
use Rv\Data\ClearGroupsDocument\ClearGroup as ClearGroupProto;
use Rv\Data\UUID;

class ClearGroupsLibrary
{
    /** @var ClearGroupDefinition[] */
    private array $groups = [];

    /** @var array<string, ClearGroupDefinition> */
    private array $groupsByUuid = [];

    /** @var array<string, ClearGroupDefinition> */
    private array $groupsByName = [];

    public function __construct(
        private readonly ClearGroupsDocument $document,
    ) {
        $this->rebuildIndex();
    }

    /**
     * Return clear groups in document order.
     *
     * @return ClearGroupDefinition[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    public function count(): int
    {
        return count($this->groups);
    }

    public function getClearGroupByUuid(string $uuid): ?ClearGroupDefinition
    {
        return $this->groupsByUuid[strtoupper($uuid)] ?? null;
    }

    public function getClearGroupByName(string $name): ?ClearGroupDefinition
    {
        return $this->groupsByName[$name] ?? null;
    }

    public function addClearGroup(string $name, string $uuid): ClearGroupDefinition
    {
        $proto = new ClearGroupProto();
        $uuidProto = new UUID();
        $uuidProto->setString($uuid);
        $proto->setUuid($uuidProto);
        $proto->setName($name);

        $existing = iterator_to_array($this->document->getGroups());
        $existing[] = $proto;
        $this->document->setGroups($existing);
        $this->rebuildIndex();

        return $this->getClearGroupByUuid($uuid) ?? new ClearGroupDefinition($proto);
    }

    public function removeClearGroup(string $uuid): bool
    {
        $needle = strtoupper($uuid);
        $kept = [];
        $removed = false;
        foreach ($this->document->getGroups() as $proto) {
            $current = strtoupper($proto->getUuid()?->getString() ?? '');
            if (!$removed && $current === $needle) {
                $removed = true;
                continue;
            }
            $kept[] = $proto;
        }

        if (!$removed) {
            return false;
        }

        $this->document->setGroups($kept);
        $this->rebuildIndex();

        return true;
    }

    public function getDocument(): ClearGroupsDocument
    {
        return $this->document;
    }

    private function rebuildIndex(): void
    {
        $this->groups = [];
        $this->groupsByUuid = [];
        $this->groupsByName = [];

        foreach ($this->document->getGroups() as $proto) {
            $group = new ClearGroupDefinition($proto);
            $this->groups[] = $group;

            $uuid = strtoupper($group->getUuid());
            if ($uuid !== '') {
                $this->groupsByUuid[$uuid] = $group;
            }

            $name = $group->getName();
            if ($name !== '') {
                $this->groupsByName[$name] ??= $group;
            }
        }
    }
}

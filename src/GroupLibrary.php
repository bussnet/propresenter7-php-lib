<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Group as GroupProto;
use Rv\Data\ProGroupsDocument;
use Rv\Data\UUID;

class GroupLibrary
{
    /** @var GroupDefinition[] */
    private array $groups = [];

    /** @var array<string, GroupDefinition> */
    private array $groupsByUuid = [];

    /** @var array<string, GroupDefinition> */
    private array $groupsByName = [];

    public function __construct(
        private readonly ProGroupsDocument $document,
    ) {
        $this->rebuildIndex();
    }

    /**
     * @return GroupDefinition[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    public function count(): int
    {
        return count($this->groups);
    }

    public function getGroupByUuid(string $uuid): ?GroupDefinition
    {
        return $this->groupsByUuid[strtoupper($uuid)] ?? null;
    }

    public function getGroupByName(string $name): ?GroupDefinition
    {
        return $this->groupsByName[$name] ?? null;
    }

    public function addGroup(string $name, string $uuid): GroupDefinition
    {
        $proto = new GroupProto();
        $uuidProto = new UUID();
        $uuidProto->setString($uuid);
        $proto->setUuid($uuidProto);
        $proto->setName($name);

        $existing = iterator_to_array($this->document->getGroups());
        $existing[] = $proto;
        $this->document->setGroups($existing);
        $this->rebuildIndex();

        return $this->getGroupByUuid($uuid) ?? new GroupDefinition($proto);
    }

    public function removeGroup(string $uuid): bool
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

    public function getDocument(): ProGroupsDocument
    {
        return $this->document;
    }

    private function rebuildIndex(): void
    {
        $this->groups = [];
        $this->groupsByUuid = [];
        $this->groupsByName = [];

        foreach ($this->document->getGroups() as $proto) {
            $group = new GroupDefinition($proto);
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

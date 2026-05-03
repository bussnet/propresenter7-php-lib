<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\ApplicationInfo;
use Rv\Data\KeyMappingsDocument;
use Rv\Data\KeyMappingsDocument\Mapping as MappingProto;
use Rv\Data\UUID;

class KeyMappingsLibrary
{
    /** @var KeyMapping[] */
    private array $mappings = [];

    /** @var array<string, KeyMapping> */
    private array $mappingsByUuid = [];

    /** @var array<string, KeyMapping> */
    private array $mappingsByName = [];

    public function __construct(
        private readonly KeyMappingsDocument $document,
    ) {
        $this->rebuildIndex();
    }

    /**
     * Return key mappings in document order.
     *
     * @return KeyMapping[]
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }

    public function count(): int
    {
        return count($this->mappings);
    }

    public function getMappingByUuid(string $uuid): ?KeyMapping
    {
        return $this->mappingsByUuid[strtoupper($uuid)] ?? null;
    }

    public function getMappingByName(string $name): ?KeyMapping
    {
        return $this->mappingsByName[$name] ?? null;
    }

    public function addMapping(string $name, string $uuid, string $target = ''): KeyMapping
    {
        $proto = new MappingProto();
        $uuidProto = new UUID();
        $uuidProto->setString($uuid);
        $proto->setUuid($uuidProto);
        $proto->setName($name);
        $proto->setTarget($target);

        $existing = iterator_to_array($this->document->getMappings());
        $existing[] = $proto;
        $this->document->setMappings($existing);
        $this->rebuildIndex();

        return $this->getMappingByUuid($uuid) ?? new KeyMapping($proto);
    }

    public function removeMapping(string $uuid): bool
    {
        $needle = strtoupper($uuid);
        $kept = [];
        $removed = false;
        foreach ($this->document->getMappings() as $proto) {
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

        $this->document->setMappings($kept);
        $this->rebuildIndex();

        return true;
    }

    public function getApplicationInfo(): ?ApplicationInfo
    {
        return $this->document->getApplicationInfo();
    }

    public function setApplicationInfo(?ApplicationInfo $applicationInfo): self
    {
        if ($applicationInfo === null) {
            $this->document->clearApplicationInfo();

            return $this;
        }

        $this->document->setApplicationInfo($applicationInfo);

        return $this;
    }

    public function getDocument(): KeyMappingsDocument
    {
        return $this->document;
    }

    private function rebuildIndex(): void
    {
        $this->mappings = [];
        $this->mappingsByUuid = [];
        $this->mappingsByName = [];

        foreach ($this->document->getMappings() as $proto) {
            $mapping = new KeyMapping($proto);
            $this->mappings[] = $mapping;

            $uuid = strtoupper($mapping->getUuid());
            if ($uuid !== '') {
                $this->mappingsByUuid[$uuid] = $mapping;
            }

            $name = $mapping->getName();
            if ($name !== '') {
                $this->mappingsByName[$name] ??= $mapping;
            }
        }
    }
}

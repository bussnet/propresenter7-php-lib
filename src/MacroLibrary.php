<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\MacrosDocument;
use Rv\Data\MacrosDocument\Macro as MacroProto;
use Rv\Data\MacrosDocument\MacroCollection as MacroCollectionProto;
use Rv\Data\UUID;

class MacroLibrary
{
    /** @var Macro[] */
    private array $macros = [];

    /** @var MacroCollection[] */
    private array $collections = [];

    /** @var array<string, Macro> */
    private array $macrosByUuid = [];

    /** @var array<string, Macro> */
    private array $macrosByName = [];

    /** @var array<string, MacroCollection> */
    private array $collectionsByUuid = [];

    /** @var array<string, MacroCollection> */
    private array $collectionsByName = [];

    /** @var array<string, MacroCollection[]> */
    private array $collectionsByMacroUuid = [];

    public function __construct(
        private readonly MacrosDocument $document,
    ) {
        $this->rebuildIndex();
    }

    /**
     * @return Macro[]
     */
    public function getMacros(): array
    {
        return $this->macros;
    }

    public function getMacroByUuid(string $uuid): ?Macro
    {
        return $this->macrosByUuid[strtoupper($uuid)] ?? null;
    }

    public function getMacroByName(string $name): ?Macro
    {
        return $this->macrosByName[$name] ?? null;
    }

    /**
     * @return MacroCollection[]
     */
    public function getCollections(): array
    {
        return $this->collections;
    }

    public function getCollectionByUuid(string $uuid): ?MacroCollection
    {
        return $this->collectionsByUuid[strtoupper($uuid)] ?? null;
    }

    public function getCollectionByName(string $name): ?MacroCollection
    {
        return $this->collectionsByName[$name] ?? null;
    }

    /**
     * @return Macro[]
     */
    public function getMacrosForCollection(MacroCollection $collection): array
    {
        $resolved = [];
        foreach ($collection->getMacroUuids() as $uuid) {
            $macro = $this->getMacroByUuid($uuid);
            if ($macro !== null) {
                $resolved[] = $macro;
            }
        }

        return $resolved;
    }

    /**
     * @return MacroCollection[]
     */
    public function getCollectionsForMacro(Macro $macro): array
    {
        $key = strtoupper($macro->getUuid());
        if ($key === '') {
            return [];
        }

        return $this->collectionsByMacroUuid[$key] ?? [];
    }

    /**
     * Append a brand-new macro to the document.
     */
    public function addMacro(string $name, string $uuid): Macro
    {
        $proto = new MacroProto();
        $uuidProto = new UUID();
        $uuidProto->setString($uuid);
        $proto->setUuid($uuidProto);
        $proto->setName($name);

        $existing = iterator_to_array($this->document->getMacros());
        $existing[] = $proto;
        $this->document->setMacros($existing);

        $this->rebuildIndex();

        return $this->getMacroByUuid($uuid) ?? new Macro($proto);
    }

    public function removeMacro(string $uuid): bool
    {
        $needle = strtoupper($uuid);
        $kept = [];
        $removed = false;
        foreach ($this->document->getMacros() as $proto) {
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

        $this->document->setMacros($kept);
        $this->rebuildIndex();

        return true;
    }

    public function addCollection(string $name, string $uuid): MacroCollection
    {
        $proto = new MacroCollectionProto();
        $uuidProto = new UUID();
        $uuidProto->setString($uuid);
        $proto->setUuid($uuidProto);
        $proto->setName($name);

        $existing = iterator_to_array($this->document->getMacroCollections());
        $existing[] = $proto;
        $this->document->setMacroCollections($existing);

        $this->rebuildIndex();

        return $this->getCollectionByUuid($uuid) ?? new MacroCollection($proto);
    }

    public function removeCollection(string $uuid): bool
    {
        $needle = strtoupper($uuid);
        $kept = [];
        $removed = false;
        foreach ($this->document->getMacroCollections() as $proto) {
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

        $this->document->setMacroCollections($kept);
        $this->rebuildIndex();

        return true;
    }

    public function getDocument(): MacrosDocument
    {
        return $this->document;
    }

    private function rebuildIndex(): void
    {
        $this->macros = [];
        $this->collections = [];
        $this->macrosByUuid = [];
        $this->macrosByName = [];
        $this->collectionsByUuid = [];
        $this->collectionsByName = [];
        $this->collectionsByMacroUuid = [];

        foreach ($this->document->getMacros() as $macroProto) {
            $macro = new Macro($macroProto);
            $this->macros[] = $macro;

            $uuid = strtoupper($macro->getUuid());
            if ($uuid !== '') {
                $this->macrosByUuid[$uuid] = $macro;
            }

            $name = $macro->getName();
            if ($name !== '') {
                $this->macrosByName[$name] = $macro;
            }
        }

        foreach ($this->document->getMacroCollections() as $collectionProto) {
            $collection = new MacroCollection($collectionProto);
            $this->collections[] = $collection;

            $uuid = strtoupper($collection->getUuid());
            if ($uuid !== '') {
                $this->collectionsByUuid[$uuid] = $collection;
            }

            $name = $collection->getName();
            if ($name !== '') {
                $this->collectionsByName[$name] = $collection;
            }

            foreach ($collection->getMacroUuids() as $macroUuid) {
                $key = strtoupper($macroUuid);
                if ($key === '') {
                    continue;
                }
                $this->collectionsByMacroUuid[$key][] = $collection;
            }
        }
    }
}

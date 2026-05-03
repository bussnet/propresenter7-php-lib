<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\MacrosDocument\MacroCollection as MacroCollectionProto;
use Rv\Data\MacrosDocument\MacroCollection\Item as ItemProto;
use Rv\Data\UUID;

class MacroCollection
{
    public function __construct(
        private readonly MacroCollectionProto $collection,
    ) {
    }

    public function getUuid(): string
    {
        return $this->collection->getUuid()?->getString() ?? '';
    }

    public function setUuid(string $uuid): self
    {
        $proto = new UUID();
        $proto->setString($uuid);
        $this->collection->setUuid($proto);

        return $this;
    }

    public function getName(): string
    {
        return $this->collection->getName();
    }

    public function setName(string $name): self
    {
        $this->collection->setName($name);

        return $this;
    }

    /**
     * @return string[]
     */
    public function getMacroUuids(): array
    {
        $uuids = [];
        foreach ($this->collection->getItems() as $item) {
            $macroId = $item->getMacroId();
            if ($macroId !== null) {
                $uuids[] = $macroId->getString();
            }
        }

        return $uuids;
    }

    /**
     * Replace the collection's referenced macro UUIDs in one call. Pass UUID
     * strings exactly as ProPresenter writes them (upper-case is conventional).
     *
     * @param string[] $uuids
     */
    public function setMacroUuids(array $uuids): self
    {
        $items = [];
        foreach ($uuids as $uuid) {
            $item = new ItemProto();
            $ref = new UUID();
            $ref->setString($uuid);
            $item->setMacroId($ref);
            $items[] = $item;
        }
        $this->collection->setItems($items);

        return $this;
    }

    public function addMacroUuid(string $uuid): self
    {
        $items = iterator_to_array($this->collection->getItems());
        $item = new ItemProto();
        $ref = new UUID();
        $ref->setString($uuid);
        $item->setMacroId($ref);
        $items[] = $item;
        $this->collection->setItems($items);

        return $this;
    }

    public function getProto(): MacroCollectionProto
    {
        return $this->collection;
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\HotKey;
use Rv\Data\KeyMappingsDocument\Mapping as MappingProto;
use Rv\Data\UUID;

class KeyMapping
{
    public function __construct(
        private readonly MappingProto $mapping,
    ) {
    }

    public function getUuid(): string
    {
        return $this->mapping->getUuid()?->getString() ?? '';
    }

    public function setUuid(string $uuid): self
    {
        $proto = new UUID();
        $proto->setString($uuid);
        $this->mapping->setUuid($proto);

        return $this;
    }

    public function getName(): string
    {
        return $this->mapping->getName();
    }

    public function setName(string $name): self
    {
        $this->mapping->setName($name);

        return $this;
    }

    public function getHotKey(): ?HotKey
    {
        if (!$this->mapping->hasHotKey()) {
            return null;
        }

        return $this->mapping->getHotKey();
    }

    public function setHotKey(?HotKey $hotKey): self
    {
        if ($hotKey === null) {
            $this->mapping->clearHotKey();

            return $this;
        }

        $this->mapping->setHotKey($hotKey);

        return $this;
    }

    public function getTarget(): string
    {
        return $this->mapping->getTarget();
    }

    public function setTarget(string $target): self
    {
        $this->mapping->setTarget($target);

        return $this;
    }

    public function getProto(): MappingProto
    {
        return $this->mapping;
    }
}

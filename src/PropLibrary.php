<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\ApplicationInfo;
use Rv\Data\Cue;
use Rv\Data\PropDocument;

class PropLibrary
{
    /** @var Prop[] */
    private array $props = [];

    /** @var array<string, Prop> */
    private array $propsByUuid = [];

    /** @var array<string, Prop> */
    private array $propsByName = [];

    public function __construct(
        private readonly PropDocument $document,
    ) {
        $this->rebuildIndex();
    }

    public function getDocument(): PropDocument
    {
        return $this->document;
    }

    /** @return Prop[] */
    public function getProps(): array
    {
        return $this->props;
    }

    public function getPropByUuid(string $uuid): ?Prop
    {
        return $this->propsByUuid[strtoupper($uuid)] ?? null;
    }

    public function getPropByName(string $name): ?Prop
    {
        return $this->propsByName[$name] ?? null;
    }

    public function addProp(Prop|Cue $prop): Prop
    {
        $proto = $prop instanceof Prop ? $prop->getProto() : $prop;
        $existing = iterator_to_array($this->document->getCues());
        $existing[] = $proto;
        $this->document->setCues($existing);
        $this->rebuildIndex();

        return $this->props[array_key_last($this->props)];
    }

    public function removeProp(string $uuid): bool
    {
        $needle = strtoupper($uuid);
        $kept = [];
        $removed = false;
        foreach ($this->document->getCues() as $proto) {
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

        $this->document->setCues($kept);
        $this->rebuildIndex();

        return true;
    }

    public function count(): int
    {
        return count($this->props);
    }

    public function getApplicationInfo(): ?ApplicationInfo
    {
        return $this->document->getApplicationInfo();
    }

    private function rebuildIndex(): void
    {
        $this->props = [];
        $this->propsByUuid = [];
        $this->propsByName = [];

        foreach ($this->document->getCues() as $proto) {
            $prop = new Prop($proto);
            $this->props[] = $prop;

            $uuid = strtoupper($prop->getUuid());
            if ($uuid !== '') {
                $this->propsByUuid[$uuid] = $prop;
            }

            $name = $prop->getName();
            if ($name !== '') {
                $this->propsByName[$name] ??= $prop;
            }
        }
    }
}

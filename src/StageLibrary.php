<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\ApplicationInfo;
use Rv\Data\Stage\Document;
use Rv\Data\Stage\Layout as LayoutProto;

class StageLibrary
{
    /** @var StageLayout[] */
    private array $layouts = [];

    /** @var array<string, StageLayout> */
    private array $layoutsByUuid = [];

    /** @var array<string, StageLayout> */
    private array $layoutsByName = [];

    public function __construct(
        private readonly Document $document,
    ) {
        $this->rebuildIndex();
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    /** @return StageLayout[] */
    public function getLayouts(): array
    {
        return $this->layouts;
    }

    public function getLayoutByUuid(string $uuid): ?StageLayout
    {
        return $this->layoutsByUuid[strtoupper($uuid)] ?? null;
    }

    public function getLayoutByName(string $name): ?StageLayout
    {
        return $this->layoutsByName[$name] ?? null;
    }

    public function addLayout(StageLayout|LayoutProto $layout): StageLayout
    {
        $proto = $layout instanceof StageLayout ? $layout->getProto() : $layout;
        $existing = iterator_to_array($this->document->getLayouts());
        $existing[] = $proto;
        $this->document->setLayouts($existing);
        $this->rebuildIndex();

        return $this->layouts[array_key_last($this->layouts)];
    }

    public function removeLayout(string $uuid): bool
    {
        $needle = strtoupper($uuid);
        $kept = [];
        $removed = false;
        foreach ($this->document->getLayouts() as $proto) {
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

        $this->document->setLayouts($kept);
        $this->rebuildIndex();

        return true;
    }

    public function count(): int
    {
        return count($this->layouts);
    }

    public function getApplicationInfo(): ?ApplicationInfo
    {
        return $this->document->getApplicationInfo();
    }

    private function rebuildIndex(): void
    {
        $this->layouts = [];
        $this->layoutsByUuid = [];
        $this->layoutsByName = [];

        foreach ($this->document->getLayouts() as $proto) {
            $layout = new StageLayout($proto);
            $this->layouts[] = $layout;

            $uuid = strtoupper($layout->getUuid());
            if ($uuid !== '') {
                $this->layoutsByUuid[$uuid] = $layout;
            }

            $name = $layout->getName();
            if ($name !== '') {
                $this->layoutsByName[$name] ??= $layout;
            }
        }
    }
}

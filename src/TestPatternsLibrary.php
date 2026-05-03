<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\TestPatternDocument;
use Rv\Data\TestPatternDocument\TestPatternData;
use Rv\Data\TestPatternDocument\TestPatternStateData;
use Rv\Data\UUID;

class TestPatternsLibrary
{
    /** @var TestPatternData[] */
    private array $patterns = [];

    /** @var array<string, TestPatternData> */
    private array $patternsByUuid = [];

    /** @var array<string, TestPatternData> */
    private array $patternsByName = [];

    public function __construct(
        private readonly TestPatternDocument $document,
    ) {
        $this->rebuildIndex();
    }

    public function getState(): ?TestPatternStateData
    {
        if (!$this->document->hasState()) {
            return null;
        }

        return $this->document->getState();
    }

    public function setState(?TestPatternStateData $state): self
    {
        if ($state === null) {
            $this->document->clearState();

            return $this;
        }

        $this->document->setState($state);

        return $this;
    }

    public function getSelectedPatternUuid(): string
    {
        return $this->getState()?->getTestPatternId()?->getString() ?? '';
    }

    public function getSelectedPatternNameLocalizationKey(): string
    {
        return $this->getState()?->getTestPatternNameLocalizationKey() ?? '';
    }

    public function getDisplayLocation(): int
    {
        return $this->getState()?->getDisplayLocation() ?? 0;
    }

    public function getSpecificScreenUuid(): string
    {
        return $this->getState()?->getSpecificScreen()?->getString() ?? '';
    }

    /**
     * Return saved test pattern definitions in document order.
     *
     * @return TestPatternData[]
     */
    public function getPatterns(): array
    {
        return $this->patterns;
    }

    public function count(): int
    {
        return count($this->patterns);
    }

    public function getPatternByUuid(string $uuid): ?TestPatternData
    {
        return $this->patternsByUuid[strtoupper($uuid)] ?? null;
    }

    public function getPatternByName(string $nameLocalizationKey): ?TestPatternData
    {
        return $this->patternsByName[$nameLocalizationKey] ?? null;
    }

    public function addPattern(string $nameLocalizationKey, string $uuid): TestPatternData
    {
        $proto = new TestPatternData();
        $uuidProto = new UUID();
        $uuidProto->setString($uuid);
        $proto->setUuid($uuidProto);
        $proto->setNameLocalizationKey($nameLocalizationKey);

        $existing = iterator_to_array($this->document->getPatterns());
        $existing[] = $proto;
        $this->document->setPatterns($existing);
        $this->rebuildIndex();

        return $this->getPatternByUuid($uuid) ?? $proto;
    }

    public function removePattern(string $uuid): bool
    {
        $needle = strtoupper($uuid);
        $kept = [];
        $removed = false;
        foreach ($this->document->getPatterns() as $proto) {
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

        $this->document->setPatterns($kept);
        $this->rebuildIndex();

        return true;
    }

    public function getDocument(): TestPatternDocument
    {
        return $this->document;
    }

    private function rebuildIndex(): void
    {
        $this->patterns = [];
        $this->patternsByUuid = [];
        $this->patternsByName = [];

        foreach ($this->document->getPatterns() as $proto) {
            $this->patterns[] = $proto;

            $uuid = strtoupper($proto->getUuid()?->getString() ?? '');
            if ($uuid !== '') {
                $this->patternsByUuid[$uuid] = $proto;
            }

            $name = $proto->getNameLocalizationKey();
            if ($name !== '') {
                $this->patternsByName[$name] ??= $proto;
            }
        }
    }
}

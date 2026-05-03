<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Google\Protobuf\Internal\RepeatedField;
use Rv\Data\ProPresenterScreen;
use Rv\Data\ProPresenterWorkspace;

class WorkspaceLibrary
{
    /** @var Screen[] */
    private array $screens = [];

    /** @var array<string, Screen> */
    private array $screensByUuid = [];

    /** @var array<string, Screen> */
    private array $screensByName = [];

    public function __construct(
        private readonly ProPresenterWorkspace $document,
    ) {
        $this->rebuildIndex();
    }

    public function getDocument(): ProPresenterWorkspace
    {
        return $this->document;
    }

    /** @return Screen[] */
    public function getScreens(): array
    {
        return $this->screens;
    }

    public function getScreenByName(string $name): ?Screen
    {
        return $this->screensByName[$name] ?? null;
    }

    public function getScreenByUuid(string $uuid): ?Screen
    {
        return $this->screensByUuid[strtoupper($uuid)] ?? null;
    }

    public function addScreen(Screen|ProPresenterScreen $screen): Screen
    {
        $proto = $screen instanceof Screen ? $screen->getProto() : $screen;
        $existing = iterator_to_array($this->document->getProScreens());
        $existing[] = $proto;
        $this->document->setProScreens($existing);
        $this->rebuildIndex();

        return $this->screens[array_key_last($this->screens)];
    }

    public function removeScreen(string $uuid): bool
    {
        $needle = strtoupper($uuid);
        $kept = [];
        $removed = false;
        foreach ($this->document->getProScreens() as $proto) {
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

        $this->document->setProScreens($kept);
        $this->rebuildIndex();

        return true;
    }

    public function count(): int
    {
        return count($this->screens);
    }

    public function getAudienceLooks(): RepeatedField
    {
        return $this->document->getAudienceLooks();
    }

    public function getMasks(): RepeatedField
    {
        return $this->document->getMasks();
    }

    public function getVideoInputs(): RepeatedField
    {
        return $this->document->getVideoInputs();
    }

    public function getSelectedLibraryName(): string
    {
        return $this->document->getSelectedLibraryName();
    }

    public function setSelectedLibraryName(string $name): self
    {
        $this->document->setSelectedLibraryName($name);

        return $this;
    }

    private function rebuildIndex(): void
    {
        $this->screens = [];
        $this->screensByUuid = [];
        $this->screensByName = [];

        foreach ($this->document->getProScreens() as $proto) {
            $screen = new Screen($proto);
            $this->screens[] = $screen;

            $uuid = strtoupper($screen->getUuid());
            if ($uuid !== '') {
                $this->screensByUuid[$uuid] = $screen;
            }

            $name = $screen->getName();
            if ($name !== '') {
                $this->screensByName[$name] ??= $screen;
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\ProPresenterScreen;
use Rv\Data\UUID;

class Screen
{
    public function __construct(
        private readonly ProPresenterScreen $screen,
    ) {
    }

    public function getName(): string
    {
        return $this->screen->getName();
    }

    public function setName(string $name): self
    {
        $this->screen->setName($name);

        return $this;
    }

    public function getUuid(): string
    {
        return $this->screen->getUuid()?->getString() ?? '';
    }

    public function setUuid(string $uuid): self
    {
        $proto = new UUID();
        $proto->setString($uuid);
        $this->screen->setUuid($proto);

        return $this;
    }

    public function getScreenType(): int
    {
        return $this->screen->getScreenType();
    }

    public function setScreenType(int $screenType): self
    {
        $this->screen->setScreenType($screenType);

        return $this;
    }

    public function getIndex(): ?int
    {
        if ($this->screen->hasArrangementSingle()) {
            $arrangement = $this->screen->getArrangementSingle();
            if ($arrangement !== null && count($arrangement->getScreens()) > 0) {
                return 0;
            }
        }

        return null;
    }

    public function getProto(): ProPresenterScreen
    {
        return $this->screen;
    }
}

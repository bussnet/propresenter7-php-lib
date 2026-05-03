<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Timer as TimerProto;
use Rv\Data\Timer\Configuration;
use Rv\Data\UUID;

class Timer
{
    public function __construct(
        private readonly TimerProto $timer,
    ) {
    }

    public function getUuid(): string
    {
        return $this->timer->getUuid()?->getString() ?? '';
    }

    public function setUuid(string $uuid): self
    {
        $proto = new UUID();
        $proto->setString($uuid);
        $this->timer->setUuid($proto);

        return $this;
    }

    public function getName(): string
    {
        return $this->timer->getName();
    }

    public function setName(string $name): self
    {
        $this->timer->setName($name);

        return $this;
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->timer->getConfiguration();
    }

    public function isCountdown(): bool
    {
        return $this->timer->getConfiguration()?->hasCountdown() ?? false;
    }

    public function isCountdownToTime(): bool
    {
        return $this->timer->getConfiguration()?->hasCountdownToTime() ?? false;
    }

    public function isElapsedTime(): bool
    {
        return $this->timer->getConfiguration()?->hasElapsedTime() ?? false;
    }

    public function getDurationSeconds(): ?int
    {
        $countdown = $this->timer->getConfiguration()?->getCountdown();
        if ($countdown === null) {
            return null;
        }

        return (int) round($countdown->getDuration());
    }

    public function getProto(): TimerProto
    {
        return $this->timer;
    }
}

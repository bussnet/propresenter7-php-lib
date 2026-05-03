<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\ApplicationInfo;
use Rv\Data\Clock;
use Rv\Data\Timer as TimerProto;
use Rv\Data\TimersDocument;
use Rv\Data\UUID;

class TimersLibrary
{
    /** @var Timer[] */
    private array $timers = [];

    /** @var array<string, Timer> */
    private array $timersByUuid = [];

    /** @var array<string, Timer> */
    private array $timersByName = [];

    public function __construct(
        private readonly TimersDocument $document,
    ) {
        $this->rebuildIndex();
    }

    /**
     * @return Timer[]
     */
    public function getTimers(): array
    {
        return $this->timers;
    }

    public function count(): int
    {
        return count($this->timers);
    }

    public function getTimerByUuid(string $uuid): ?Timer
    {
        return $this->timersByUuid[strtoupper($uuid)] ?? null;
    }

    public function getTimerByName(string $name): ?Timer
    {
        return $this->timersByName[$name] ?? null;
    }

    public function addTimer(string $name, string $uuid): Timer
    {
        $proto = new TimerProto();
        $uuidProto = new UUID();
        $uuidProto->setString($uuid);
        $proto->setUuid($uuidProto);
        $proto->setName($name);

        $existing = iterator_to_array($this->document->getTimers());
        $existing[] = $proto;
        $this->document->setTimers($existing);
        $this->rebuildIndex();

        return $this->getTimerByUuid($uuid) ?? new Timer($proto);
    }

    public function removeTimer(string $uuid): bool
    {
        $needle = strtoupper($uuid);
        $kept = [];
        $removed = false;
        foreach ($this->document->getTimers() as $proto) {
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

        $this->document->setTimers($kept);
        $this->rebuildIndex();

        return true;
    }

    public function getClockFormat(): string
    {
        return $this->document->getClock()?->getFormat() ?? '';
    }

    public function setClockFormat(string $format): void
    {
        $clock = $this->document->getClock() ?? new Clock();
        $clock->setFormat($format);
        $this->document->setClock($clock);
    }

    public function getApplicationInfo(): ?ApplicationInfo
    {
        return $this->document->getApplicationInfo();
    }

    public function getDocument(): TimersDocument
    {
        return $this->document;
    }

    private function rebuildIndex(): void
    {
        $this->timers = [];
        $this->timersByUuid = [];
        $this->timersByName = [];

        foreach ($this->document->getTimers() as $proto) {
            $timer = new Timer($proto);
            $this->timers[] = $timer;

            $uuid = strtoupper($timer->getUuid());
            if ($uuid !== '') {
                $this->timersByUuid[$uuid] = $timer;
            }

            $name = $timer->getName();
            if ($name !== '') {
                $this->timersByName[$name] ??= $timer;
            }
        }
    }
}

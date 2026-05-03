<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\CalendarDocument\Event as EventProto;
use Rv\Data\Timestamp;
use Rv\Data\UUID;

class CalendarEvent
{
    public function __construct(
        private readonly EventProto $event,
    ) {
    }

    public function getUuid(): string
    {
        return $this->event->getUuid()?->getString() ?? '';
    }

    public function setUuid(string $uuid): self
    {
        $proto = new UUID();
        $proto->setString($uuid);
        $this->event->setUuid($proto);

        return $this;
    }

    public function getName(): string
    {
        return $this->event->getName();
    }

    public function setName(string $name): self
    {
        $this->event->setName($name);

        return $this;
    }

    public function getStartTime(): ?Timestamp
    {
        return $this->event->getStartTime();
    }

    public function setStartTime(Timestamp $timestamp): self
    {
        $this->event->setStartTime($timestamp);

        return $this;
    }

    public function getStartTimeSeconds(): ?int
    {
        $seconds = $this->event->getStartTime()?->getSeconds();

        return $seconds === null ? null : (int) $seconds;
    }

    public function setStartTimeSeconds(int $seconds): self
    {
        $timestamp = new Timestamp();
        $timestamp->setSeconds($seconds);
        $this->event->setStartTime($timestamp);

        return $this;
    }

    public function getEndTime(): ?Timestamp
    {
        return $this->event->getEndTime();
    }

    public function setEndTime(Timestamp $timestamp): self
    {
        $this->event->setEndTime($timestamp);

        return $this;
    }

    public function getEndTimeSeconds(): ?int
    {
        $seconds = $this->event->getEndTime()?->getSeconds();

        return $seconds === null ? null : (int) $seconds;
    }

    public function setEndTimeSeconds(int $seconds): self
    {
        $timestamp = new Timestamp();
        $timestamp->setSeconds($seconds);
        $this->event->setEndTime($timestamp);

        return $this;
    }

    public function getFlags(): string
    {
        return $this->event->getFlags();
    }

    public function setFlags(string $flags): self
    {
        $this->event->setFlags($flags);

        return $this;
    }

    public function getActionData(): string
    {
        return $this->event->getActionData();
    }

    public function setActionData(string $actionData): self
    {
        $this->event->setActionData($actionData);

        return $this;
    }

    public function getMacroData(): string
    {
        return $this->event->getMacroData();
    }

    public function setMacroData(string $macroData): self
    {
        $this->event->setMacroData($macroData);

        return $this;
    }

    public function getProto(): EventProto
    {
        return $this->event;
    }
}

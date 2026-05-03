<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\CalendarDocument;
use Rv\Data\CalendarDocument\Event as EventProto;
use Rv\Data\UUID;

class CalendarLibrary
{
    /** @var CalendarEvent[] */
    private array $events = [];

    /** @var array<string, CalendarEvent> */
    private array $eventsByUuid = [];

    /** @var array<string, CalendarEvent> */
    private array $eventsByName = [];

    public function __construct(
        private readonly CalendarDocument $document,
    ) {
        $this->rebuildIndex();
    }

    /**
     * @return CalendarEvent[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function count(): int
    {
        return count($this->events);
    }

    public function getEventByUuid(string $uuid): ?CalendarEvent
    {
        return $this->eventsByUuid[strtoupper($uuid)] ?? null;
    }

    public function getEventByName(string $name): ?CalendarEvent
    {
        return $this->eventsByName[$name] ?? null;
    }

    public function addEvent(string $name, string $uuid): CalendarEvent
    {
        $proto = new EventProto();
        $uuidProto = new UUID();
        $uuidProto->setString($uuid);
        $proto->setUuid($uuidProto);
        $proto->setName($name);

        $existing = iterator_to_array($this->document->getEvents());
        $existing[] = $proto;
        $this->document->setEvents($existing);
        $this->rebuildIndex();

        return $this->getEventByUuid($uuid) ?? new CalendarEvent($proto);
    }

    public function removeEvent(string $uuid): bool
    {
        $needle = strtoupper($uuid);
        $kept = [];
        $removed = false;
        foreach ($this->document->getEvents() as $proto) {
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

        $this->document->setEvents($kept);
        $this->rebuildIndex();

        return true;
    }

    public function getMode(): int
    {
        return $this->document->getMode();
    }

    public function setMode(int $mode): void
    {
        $this->document->setMode($mode);
    }

    public function getDocument(): CalendarDocument
    {
        return $this->document;
    }

    private function rebuildIndex(): void
    {
        $this->events = [];
        $this->eventsByUuid = [];
        $this->eventsByName = [];

        foreach ($this->document->getEvents() as $proto) {
            $event = new CalendarEvent($proto);
            $this->events[] = $event;

            $uuid = strtoupper($event->getUuid());
            if ($uuid !== '') {
                $this->eventsByUuid[$uuid] = $event;
            }

            $name = $event->getName();
            if ($name !== '') {
                $this->eventsByName[$name] ??= $event;
            }
        }
    }
}

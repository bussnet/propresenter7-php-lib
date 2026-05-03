<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\CalendarEvent;
use ProPresenter\Parser\CalendarFileReader;
use ProPresenter\Parser\CalendarFileWriter;
use ProPresenter\Parser\CalendarLibrary;

class CalendarFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/Calendar';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CalendarFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-calendar');
    }

    #[Test]
    public function readReturnsLibraryWithExpectedCount(): void
    {
        $library = CalendarFileReader::read(self::REFERENCE_PATH);
        $this->assertInstanceOf(CalendarLibrary::class, $library);
        $this->assertCount(3, $library->getEvents());
        $this->assertSame(3, $library->count());
    }

    #[Test]
    public function eventsExposeNameAndUuid(): void
    {
        $first = CalendarFileReader::read(self::REFERENCE_PATH)->getEvents()[0];
        $this->assertInstanceOf(CalendarEvent::class, $first);
        $this->assertSame('Doors Open', $first->getName());
        $this->assertSame('3E749EF4-0663-4F0F-AACA-BD801B6D8ACD', $first->getUuid());
    }

    #[Test]
    public function eventsHaveExpectedNamesAndOpaqueBytes(): void
    {
        $events = CalendarFileReader::read(self::REFERENCE_PATH)->getEvents();
        $this->assertSame(['Doors Open', 'Godi Start', 'Doors Open'], array_map(static fn (CalendarEvent $event): string => $event->getName(), $events));
        foreach ($events as $event) {
            $this->assertNotSame('', $event->getActionData());
            $this->assertNotSame('', $event->getMacroData());
        }
    }

    #[Test]
    public function lookupAndModeSucceed(): void
    {
        $library = CalendarFileReader::read(self::REFERENCE_PATH);
        $event = $library->getEventByUuid('3e749ef4-0663-4f0f-aaca-bd801b6d8acd');
        $this->assertNotNull($event);
        $this->assertSame('Doors Open', $event->getName());
        $this->assertSame(1, $library->getMode());
        $this->assertSame(1731833100, $event->getStartTimeSeconds());
    }

    #[Test]
    public function addAndRemoveEventRoundTrip(): void
    {
        $library = CalendarFileReader::read(self::REFERENCE_PATH);
        $library->addEvent('Test Event', '11111111-1111-1111-1111-111111111111');
        $this->assertSame(4, $library->count());
        $this->assertNotNull($library->getEventByUuid('11111111-1111-1111-1111-111111111111'));
        $this->assertTrue($library->removeEvent('11111111-1111-1111-1111-111111111111'));
        $this->assertSame(3, $library->count());
    }

    #[Test]
    public function writerProducesByteIdenticalRoundTrip(): void
    {
        $first = tempnam(sys_get_temp_dir(), 'calendar_');
        $second = tempnam(sys_get_temp_dir(), 'calendar_');
        try {
            CalendarFileWriter::write(CalendarFileReader::read(self::REFERENCE_PATH), $first);
            CalendarFileWriter::write(CalendarFileReader::read($first), $second);
            $this->assertSame(file_get_contents($first), file_get_contents($second));
        } finally {
            @unlink($first ?: '');
            @unlink($second ?: '');
        }
    }
}

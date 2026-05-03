<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\Timer;
use ProPresenter\Parser\TimersFileReader;
use ProPresenter\Parser\TimersFileWriter;
use ProPresenter\Parser\TimersLibrary;

class TimersFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/Timers';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TimersFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-timers');
    }

    #[Test]
    public function readReturnsLibraryWithExpectedCount(): void
    {
        $library = TimersFileReader::read(self::REFERENCE_PATH);
        $this->assertInstanceOf(TimersLibrary::class, $library);
        $this->assertCount(5, $library->getTimers());
        $this->assertSame(5, $library->count());
    }

    #[Test]
    public function timersExposeNameAndUuid(): void
    {
        $first = TimersFileReader::read(self::REFERENCE_PATH)->getTimers()[0];
        $this->assertInstanceOf(Timer::class, $first);
        $this->assertSame('Gottesdienst (10:02)', $first->getName());
        $this->assertSame('0E45D0AF-BCC2-4A31-BCFD-0F5A3358E225', $first->getUuid());
    }

    #[Test]
    public function lookupByUuidIsCaseInsensitiveAndClockFormatIsExposed(): void
    {
        $library = TimersFileReader::read(self::REFERENCE_PATH);
        $upper = $library->getTimerByUuid('0E45D0AF-BCC2-4A31-BCFD-0F5A3358E225');
        $lower = $library->getTimerByUuid('0e45d0af-bcc2-4a31-bcfd-0f5a3358e225');
        $this->assertNotNull($upper);
        $this->assertSame($upper, $lower);
        $this->assertSame('HH:mm', $library->getClockFormat());
    }

    #[Test]
    public function timerTypesAreIdentified(): void
    {
        $library = TimersFileReader::read(self::REFERENCE_PATH);
        $service = $library->getTimerByName('Gottesdienst (10:02)');
        $five = $library->getTimerByName('5 Minuten Countdown');
        $this->assertNotNull($service);
        $this->assertTrue($service->isCountdownToTime());
        $this->assertFalse($service->isCountdown());
        $this->assertNotNull($five);
        $this->assertTrue($five->isCountdown());
        $this->assertSame(300, $five->getDurationSeconds());
    }

    #[Test]
    public function addAndRemoveTimerRoundTrip(): void
    {
        $library = TimersFileReader::read(self::REFERENCE_PATH);
        $library->addTimer('Test Timer', '11111111-1111-1111-1111-111111111111');
        $this->assertSame(6, $library->count());
        $this->assertNotNull($library->getTimerByUuid('11111111-1111-1111-1111-111111111111'));
        $this->assertTrue($library->removeTimer('11111111-1111-1111-1111-111111111111'));
        $this->assertSame(5, $library->count());
    }

    #[Test]
    public function writerProducesByteIdenticalRoundTrip(): void
    {
        $first = tempnam(sys_get_temp_dir(), 'timers_');
        $second = tempnam(sys_get_temp_dir(), 'timers_');
        try {
            TimersFileWriter::write(TimersFileReader::read(self::REFERENCE_PATH), $first);
            TimersFileWriter::write(TimersFileReader::read($first), $second);
            $this->assertSame(file_get_contents($first), file_get_contents($second));
        } finally {
            @unlink($first ?: '');
            @unlink($second ?: '');
        }
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\CommunicationDevice;
use ProPresenter\Parser\CommunicationDevicesFileReader;
use ProPresenter\Parser\CommunicationDevicesFileWriter;
use ProPresenter\Parser\CommunicationDevicesLibrary;

class CommunicationDevicesFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/CommunicationDevices';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CommunicationDevicesFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-communication-devices');
    }

    #[Test]
    public function readReturnsLibraryWithExpectedCount(): void
    {
        $library = CommunicationDevicesFileReader::read(self::REFERENCE_PATH);
        $this->assertInstanceOf(CommunicationDevicesLibrary::class, $library);
        $this->assertCount(0, $library->getDevices());
        $this->assertSame(0, $library->count());
    }

    #[Test]
    public function emptyListCanAddDevice(): void
    {
        $library = CommunicationDevicesFileReader::read(self::REFERENCE_PATH);
        $device = (new CommunicationDevice())
            ->setId('device-1')
            ->setName('Lighting Console')
            ->setType('network')
            ->setAddress('192.0.2.10');
        $library->addDevice($device);
        $this->assertSame(1, $library->count());
        $this->assertSame('Lighting Console', $library->getDevices()[0]->getName());
        $this->assertSame('device-1', $library->getDevices()[0]->getId());
    }

    #[Test]
    public function writeReadRoundTripPreservesDeviceSemantics(): void
    {
        $library = new CommunicationDevicesLibrary();
        $library->addDevice((new CommunicationDevice())->setId('device-1')->setName('Stage Router')->setType('tcp')->setAddress('10.0.0.5'));
        $tmp = tempnam(sys_get_temp_dir(), 'devices_');
        try {
            CommunicationDevicesFileWriter::write($library, $tmp);
            $roundTrip = CommunicationDevicesFileReader::read($tmp);
            $this->assertSame($library->getDocument(), $roundTrip->getDocument());
        } finally {
            @unlink($tmp ?: '');
        }
    }

    #[Test]
    public function addAndRemoveDeviceRoundTrip(): void
    {
        $library = CommunicationDevicesFileReader::read(self::REFERENCE_PATH);
        $library->addDevice((new CommunicationDevice())->setId('device-1')->setName('Stage Router'));
        $this->assertSame(1, $library->count());
        $this->assertTrue($library->removeDevice('device-1'));
        $this->assertSame(0, $library->count());
    }

    #[Test]
    public function writerProducesByteIdenticalRoundTrip(): void
    {
        $first = tempnam(sys_get_temp_dir(), 'devices_');
        $second = tempnam(sys_get_temp_dir(), 'devices_');
        try {
            CommunicationDevicesFileWriter::write(CommunicationDevicesFileReader::read(self::REFERENCE_PATH), $first);
            CommunicationDevicesFileWriter::write(CommunicationDevicesFileReader::read($first), $second);
            $this->assertSame(json_decode((string) file_get_contents($first), true), json_decode((string) file_get_contents($second), true));
        } finally {
            @unlink($first ?: '');
            @unlink($second ?: '');
        }
    }
}

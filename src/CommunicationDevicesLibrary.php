<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use RuntimeException;

class CommunicationDevicesLibrary
{
    /** @var CommunicationDevice[] */
    private array $devices = [];

    /**
     * @param CommunicationDevice[] $devices
     */
    public function __construct(array $devices = [])
    {
        $this->devices = array_values($devices);
    }

    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Unable to decode CommunicationDevices JSON: ' . json_last_error_msg());
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('CommunicationDevices JSON must decode to an array.');
        }

        $devices = [];
        foreach ($decoded as $entry) {
            if (is_array($entry)) {
                /** @var array<string, mixed> $entry */
                $devices[] = new CommunicationDevice($entry);
            }
        }

        return new self($devices);
    }

    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): string
    {
        $json = json_encode($this->getDocument(), $flags);
        if ($json === false) {
            throw new RuntimeException('Unable to encode CommunicationDevices JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDocument(): array
    {
        return array_map(static fn (CommunicationDevice $device): array => $device->toArray(), $this->devices);
    }

    /**
     * @return CommunicationDevice[]
     */
    public function getDevices(): array
    {
        return $this->devices;
    }

    public function addDevice(CommunicationDevice $device): CommunicationDevice
    {
        $this->devices[] = $device;

        return $device;
    }

    public function removeDevice(string $id): bool
    {
        foreach ($this->devices as $index => $device) {
            if ($device->getId() === $id) {
                array_splice($this->devices, $index, 1);

                return true;
            }
        }

        return false;
    }

    public function count(): int
    {
        return count($this->devices);
    }
}

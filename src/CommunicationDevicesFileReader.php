<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;

final class CommunicationDevicesFileReader
{
    public static function read(string $filePath): CommunicationDevicesLibrary
    {
        if ($filePath === '' || !is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('CommunicationDevices file not found: %s', $filePath));
        }

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read CommunicationDevices file: %s', $filePath));
        }

        return CommunicationDevicesLibrary::fromJson($contents);
    }
}

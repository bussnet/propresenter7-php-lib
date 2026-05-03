<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;

final class CommunicationDevicesFileWriter
{
    public static function write(CommunicationDevicesLibrary $library, string $filePath): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Target directory does not exist: %s', $directory));
        }

        $json = $library->toJson(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $writtenBytes = file_put_contents($filePath, $json);
        if ($writtenBytes === false) {
            throw new RuntimeException(sprintf('Unable to write CommunicationDevices file: %s', $filePath));
        }
    }
}

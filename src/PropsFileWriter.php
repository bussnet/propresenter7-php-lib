<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;

final class PropsFileWriter
{
    public static function write(PropLibrary $library, string $filePath): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Target directory does not exist: %s', $directory));
        }
        if (file_put_contents($filePath, $library->getDocument()->serializeToString()) === false) {
            throw new RuntimeException(sprintf('Unable to write Props file: %s', $filePath));
        }
    }
}

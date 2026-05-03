<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;

final class StageFileWriter
{
    public static function write(StageLibrary $library, string $filePath): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Target directory does not exist: %s', $directory));
        }

        $writtenBytes = file_put_contents($filePath, $library->getDocument()->serializeToString());
        if ($writtenBytes === false) {
            throw new RuntimeException(sprintf('Unable to write Stage file: %s', $filePath));
        }
    }
}

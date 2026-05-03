<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;

final class MacrosFileWriter
{
    public static function write(MacroLibrary $library, string $filePath): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Target directory does not exist: %s', $directory));
        }

        $data = $library->getDocument()->serializeToString();
        $writtenBytes = file_put_contents($filePath, $data);

        if ($writtenBytes === false) {
            throw new RuntimeException(sprintf('Unable to write Macros file: %s', $filePath));
        }
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;

final class LabelsFileWriter
{
    public static function write(LabelLibrary $library, string $filePath): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Target directory does not exist: %s', $directory));
        }

        $data = $library->getDocument()->serializeToString();
        $writtenBytes = file_put_contents($filePath, $data);

        if ($writtenBytes === false) {
            throw new RuntimeException(sprintf('Unable to write Labels file: %s', $filePath));
        }
    }
}

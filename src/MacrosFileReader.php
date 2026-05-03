<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use Rv\Data\MacrosDocument;

/**
 * Reader for the global ProPresenter `Macros` file (a raw protobuf
 * serialisation of {@see MacrosDocument}, no extension).
 */
final class MacrosFileReader
{
    public static function read(string $filePath): MacroLibrary
    {
        if ($filePath === '' || !is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('Macros file not found: %s', $filePath));
        }

        $size = filesize($filePath);
        if ($size === false) {
            throw new RuntimeException(sprintf('Unable to determine file size: %s', $filePath));
        }

        if ($size === 0) {
            throw new RuntimeException(sprintf('Macros file is empty: %s', $filePath));
        }

        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new RuntimeException(sprintf('Unable to read macros file: %s', $filePath));
        }

        $document = new MacrosDocument();
        $document->mergeFromString($data);

        return new MacroLibrary($document);
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use Rv\Data\KeyMappingsDocument;

final class KeyMappingsFileReader
{
    public static function read(string $filePath): KeyMappingsLibrary
    {
        if ($filePath === '' || !is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('KeyMappings file not found: %s', $filePath));
        }

        $size = filesize($filePath);
        if ($size === false) {
            throw new RuntimeException(sprintf('Unable to determine file size: %s', $filePath));
        }

        if ($size === 0) {
            throw new RuntimeException(sprintf('KeyMappings file is empty: %s', $filePath));
        }

        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new RuntimeException(sprintf('Unable to read KeyMappings file: %s', $filePath));
        }

        $document = new KeyMappingsDocument();
        $document->mergeFromString($data);

        return new KeyMappingsLibrary($document);
    }
}

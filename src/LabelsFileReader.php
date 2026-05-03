<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use Rv\Data\ProLabelsDocument;

/**
 * Reader for the global ProPresenter `Labels` file (a raw protobuf
 * serialisation of {@see ProLabelsDocument}, no extension).
 */
final class LabelsFileReader
{
    public static function read(string $filePath): LabelLibrary
    {
        if ($filePath === '' || !is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('Labels file not found: %s', $filePath));
        }

        $size = filesize($filePath);
        if ($size === false) {
            throw new RuntimeException(sprintf('Unable to determine file size: %s', $filePath));
        }

        if ($size === 0) {
            throw new RuntimeException(sprintf('Labels file is empty: %s', $filePath));
        }

        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new RuntimeException(sprintf('Unable to read labels file: %s', $filePath));
        }

        $document = new ProLabelsDocument();
        $document->mergeFromString($data);

        return new LabelLibrary($document);
    }
}

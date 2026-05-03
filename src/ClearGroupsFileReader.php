<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use Rv\Data\ClearGroupsDocument;

final class ClearGroupsFileReader
{
    public static function read(string $filePath): ClearGroupsLibrary
    {
        if ($filePath === '' || !is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('ClearGroups file not found: %s', $filePath));
        }

        $size = filesize($filePath);
        if ($size === false) {
            throw new RuntimeException(sprintf('Unable to determine file size: %s', $filePath));
        }

        if ($size === 0) {
            throw new RuntimeException(sprintf('ClearGroups file is empty: %s', $filePath));
        }

        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new RuntimeException(sprintf('Unable to read ClearGroups file: %s', $filePath));
        }

        $document = new ClearGroupsDocument();
        $document->mergeFromString($data);

        return new ClearGroupsLibrary($document);
    }
}

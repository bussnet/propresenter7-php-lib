<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use Rv\Data\CalendarDocument;

final class CalendarFileReader
{
    public static function read(string $filePath): CalendarLibrary
    {
        if ($filePath === '' || !is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('Calendar file not found: %s', $filePath));
        }

        $size = filesize($filePath);
        if ($size === false) {
            throw new RuntimeException(sprintf('Unable to determine file size: %s', $filePath));
        }
        if ($size === 0) {
            throw new RuntimeException(sprintf('Calendar file is empty: %s', $filePath));
        }

        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new RuntimeException(sprintf('Unable to read Calendar file: %s', $filePath));
        }

        $document = new CalendarDocument();
        $document->mergeFromString($data);

        return new CalendarLibrary($document);
    }
}

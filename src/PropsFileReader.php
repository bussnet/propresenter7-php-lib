<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use Rv\Data\PropDocument;

final class PropsFileReader
{
    public static function read(string $filePath): PropLibrary
    {
        if ($filePath === '' || !is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('Props file not found: %s', $filePath));
        }
        $size = filesize($filePath);
        if ($size === false) {
            throw new RuntimeException(sprintf('Unable to determine file size: %s', $filePath));
        }
        if ($size === 0) {
            throw new RuntimeException(sprintf('Props file is empty: %s', $filePath));
        }
        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new RuntimeException(sprintf('Unable to read Props file: %s', $filePath));
        }

        $document = new PropDocument();
        $document->mergeFromString($data);

        return new PropLibrary($document);
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use Rv\Data\TestPatternDocument;

final class TestPatternsFileReader
{
    public static function read(string $filePath): TestPatternsLibrary
    {
        if ($filePath === '' || !is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('TestPatterns file not found: %s', $filePath));
        }

        $size = filesize($filePath);
        if ($size === false) {
            throw new RuntimeException(sprintf('Unable to determine file size: %s', $filePath));
        }

        if ($size === 0) {
            throw new RuntimeException(sprintf('TestPatterns file is empty: %s', $filePath));
        }

        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new RuntimeException(sprintf('Unable to read TestPatterns file: %s', $filePath));
        }

        $document = new TestPatternDocument();
        $document->mergeFromString($data);

        return new TestPatternsLibrary($document);
    }
}

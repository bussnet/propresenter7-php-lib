<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use Rv\Data\ProPresenterWorkspace;

final class WorkspaceFileReader
{
    public static function read(string $filePath): WorkspaceLibrary
    {
        if ($filePath === '' || !is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('Workspace file not found: %s', $filePath));
        }
        $size = filesize($filePath);
        if ($size === false) {
            throw new RuntimeException(sprintf('Unable to determine file size: %s', $filePath));
        }
        if ($size === 0) {
            throw new RuntimeException(sprintf('Workspace file is empty: %s', $filePath));
        }
        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new RuntimeException(sprintf('Unable to read Workspace file: %s', $filePath));
        }

        $document = new ProPresenterWorkspace();
        $document->mergeFromString($data);

        return new WorkspaceLibrary($document);
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

final class ProPlaylistWriter
{
    public static function write(PlaylistArchive $archive, string $filePath): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Target directory does not exist: %s', $directory));
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'proplaylist-');
        if ($tempPath === false) {
            throw new RuntimeException('Unable to create temporary file for playlist archive.');
        }

        $zip = new ZipArchive();
        $isOpen = false;

        try {
            $openResult = $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($openResult !== true) {
                throw new RuntimeException(sprintf('Failed to create playlist archive: %s', $filePath));
            }
            $isOpen = true;

            $protoBytes = $archive->getDocument()->serializeToString();
            self::addStoredEntry($zip, 'data', $protoBytes, $filePath);

            foreach ($archive->getEmbeddedFiles() as $entryName => $contents) {
                self::addStoredEntry($zip, $entryName, $contents, $filePath);
            }

            if (!$zip->close()) {
                throw new RuntimeException(sprintf('Failed to finalize playlist archive: %s', $filePath));
            }
            $isOpen = false;

            self::moveTempFileToTarget($tempPath, $filePath);
        } finally {
            if ($isOpen) {
                $zip->close();
            }
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private static function addStoredEntry(ZipArchive $zip, string $entryName, string $contents, string $filePath): void
    {
        if (!$zip->addFromString($entryName, $contents)) {
            throw new RuntimeException(sprintf('Failed to add ZIP entry %s: %s', $entryName, $filePath));
        }

        if (!$zip->setCompressionName($entryName, ZipArchive::CM_STORE)) {
            throw new RuntimeException(sprintf('Failed to set store compression for %s: %s', $entryName, $filePath));
        }
    }

    private static function moveTempFileToTarget(string $tempPath, string $filePath): void
    {
        if (@rename($tempPath, $filePath)) {
            return;
        }

        if (@copy($tempPath, $filePath) && @unlink($tempPath)) {
            return;
        }

        throw new RuntimeException(sprintf('Unable to write playlist file: %s', $filePath));
    }
}

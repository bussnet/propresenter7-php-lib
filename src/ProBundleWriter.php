<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

final class ProBundleWriter
{
    public static function write(PresentationBundle $bundle, string $filePath): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Target directory does not exist: %s', $directory));
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'probundle-');
        if ($tempPath === false) {
            throw new RuntimeException('Unable to create temporary file for bundle archive.');
        }

        $zip = new ZipArchive();
        $isOpen = false;

        try {
            $openResult = $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($openResult !== true) {
                throw new RuntimeException(sprintf('Failed to create bundle archive: %s', $filePath));
            }
            $isOpen = true;

            foreach ($bundle->getMediaFiles() as $entryName => $contents) {
                self::addEntry($zip, basename($entryName), $contents, $filePath);
            }

            $proBytes = $bundle->getPresentation()->serializeToString();
            self::addEntry($zip, $bundle->getProFilename(), $proBytes, $filePath);

            if (!$zip->close()) {
                throw new RuntimeException(sprintf('Failed to finalize bundle archive: %s', $filePath));
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

    private static function addEntry(ZipArchive $zip, string $entryName, string $contents, string $filePath): void
    {
        if (!$zip->addFromString($entryName, $contents)) {
            throw new RuntimeException(sprintf('Failed to add ZIP entry %s: %s', $entryName, $filePath));
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

        throw new RuntimeException(sprintf('Unable to write bundle file: %s', $filePath));
    }
}

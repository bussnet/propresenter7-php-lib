<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use Rv\Data\Presentation;
use ZipArchive;

final class ProBundleReader
{
    public static function read(string $filePath): PresentationBundle
    {
        if ($filePath === '' || !is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('Bundle file not found: %s', $filePath));
        }

        $size = filesize($filePath);
        if ($size === false) {
            throw new RuntimeException(sprintf('Unable to determine file size: %s', $filePath));
        }

        if ($size === 0) {
            throw new RuntimeException(sprintf('Bundle file is empty: %s', $filePath));
        }

        $rawBytes = file_get_contents($filePath);
        if ($rawBytes === false) {
            throw new RuntimeException(sprintf('Unable to read bundle file: %s', $filePath));
        }

        $fixedBytes = Zip64Fixer::fix($rawBytes);

        $tempPath = tempnam(sys_get_temp_dir(), 'probundle-');
        if ($tempPath === false) {
            throw new RuntimeException('Unable to create temporary file for bundle archive.');
        }

        $zip = new ZipArchive();
        $isOpen = false;

        try {
            if (file_put_contents($tempPath, $fixedBytes) === false) {
                throw new RuntimeException(sprintf('Unable to write temporary bundle archive: %s', $filePath));
            }

            if ($zip->open($tempPath) !== true) {
                throw new RuntimeException(sprintf('Failed to open bundle archive: %s', $filePath));
            }
            $isOpen = true;

            $proFilename = self::findProFile($zip, $filePath);

            $proBytes = $zip->getFromName($proFilename);
            if ($proBytes === false) {
                throw new RuntimeException(sprintf('Unable to read .pro entry %s: %s', $proFilename, $filePath));
            }

            $presentation = new Presentation();
            $presentation->mergeFromString($proBytes);
            $song = new Song($presentation);

            $mediaFiles = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name === false || $name === $proFilename) {
                    continue;
                }

                $contents = $zip->getFromIndex($i);
                if ($contents === false) {
                    throw new RuntimeException(sprintf('Unable to read ZIP entry %s: %s', $name, $filePath));
                }

                $mediaFiles[$name] = $contents;
            }

            return new PresentationBundle($song, $proFilename, $mediaFiles);
        } finally {
            if ($isOpen) {
                $zip->close();
            }
            @unlink($tempPath);
        }
    }

    private static function findProFile(ZipArchive $zip, string $filePath): string
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name !== false && str_ends_with(strtolower($name), '.pro')) {
                return $name;
            }
        }

        throw new RuntimeException(sprintf('No .pro file found in bundle archive: %s', $filePath));
    }
}

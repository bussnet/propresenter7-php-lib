<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use Rv\Data\PlaylistDocument;
use ZipArchive;

final class ProPlaylistReader
{
    public static function read(string $filePath): PlaylistArchive
    {
        if ($filePath === '' || !is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('Playlist file not found: %s', $filePath));
        }

        $size = filesize($filePath);
        if ($size === false) {
            throw new RuntimeException(sprintf('Unable to determine file size: %s', $filePath));
        }

        if ($size === 0) {
            throw new RuntimeException(sprintf('Playlist file is empty: %s', $filePath));
        }

        $rawBytes = file_get_contents($filePath);
        if ($rawBytes === false) {
            throw new RuntimeException(sprintf('Unable to read playlist file: %s', $filePath));
        }

        $fixedBytes = Zip64Fixer::fix($rawBytes);

        $tempPath = tempnam(sys_get_temp_dir(), 'proplaylist-');
        if ($tempPath === false) {
            throw new RuntimeException('Unable to create temporary file for playlist archive.');
        }

        $zip = new ZipArchive();
        $isOpen = false;

        try {
            if (file_put_contents($tempPath, $fixedBytes) === false) {
                throw new RuntimeException(sprintf('Unable to write temporary playlist archive: %s', $filePath));
            }

            if ($zip->open($tempPath) !== true) {
                throw new RuntimeException(sprintf('Failed to open playlist archive: %s', $filePath));
            }
            $isOpen = true;

            $dataBytes = $zip->getFromName('data');
            if ($dataBytes === false) {
                throw new RuntimeException(sprintf('Missing data entry in playlist archive: %s', $filePath));
            }

            $document = new PlaylistDocument();
            $document->mergeFromString($dataBytes);

            $embeddedFiles = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name === false || $name === 'data') {
                    continue;
                }

                $contents = $zip->getFromIndex($i);
                if ($contents === false) {
                    throw new RuntimeException(sprintf('Unable to read ZIP entry %s: %s', $name, $filePath));
                }

                $embeddedFiles[$name] = $contents;
            }

            return new PlaylistArchive($document, $embeddedFiles);
        } finally {
            if ($isOpen) {
                $zip->close();
            }
            @unlink($tempPath);
        }
    }
}

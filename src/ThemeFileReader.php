<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;
use Rv\Data\Template\Document;

final class ThemeFileReader
{
    public static function read(string $folderPath): ThemeBundle
    {
        if ($folderPath === '' || !is_dir($folderPath)) {
            throw new InvalidArgumentException(sprintf('Theme folder not found: %s', $folderPath));
        }

        $themePath = rtrim($folderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Theme';
        if (!is_file($themePath)) {
            throw new InvalidArgumentException(sprintf('Theme file not found: %s', $themePath));
        }

        $data = file_get_contents($themePath);
        if ($data === false) {
            throw new RuntimeException(sprintf('Unable to read Theme file: %s', $themePath));
        }

        $document = new Document();
        $document->mergeFromString($data);

        return new ThemeBundle($document, self::readAssets($folderPath));
    }

    /** @return ThemeAsset[] */
    private static function readAssets(string $folderPath): array
    {
        $assetsPath = rtrim($folderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Assets';
        if (!is_dir($assetsPath)) {
            return [];
        }

        $entries = scandir($assetsPath);
        if ($entries === false) {
            throw new RuntimeException(sprintf('Unable to scan Assets directory: %s', $assetsPath));
        }

        $assets = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $assetsPath . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($path)) {
                continue;
            }
            $bytes = file_get_contents($path);
            if ($bytes === false) {
                throw new RuntimeException(sprintf('Unable to read Theme asset: %s', $path));
            }
            $assets[] = new ThemeAsset($entry, $bytes);
        }

        usort($assets, static fn (ThemeAsset $a, ThemeAsset $b): int => strcmp($a->getName(), $b->getName()));

        return $assets;
    }
}

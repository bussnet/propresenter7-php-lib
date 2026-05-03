<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use RuntimeException;

final class ThemeFileWriter
{
    public static function write(ThemeBundle $bundle, string $folderPath): void
    {
        if (!is_dir($folderPath) && !mkdir($folderPath, 0777, true)) {
            throw new RuntimeException(sprintf('Unable to create Theme folder: %s', $folderPath));
        }

        $themePath = rtrim($folderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Theme';
        if (file_put_contents($themePath, $bundle->getDocument()->serializeToString()) === false) {
            throw new RuntimeException(sprintf('Unable to write Theme file: %s', $themePath));
        }

        $assetsPath = rtrim($folderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Assets';
        if (!is_dir($assetsPath) && !mkdir($assetsPath, 0777, true)) {
            throw new RuntimeException(sprintf('Unable to create Assets directory: %s', $assetsPath));
        }

        $expected = [];
        foreach ($bundle->getAssets() as $asset) {
            $name = basename($asset->getName());
            $expected[$name] = true;
            $path = $assetsPath . DIRECTORY_SEPARATOR . $name;
            if (file_put_contents($path, $asset->getBytes()) === false) {
                throw new RuntimeException(sprintf('Unable to write Theme asset: %s', $path));
            }
        }

        $entries = scandir($assetsPath);
        if ($entries === false) {
            throw new RuntimeException(sprintf('Unable to scan Assets directory: %s', $assetsPath));
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || isset($expected[$entry])) {
                continue;
            }
            $path = $assetsPath . DIRECTORY_SEPARATOR . $entry;
            if (is_file($path) && !unlink($path)) {
                throw new RuntimeException(sprintf('Unable to remove stale Theme asset: %s', $path));
            }
        }
    }
}

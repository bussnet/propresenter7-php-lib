#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\ThemeFileReader;

if ($argc < 2) {
    echo "Usage: parse-theme.php <theme-folder>\n";
    exit(1);
}

try {
    $bundle = ThemeFileReader::read($argv[1]);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

echo "Theme folder: {$argv[1]}\n";
echo 'Slides (' . $bundle->count() . "):\n";
foreach ($bundle->getSlides() as $index => $slide) {
    echo sprintf("  [%d] %s\n", $index + 1, $slide->getName() ?: '(unnamed)');
}
echo 'Assets (' . $bundle->getAssetCount() . "):\n";
foreach ($bundle->getAssets() as $index => $asset) {
    echo sprintf("  [%d] Assets/%s :: %d bytes :: %s\n", $index + 1, $asset->getName(), $asset->getSize(), $asset->getMimeType());
}

#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\StageFileReader;

if ($argc < 2) {
    echo "Usage: parse-stage.php <Stage>\n";
    exit(1);
}

try {
    $library = StageFileReader::read($argv[1]);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

echo 'Stage layouts (' . $library->count() . "):\n";
foreach ($library->getLayouts() as $index => $layout) {
    echo sprintf("  [%d] %s :: %s\n", $index + 1, $layout->getName() ?: '(unnamed)', $layout->getUuid());
}

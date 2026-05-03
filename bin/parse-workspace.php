#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\WorkspaceFileReader;

if ($argc < 2) {
    echo "Usage: parse-workspace.php <Workspace>\n";
    exit(1);
}

try {
    $library = WorkspaceFileReader::read($argv[1]);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

echo 'Screens (' . $library->count() . "):\n";
foreach ($library->getScreens() as $index => $screen) {
    echo sprintf("  [%d] %s :: %s :: type %d\n", $index + 1, $screen->getName() ?: '(unnamed)', $screen->getUuid(), $screen->getScreenType());
}

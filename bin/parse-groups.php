#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\GroupsFileReader;

if ($argc < 2) {
    echo "Usage: parse-groups.php <Groups>\n";
    exit(1);
}

$filePath = $argv[1];

try {
    $library = GroupsFileReader::read($filePath);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

$groups = $library->getGroups();

echo "Groups (" . count($groups) . "):\n";
foreach ($groups as $index => $group) {
    $number = $index + 1;
    $name = $group->getName();
    $displayName = $name === '' ? '(unnamed)' : $name;
    $uuid = $group->getUuid();
    $colorPart = $group->getColorHex() ?? '(no color)';

    echo sprintf("  [%d] %s :: %s :: %s\n", $number, $displayName, $uuid, $colorPart);
}

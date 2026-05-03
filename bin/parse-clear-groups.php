#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\ClearGroupsFileReader;

if ($argc < 2) {
    echo "Usage: parse-clear-groups.php <ClearGroups>\n";
    exit(1);
}

$filePath = $argv[1];

try {
    $library = ClearGroupsFileReader::read($filePath);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

$groups = $library->getGroups();

echo "ClearGroups (" . count($groups) . "):\n";
foreach ($groups as $index => $group) {
    $number = $index + 1;
    $name = $group->getName();
    $displayName = $name === '' ? '(unnamed)' : $name;
    $uuid = $group->getUuid();
    $colorPart = $group->getColorHex() ?? '(no tint)';

    echo sprintf("  [%d] %s :: %s :: image_type=%d :: %s\n", $number, $displayName, $uuid, $group->getImageType(), $colorPart);
}

#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\KeyMappingsFileReader;

if ($argc < 2) {
    echo "Usage: parse-key-mappings.php <KeyMappings>\n";
    exit(1);
}

$filePath = $argv[1];

try {
    $library = KeyMappingsFileReader::read($filePath);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

$mappings = $library->getMappings();

echo "KeyMappings (" . count($mappings) . "):\n";
foreach ($mappings as $index => $mapping) {
    $number = $index + 1;
    $name = $mapping->getName();
    $displayName = $name === '' ? '(unnamed)' : $name;
    $uuid = $mapping->getUuid();
    $hotKeyPart = $mapping->getHotKey() === null ? '(no hot key)' : 'hot_key=yes';

    echo sprintf("  [%d] %s :: %s :: target_bytes=%d :: %s\n", $number, $displayName, $uuid, strlen($mapping->getTarget()), $hotKeyPart);
}

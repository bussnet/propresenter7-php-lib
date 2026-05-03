#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\TestPatternsFileReader;

if ($argc < 2) {
    echo "Usage: parse-test-patterns.php <TestPatterns>\n";
    exit(1);
}

$filePath = $argv[1];

try {
    $library = TestPatternsFileReader::read($filePath);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "TestPatterns (" . $library->count() . "):\n";
echo sprintf("  State: selected=%s :: name=%s :: display_location=%d :: screen=%s\n", $library->getSelectedPatternUuid() === '' ? '(none)' : $library->getSelectedPatternUuid(), $library->getSelectedPatternNameLocalizationKey() === '' ? '(none)' : $library->getSelectedPatternNameLocalizationKey(), $library->getDisplayLocation(), $library->getSpecificScreenUuid() === '' ? '(none)' : $library->getSpecificScreenUuid());

foreach ($library->getPatterns() as $index => $pattern) {
    $number = $index + 1;
    $name = $pattern->getNameLocalizationKey();
    $displayName = $name === '' ? '(unnamed)' : $name;
    $uuid = $pattern->getUuid()?->getString() ?? '';

    echo sprintf("  [%d] %s :: %s\n", $number, $displayName, $uuid);
}

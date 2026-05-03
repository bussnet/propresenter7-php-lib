#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\CCLIFileReader;

if ($argc < 2) {
    echo "Usage: parse-ccli.php <CCLI>\n";
    exit(1);
}

$filePath = $argv[1];

try {
    $library = CCLIFileReader::read($filePath);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "CCLI (1):\n";
echo sprintf("  [1] enabled=%s :: license=%s :: display_type=%d :: template=%s\n", $library->isCCLIDisplayEnabled() ? 'yes' : 'no', $library->getCCLILicense() === '' ? '(empty)' : $library->getCCLILicense(), $library->getDisplayType(), $library->getTemplate() === null ? 'no' : 'yes');

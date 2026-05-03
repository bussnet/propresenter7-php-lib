#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\PropsFileReader;

if ($argc < 2) {
    echo "Usage: parse-props.php <Props>\n";
    exit(1);
}

try {
    $library = PropsFileReader::read($argv[1]);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

echo 'Props (' . $library->count() . "):\n";
foreach ($library->getProps() as $index => $prop) {
    echo sprintf("  [%d] %s :: %s :: %s\n", $index + 1, $prop->getName() ?: '(unnamed)', $prop->getUuid(), $prop->isEnabled() ? 'enabled' : 'disabled');
}

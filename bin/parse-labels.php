#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\LabelsFileReader;

if ($argc < 2) {
    echo "Usage: parse-labels.php <Labels>\n";
    exit(1);
}

$filePath = $argv[1];

try {
    $library = LabelsFileReader::read($filePath);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

$labels = $library->getLabels();

echo "Labels (" . count($labels) . "):\n";
foreach ($labels as $index => $label) {
    $number = $index + 1;
    $name = $label->getName();
    $displayName = $name === '' ? '(unnamed)' : $name;

    if ($label->hasColor()) {
        $color = $label->getColor();
        $colorPart = sprintf(
            '%s  rgba(%.3f, %.3f, %.3f, %.3f)',
            $label->getColorHex(),
            $color['r'],
            $color['g'],
            $color['b'],
            $color['a'],
        );
    } else {
        $colorPart = '(no color)';
    }

    echo sprintf("  [%d] %s :: %s\n", $number, $displayName, $colorPart);
}

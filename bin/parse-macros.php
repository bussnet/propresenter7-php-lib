#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\MacrosFileReader;

if ($argc < 2) {
    echo "Usage: parse-macros.php <Macros>\n";
    exit(1);
}

$filePath = $argv[1];

try {
    $library = MacrosFileReader::read($filePath);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

$macros = $library->getMacros();
$collections = $library->getCollections();

echo "Macros (" . count($macros) . "):\n";
foreach ($macros as $index => $macro) {
    $number = $index + 1;
    $name = $macro->getName();
    $uuid = $macro->getUuid();
    $actionCount = $macro->getActionCount();
    $startup = $macro->getTriggerOnStartup() ? ' (startup)' : '';

    $memberships = $library->getCollectionsForMacro($macro);
    $collectionNames = array_map(fn ($c) => $c->getName(), $memberships);
    $collectionSuffix = $collectionNames === [] ? '' : ' [in: ' . implode(', ', $collectionNames) . ']';

    $displayName = $name === '' ? '(unnamed)' : $name;
    echo "  [" . $number . "] " . $displayName . " :: " . $uuid . " (" . $actionCount . " action" . ($actionCount !== 1 ? "s" : "") . ")" . $startup . $collectionSuffix . "\n";
}

echo "\n";

if ($collections === []) {
    echo "Collections: (none)\n";
    exit(0);
}

echo "Collections (" . count($collections) . "):\n";
foreach ($collections as $index => $collection) {
    $number = $index + 1;
    $name = $collection->getName();
    $uuid = $collection->getUuid();
    $resolvedMacros = $library->getMacrosForCollection($collection);
    $count = count($resolvedMacros);

    $displayName = $name === '' ? '(unnamed)' : $name;
    echo "  [" . $number . "] " . $displayName . " :: " . $uuid . " (" . $count . " macro" . ($count !== 1 ? "s" : "") . ")\n";

    foreach ($resolvedMacros as $macroIndex => $macro) {
        $macroNumber = $macroIndex + 1;
        $macroName = $macro->getName() === '' ? '(unnamed)' : $macro->getName();
        echo "      " . $macroNumber . ". " . $macroName . " :: " . $macro->getUuid() . "\n";
    }
}

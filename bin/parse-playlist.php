#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\ProPlaylistReader;

// Check for required argument
if ($argc < 2) {
    echo "Usage: parse-playlist.php <file.proplaylist>\n";
    exit(1);
}

$filePath = $argv[1];

// Try to read the playlist file
try {
    $archive = ProPlaylistReader::read($filePath);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Display playlist header
echo "Playlist: " . $archive->getName() . "\n";
echo "UUID: " . $archive->getRootNode()->getUuid() . "\n";

// Application info
$appInfo = $archive->getDocument()->getApplicationInfo();
$platformVersion = $appInfo->getPlatformVersion();
$appVersion = $appInfo->getApplicationVersion();
$platformStr = '';
$appStr = '';

if ($platformVersion !== null) {
    $platformStr = $platformVersion->getMajorVersion() . '.' . $platformVersion->getMinorVersion() . '.' . $platformVersion->getPatchVersion();
    if ($platformVersion->getBuild() !== '') {
        $platformStr .= ' (' . $platformVersion->getBuild() . ')';
    }
}

if ($appVersion !== null) {
    $appStr = $appVersion->getMajorVersion() . '.' . $appVersion->getMinorVersion() . '.' . $appVersion->getPatchVersion();
    if ($appVersion->getBuild() !== '') {
        $appStr .= ' (' . $appVersion->getBuild() . ')';
    }
}

echo "Application: " . $platformStr . " " . $appStr . "\n";

// Document type
echo "Type: " . $archive->getType() . "\n";

echo "\n";

// Embedded files summary
$proFiles = $archive->getEmbeddedProFiles();
$mediaFiles = $archive->getEmbeddedMediaFiles();
echo "Embedded Files: " . count($proFiles) . " .pro files, " . count($mediaFiles) . " media files\n";

echo "\n";

// Entries
$entries = $archive->getEntries();
echo "Entries (" . count($entries) . "):\n";

foreach ($entries as $entry) {
    $prefix = match($entry->getType()) {
        'header' => '[H]',
        'presentation' => '[P]',
        'placeholder' => '[-]',
        'cue' => '[C]',
        default => '[?]',
    };

    echo $prefix . " " . $entry->getName();

    if ($entry->isHeader()) {
        $color = $entry->getHeaderColor();
        if ($color) {
            echo " (color: " . implode(',', $color) . ")";
        }
    } elseif ($entry->isPresentation()) {
        $arrName = $entry->getArrangementName();
        if ($arrName) {
            echo " (arrangement: " . $arrName . ")";
        }
        $path = $entry->getDocumentPath();
        if ($path) {
            echo " - " . $path;
        }
    }

    echo "\n";
}

echo "\n";

// Embedded .pro files
if (!empty($proFiles)) {
    echo "Embedded .pro Files:\n";
    foreach (array_keys($proFiles) as $filename) {
        echo "- " . $filename . "\n";
    }
    echo "\n";
}

// Embedded media files
if (!empty($mediaFiles)) {
    echo "Embedded Media Files:\n";
    foreach (array_keys($mediaFiles) as $filename) {
        echo "- " . $filename . "\n";
    }
}

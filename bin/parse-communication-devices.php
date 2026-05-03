#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\CommunicationDevicesFileReader;

if ($argc < 2) {
    echo "Usage: parse-communication-devices.php <CommunicationDevices>\n";
    exit(1);
}

try {
    $library = CommunicationDevicesFileReader::read($argv[1]);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

$devices = $library->getDevices();
echo 'Communication devices (' . count($devices) . "):\n";
if ($devices === []) {
    echo "  (none configured)\n";
}
foreach ($devices as $index => $device) {
    echo sprintf("  [%d] %s :: %s :: %s :: %s\n", $index + 1, $device->getName() === '' ? '(unnamed)' : $device->getName(), $device->getId(), $device->getType(), $device->getAddress());
}

#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\CalendarFileReader;

if ($argc < 2) {
    echo "Usage: parse-calendar.php <Calendar>\n";
    exit(1);
}

try {
    $library = CalendarFileReader::read($argv[1]);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

$events = $library->getEvents();
echo 'Calendar events (' . count($events) . ') mode=' . $library->getMode() . ":\n";
foreach ($events as $index => $event) {
    echo sprintf("  [%d] %s :: %s :: start=%s :: end=%s :: action=%dB :: macro=%dB\n", $index + 1, $event->getName() === '' ? '(unnamed)' : $event->getName(), $event->getUuid(), (string) ($event->getStartTimeSeconds() ?? ''), (string) ($event->getEndTimeSeconds() ?? ''), strlen($event->getActionData()), strlen($event->getMacroData()));
}

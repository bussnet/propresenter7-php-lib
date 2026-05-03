#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\TimersFileReader;

if ($argc < 2) {
    echo "Usage: parse-timers.php <Timers>\n";
    exit(1);
}

try {
    $library = TimersFileReader::read($argv[1]);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

$timers = $library->getTimers();
echo 'Timers (' . count($timers) . ') clock=' . $library->getClockFormat() . ":\n";
foreach ($timers as $index => $timer) {
    $type = $timer->isCountdown() ? 'countdown' : ($timer->isCountdownToTime() ? 'countdown_to_time' : ($timer->isElapsedTime() ? 'elapsed_time' : 'unknown'));
    $duration = $timer->getDurationSeconds();
    $durationPart = $duration === null ? '' : sprintf(' :: %ds', $duration);
    echo sprintf("  [%d] %s :: %s :: %s%s\n", $index + 1, $timer->getName() === '' ? '(unnamed)' : $timer->getName(), $timer->getUuid(), $type, $durationPart);
}

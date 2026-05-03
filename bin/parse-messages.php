#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\MessagesFileReader;

if ($argc < 2) {
    echo "Usage: parse-messages.php <Messages>\n";
    exit(1);
}

try {
    $library = MessagesFileReader::read($argv[1]);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

$messages = $library->getMessages();
echo 'Messages (' . count($messages) . "):\n";
foreach ($messages as $index => $message) {
    $title = $message->getTitle() === '' ? '(untitled)' : $message->getTitle();
    echo sprintf("  [%d] %s :: %s :: clear=%d :: network=%s\n", $index + 1, $title, $message->getUuid(), $message->getClearType(), $message->isVisibleOnNetwork() ? 'yes' : 'no');
}

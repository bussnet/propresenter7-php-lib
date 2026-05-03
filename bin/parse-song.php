#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ProPresenter\Parser\ProFileReader;

// Check for required argument
if ($argc < 2) {
    echo "Usage: parse-song.php <file.pro>\n";
    exit(1);
}

$filePath = $argv[1];

// Try to read the song file
try {
    $song = ProFileReader::read($filePath);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Display song header
echo "Song: " . $song->getName() . "\n";
echo "UUID: " . $song->getUuid() . "\n";

// Display metadata
$category = $song->getCategory();
$notes = $song->getNotes();
$selectedArrangement = $song->getSelectedArrangementUuid();

if ($category !== '') {
    echo "Category: " . $category . "\n";
}
if ($notes !== '') {
    echo "Notes: " . $notes . "\n";
}
if ($selectedArrangement !== '') {
    echo "Selected Arrangement: " . $selectedArrangement . "\n";
}

// Display CCLI metadata
$ccliAuthor = $song->getCcliAuthor();
$ccliTitle = $song->getCcliSongTitle();
$ccliPublisher = $song->getCcliPublisher();
$ccliYear = $song->getCcliCopyrightYear();
$ccliNumber = $song->getCcliSongNumber();
$ccliDisplay = $song->getCcliDisplay();
$ccliCredits = $song->getCcliArtistCredits();
$ccliAlbum = $song->getCcliAlbum();

$hasCcli = $ccliAuthor !== '' || $ccliTitle !== '' || $ccliPublisher !== '' || $ccliYear !== 0 || $ccliNumber !== 0 || $ccliCredits !== '' || $ccliAlbum !== '';

if ($hasCcli) {
    echo "\nCCLI Metadata:\n";
    if ($ccliTitle !== '') {
        echo "  Song Title: " . $ccliTitle . "\n";
    }
    if ($ccliAuthor !== '') {
        echo "  Author: " . $ccliAuthor . "\n";
    }
    if ($ccliPublisher !== '') {
        echo "  Publisher: " . $ccliPublisher . "\n";
    }
    if ($ccliYear !== 0) {
        echo "  Copyright Year: " . $ccliYear . "\n";
    }
    if ($ccliNumber !== 0) {
        echo "  Song Number: " . $ccliNumber . "\n";
    }
    if ($ccliCredits !== '') {
        echo "  Artist Credits: " . $ccliCredits . "\n";
    }
    if ($ccliAlbum !== '') {
        echo "  Album: " . $ccliAlbum . "\n";
    }
    echo "  Display: " . ($ccliDisplay ? 'yes' : 'no') . "\n";
}

echo "\n";

// Display groups
$groups = $song->getGroups();
echo "Groups (" . count($groups) . "):\n";

foreach ($groups as $index => $group) {
    $groupNumber = $index + 1;
    $slides = $song->getSlidesForGroup($group);
    $slideCount = count($slides);

    echo "  [" . $groupNumber . "] " . $group->getName() . " (" . $slideCount . " slide" . ($slideCount !== 1 ? "s" : "") . ")\n";

    foreach ($slides as $slideIndex => $slide) {
        $slideNumber = $slideIndex + 1;
        $plainText = $slide->getPlainText();

        if ($plainText === '') {
            echo "      Slide " . $slideNumber . ": (no text)\n";
        } else {
            // Replace newlines with " / " for single-line display
            $displayText = str_replace("\n", " / ", $plainText);
            echo "      Slide " . $slideNumber . ": " . $displayText . "\n";
        }

        // Display translation if it exists
        if ($slide->hasTranslation()) {
            $translation = $slide->getTranslation();
            if ($translation !== null) {
                $translationText = $translation->getPlainText();
                if ($translationText !== '') {
                    $displayTranslation = str_replace("\n", " / ", $translationText);
                    echo "        Translation: " . $displayTranslation . "\n";
                }
            }
        }

        $label = $slide->getLabel();
        if ($label !== '') {
            echo "      Label: " . $label . "\n";
        }

        if ($slide->hasMacro()) {
            echo "      Macro: " . ($slide->getMacroName() ?? '') . " (" . ($slide->getMacroUuid() ?? '') . ")\n";
        }

        if ($slide->hasMedia()) {
            $format = $slide->getMediaFormat();
            $formatSuffix = $format !== null && $format !== '' ? ' [' . $format . ']' : '';
            echo "      Media: " . ($slide->getMediaUrl() ?? '') . $formatSuffix . "\n";
        }
    }
}

echo "\n";

// Display arrangements
$arrangements = $song->getArrangements();

if (empty($arrangements)) {
    echo "Arrangements: (none)\n";
} else {
    echo "Arrangements (" . count($arrangements) . "):\n";

    foreach ($arrangements as $index => $arrangement) {
        $arrangementNumber = $index + 1;
        $groupsInArrangement = $song->getGroupsForArrangement($arrangement);
        $groupNames = array_map(fn ($g) => $g->getName(), $groupsInArrangement);
        $arrangementSequence = implode(" -> ", $groupNames);

        echo "  [" . $arrangementNumber . "] " . $arrangement->getName() . ": " . $arrangementSequence . "\n";
    }
}

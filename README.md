# ProPresenter 7 PHP Library

> A PHP library to **read, modify, and generate** [ProPresenter 7](https://renewedvision.com/propresenter/) files — songs, playlists, bundles, themes, and global library files.

[![PHP Version](https://img.shields.io/badge/php-%5E8.4-777bb4.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-369%20passing-brightgreen.svg)](#development)
[![Built on Protocol Buffers](https://img.shields.io/badge/format-protobuf-4285F4.svg)](https://protobuf.dev/)

ProPresenter 7 stores its data in protobuf-encoded binary files (with ZIP wrappers for playlists and bundles). This library decodes those formats into idiomatic PHP objects, lets you modify them, and writes them back out — with full round-trip fidelity for global library files and verified compatibility with PP7 for songs and bundles.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Getting Started](#getting-started)
  - [1. Read a song (`.pro`)](#1-read-a-song-pro)
  - [2. Modify and save a song](#2-modify-and-save-a-song)
  - [3. Generate a song from scratch](#3-generate-a-song-from-scratch)
  - [4. Read a playlist (`.proplaylist`)](#4-read-a-playlist-proplaylist)
  - [5. Generate a playlist](#5-generate-a-playlist)
  - [6. Work with a `.probundle`](#6-work-with-a-probundle)
  - [7. Read a global library file](#7-read-a-global-library-file)
- [CLI Tools](#cli-tools)
- [Documentation](#documentation)
- [Project Structure](#project-structure)
- [Development](#development)
- [Compatibility & Caveats](#compatibility--caveats)
- [Contributing](#contributing)
- [License](#license)
- [Credits](#credits)

---

## Features

### File formats supported

| Format | Extension | Read | Modify | Generate | Notes |
|--------|-----------|:----:|:------:|:--------:|-------|
| Song | `.pro` | ✅ | ✅ | ✅ | Lyrics, groups, slides, arrangements, translations, CCLI metadata, macros, media |
| Playlist | `.proplaylist` | ✅ | ✅ | ✅ | ZIP64 archive, embedded songs, headers, placeholders |
| Bundle | `.probundle` | ✅ | ✅ | ✅ | ZIP archive containing a song + flat media assets |
| Theme | folder | ✅ | ✅ | ✅ | `Theme` protobuf + `Assets/` directory |
| Macros | `Macros` | ✅ | ✅ | — | Macros + collections |
| Labels | `Labels` | ✅ | ✅ | — | Slide labels with optional UI colors |
| Groups | `Groups` | ✅ | ✅ | — | Library groups (UUID, color, hot keys) |
| ClearGroups | `ClearGroups` | ✅ | ✅ | — | Clear-action groups |
| CCLI | `CCLI` | ✅ | ✅ | — | License, copyright template |
| Messages | `Messages` | ✅ | ✅ | — | Lower-third / overlay messages |
| Timers | `Timers` | ✅ | ✅ | — | Timer definitions + clock format |
| Stage | `Stage` | ✅ | ✅ | — | Stage display layouts |
| Workspace | `Workspace` | ✅ | ✅ | — | Screens, looks, masks, audio/video inputs |
| Props | `Props` | ✅ | ✅ | — | Prop cues + transitions |
| TestPatterns | `TestPatterns` | ✅ | ✅ | — | Test pattern overrides |
| Calendar | `Calendar` | ✅ | ✅ | — | Scheduled events firing macros |
| KeyMappings | `KeyMappings` | ✅ | ✅ | — | Custom hot-key bindings |
| CommunicationDevices | JSON | ✅ | ✅ | — | MIDI / serial / OSC bindings |

### Highlights

- **High-level wrappers** — work with `Song`, `Group`, `Slide`, `Arrangement`, `PlaylistArchive` etc. instead of raw protobuf classes.
- **RTF text extraction** — `Slide::getPlainText()` returns clean text from ProPresenter's CocoaRTF, including German umlauts and Unicode.
- **Translation-aware** — read and write multi-language slides (`hasTranslation()`, `getTranslation()`).
- **ZIP64 repair** — automatically fixes ProPresenter's 98-byte ZIP64 header bug on read.
- **Generate from scratch** — build complete `.pro` and `.proplaylist` files programmatically with media references.
- **18 CLI tools** — quickly inspect any ProPresenter file from the command line.
- **369 tests, 1,300+ assertions** — covering all readers, writers, generators, and round-trip fidelity against a synthetic test corpus.
- **Comprehensive docs** — every API and binary format is documented in [`doc/`](doc/).

---

## Requirements

- **PHP 8.4** or higher
- [`google/protobuf`](https://github.com/protocolbuffers/protobuf-php) (installed via Composer)
- [`ext-zip`](https://www.php.net/manual/en/book.zip.php) for `.proplaylist` and `.probundle` files (bundled with most PHP distributions)

---

## Installation

```bash
composer require bussnet/propresenter7-php-lib
```

Or clone the repository to develop locally:

```bash
git clone https://github.com/bussnet/propresenter7-php-lib.git
cd propresenter7-php-lib
composer install
```

---

## Getting Started

All examples assume Composer's autoloader is loaded:

```php
require 'vendor/autoload.php';
```

### 1. Read a song (`.pro`)

```php
use ProPresenter\Parser\ProFileReader;

$song = ProFileReader::read('path/to/Amazing Grace.pro');

echo $song->getName() . "\n";              // "Amazing Grace"
echo $song->getCcliAuthor() . "\n";        // "John Newton"
echo $song->getCcliCopyrightYear() . "\n"; // 1779

// Walk groups → slides → text
foreach ($song->getGroups() as $group) {
    echo "[{$group->getName()}]\n";

    foreach ($song->getSlidesForGroup($group) as $slide) {
        echo "  " . $slide->getPlainText() . "\n";

        if ($slide->hasTranslation()) {
            echo "  → " . $slide->getTranslation()->getPlainText() . "\n";
        }
    }
}

// Resolve an arrangement to a flat list of groups (in performance order)
$arrangement = $song->getArrangements()[0];
foreach ($song->getGroupsForArrangement($arrangement) as $group) {
    echo $group->getName() . " → ";
}
```

### 2. Modify and save a song

```php
use ProPresenter\Parser\ProFileReader;
use ProPresenter\Parser\ProFileWriter;

$song = ProFileReader::read('input.pro');

// Update CCLI metadata
$song->setName('Amazing Grace (My Chains Are Gone)');
$song->setCcliPublisher('Public Domain');
$song->setCcliCopyrightYear(2006);

// Rename a group
$song->getGroupByName('Verse 1')?->setName('Strophe 1');

// Add a label to the first slide
$song->getSlides()[0]->setLabel('Intro');

ProFileWriter::write($song, 'output.pro');
```

### 3. Generate a song from scratch

```php
use ProPresenter\Parser\ProFileGenerator;

ProFileGenerator::generateAndWrite(
    'amazing-grace.pro',
    'Amazing Grace',
    [
        [
            'name'  => 'Verse 1',
            'color' => [0.13, 0.59, 0.95, 1.0], // RGBA floats (0..1)
            'slides' => [
                ['text' => "Amazing grace, how sweet the sound\nThat saved a wretch like me"],
                [
                    'text'        => 'I once was lost, but now am found',
                    'translation' => 'Ich war verloren, doch jetzt gefunden',
                ],
            ],
        ],
        [
            'name'  => 'Chorus',
            'color' => [0.95, 0.27, 0.27, 1.0],
            'slides' => [
                ['text' => 'My chains are gone, I have been set free'],
            ],
        ],
    ],
    [
        ['name' => 'normal', 'groupNames' => ['Verse 1', 'Chorus', 'Verse 1', 'Chorus']],
    ],
    [
        'author'         => 'John Newton',
        'song_title'     => 'Amazing Grace',
        'copyright_year' => 1779,
    ],
);
```

#### Supported `slideData` keys

Every entry of a group's `slides` array is a `slideData` array. All keys are optional.

| Key | Type | Description |
|-----|------|-------------|
| `text` | `string` | Main slide text (multi-line allowed). |
| `translation` | `string` | Second text element; renders original + translation side by side. |
| `subtitle` | `string` | Smaller non-bold second run below `text` (ignored when `translation` is set). |
| `imageOnly` | `bool` | Skip the text layer entirely (image-only slide). |
| `media` | `string` | Foreground media filename. |
| `background` | `array` | Background media layer (a media ACTION), e.g. `['path' => 'BACKGROUND.jpg', 'bundleRelative' => true]`. |
| `image` | `array` | Image content ELEMENT placed at the lowest visible layer of the slide, behind text (see below). |
| `label` | `string` | Slide label text. |
| `clock` | `array` | Live wall-clock element (see below). |
| `timer` | `array` | Timer/countdown element bound to a ProPresenter timer (see below). |

##### `image` keys

Unlike `background` — which emits a media *action* on the background layer — `image`
emits a real slide content *element* whose fill is the given image. It is always
inserted at **index 0** of the slide's element array, i.e. at the lowest visible
layer, so `text`, `translation`, `subtitle`, `clock` and `timer` elements are
painted on top of it. Combine `image` with `imageOnly => true` for an
image-only slide, or with `text` for text over an image.

The image is referenced **bundle-relative** by its bare filename (`path` is
reduced to `basename()`), so it resolves against the bytes embedded in the
`.pro` / `.probundle` archive — never by an absolute path.

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `path` | `string` | `''` | Bare filename, referenced bundle-relative. |
| `format` | `string` | `'JPG'` | Media format, e.g. `JPG`, `PNG`. |
| `width` | `int` | `1920` | Natural image width. |
| `height` | `int` | `1080` | Natural image height. |
| `bounds` | `array` | `x:0, y:0, width:1920, height:1080` | `['x','y','width','height']` in slide coordinates. |
| `scaleBehavior` | `string` | `'fill'` | `fill`, `fit` or `stretch`. |
| `opacity` | `float` | `1.0` | Element opacity. |
| `name` | `string` | `''` | Name of the graphics element. |

```php
// Uploaded info slide image with text rendered on top of it
['text' => 'Herzlich willkommen', 'image' => ['path' => 'INFO_1.jpg', 'format' => 'JPG']]

// Image-only slide (no text layer)
['imageOnly' => true, 'image' => ['path' => 'INFO_2.jpg']]
```

Slides read back from a `.pro` file expose `hasImageElement()`,
`getImageElementUrl()` and `getImageElementFormat()` (mirroring
`hasBackgroundMedia()` / `getBackgroundMediaUrl()` / `getBackgroundMediaFormat()`
for the `background` media action).

##### `clock` keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `format` | `string` | `'HH:mm'` | Clock format string. |
| `military24` | `bool` | `true` | 24-hour time. |
| `text` | `string` | `'00:00'` | Placeholder text rendered in the editor. |
| `bounds` | `array` | `x:60, y:40, width:600, height:200` | `['x','y','width','height']` in slide coordinates. |
| `style` | `array` | — | Text styling, see below. |

##### `timer` keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `timerUuid` | `string` | — | UUID of the timer in the ProPresenter Timers library. Omit to leave unbound. |
| `timerName` | `string` | `''` | Timer name (fallback lookup when the UUID is unknown). |
| `format` | `string` | `'mm:ss'` | Format string; alias `formatString`. Components present in the string (`H`, `m`, `s`, `S`) are rendered, the rest are removed. |
| `text` | `string` | `'00:00'` | Placeholder text rendered in the editor. |
| `name` | `string` | `'Timer'` | Name of the graphics element. |
| `bounds` | `array` | `x:60, y:40, width:1800, height:1000` | `['x','y','width','height']` in slide coordinates. |
| `style` | `array` | — | Text styling, see below. |
| `military24` | `bool` | `true` | Maps to `Timer.Format.is_24_hour_time`. |
| `wallClock` | `bool` | `false` | Maps to `Timer.Format.is_wall_clock_time`. |
| `millisecondsUnderMinuteOnly` | `bool` | `false` | Maps to `Timer.Format.show_milliseconds_under_minute_only`. |
| `visibleWhen` | `string` | — | Optional visibility condition bound to the same timer: `hasTimeRemaining`, `hasExpired`, `isRunning` or `notRunning`. Emitted as an additional `VisibilityLink` DataLink so ProPresenter hides the element once the condition no longer holds. Omit to keep the element always visible. |

##### `style` keys (shared by `clock` and `timer`)

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `fontName` | `string` | `'HelveticaNeue'` | Font family. |
| `fontSize` | `int` | `42` | Font size in points. |
| `bold` | `bool` | `false` | Bold text run. |
| `color` | `array` | white | `[r, g, b]` as `0..255` ints or `0..1` floats. |
| `align` | `string` | `'center'` | `left`, `center` or `right`. |
| `verticalAlign` | `string` | `'middle'` | `top`, `middle` or `bottom`. |

Omitting `style` keeps the default RTF template byte-identical to previously generated files.

```php
// Big centred countdown bound to a timer from the Timers library
['timer' => [
    'timerUuid' => '0E45D0AF-BCC2-4A31-BCFD-0F5A3358E225',
    'timerName' => 'Gottesdienst',
    'format'    => 'mm:ss',
    'bounds'    => ['x' => 60, 'y' => 40, 'width' => 1800, 'height' => 1000],
    'style'     => ['fontName' => 'HelveticaNeue', 'fontSize' => 300, 'bold' => true, 'color' => [255, 255, 255]],
]]

// Countdown that disappears once it has run out
['timer' => [
    'timerUuid'   => '0E45D0AF-BCC2-4A31-BCFD-0F5A3358E225',
    'timerName'   => 'Gottesdienst',
    'format'      => 'mm:ss',
    'visibleWhen' => 'hasTimeRemaining',
]]
```

Slides read back from a `.pro` file expose `hasTimer()`, `getTimerFormat()`, `getTimerName()` and `getTimerUuid()` (mirroring `hasClock()` / `getClockFormat()`).

When `visibleWhen` is set, the slide additionally exposes `hasTimerVisibilityCondition()`, `getTimerVisibilityCriterion()` (returns the same string that was passed in) and `getTimerVisibilityTimerUuid()`.

### 4. Read a playlist (`.proplaylist`)

```php
use ProPresenter\Parser\ProPlaylistReader;

$archive = ProPlaylistReader::read('Sunday Service.proplaylist');

echo $archive->getName() . "\n";

foreach ($archive->getEntries() as $entry) {
    echo match ($entry->getType()) {
        'header'       => "── {$entry->getName()} ──\n",
        'presentation' => "  ♪ {$entry->getName()} (arr: " . ($entry->getArrangementName() ?? 'default') . ")\n",
        'placeholder'  => "  · {$entry->getName()} (TBD)\n",
        default        => "  ? {$entry->getName()}\n",
    };

    // Lazily parse embedded .pro files
    if ($entry->getType() === 'presentation') {
        $song = $archive->getEmbeddedSong($entry);
        if ($song !== null) {
            echo "      → " . count($song->getSlides()) . " slides\n";
        }
    }
}
```

### 5. Generate a playlist

```php
use ProPresenter\Parser\ProPlaylistGenerator;

ProPlaylistGenerator::generateAndWrite(
    'sunday-service.proplaylist',
    'Sunday Service',
    [
        ['type' => 'header',       'name' => 'Worship',       'color' => [0.95, 0.27, 0.27, 1.0]],
        ['type' => 'presentation', 'name' => 'Amazing Grace', 'path'  => 'file:///Songs/amazing-grace.pro', 'arrangement' => 'normal'],
        ['type' => 'presentation', 'name' => 'Oceans',        'path'  => 'file:///Songs/oceans.pro'],
        ['type' => 'header',       'name' => 'Sermon'],
        ['type' => 'placeholder',  'name' => 'Sermon notes'],
    ],
    ['notes' => 'Sunday morning service'],
);
```

### 6. Work with a `.probundle`

A `.probundle` is a ZIP archive containing a single `.pro` file plus its referenced media — perfect for sharing presentations between machines.

```php
use ProPresenter\Parser\ProBundleReader;
use ProPresenter\Parser\ProBundleWriter;
use ProPresenter\Parser\PresentationBundle;
use ProPresenter\Parser\ProFileGenerator;

// Read
$bundle = ProBundleReader::read('Christmas Slides.probundle');
echo $bundle->getName() . "\n";
echo $bundle->getMediaFileCount() . " media files\n";

foreach ($bundle->getMediaFiles() as $filename => $bytes) {
    echo "  $filename: " . strlen($bytes) . " bytes\n";
}

// Build a new bundle (media uses ROOT_CURRENT_RESOURCE → portable across machines)
$song = ProFileGenerator::generate(
    'My Slides',
    [[
        'name'   => 'Background',
        'color'  => [0.2, 0.2, 0.2, 1.0],
        'slides' => [[
            'media'          => 'background.png',
            'format'         => 'png',
            'label'          => 'background.png',
            'bundleRelative' => true,
        ]],
    ]],
    [['name' => 'normal', 'groupNames' => ['Background']]],
);

$bundle = new PresentationBundle(
    $song,
    'My Slides.pro',
    ['background.png' => file_get_contents('background.png')],
);

ProBundleWriter::write($bundle, 'my-slides.probundle');
```

### 7. Read a global library file

ProPresenter stores its global library in extension-less protobuf files inside the user library folder. Each is exposed through a dedicated reader/writer:

```php
use ProPresenter\Parser\MacrosFileReader;
use ProPresenter\Parser\MacrosFileWriter;

$library = MacrosFileReader::read('/path/to/Macros');

foreach ($library->getMacros() as $macro) {
    echo $macro->getName() . " — " . $macro->getUuid() . "\n";
}

// Add a macro programmatically
$library->addMacro('Service Start', '00000000-0000-0000-0000-000000000001');
$library->getMacroByName('Service Start')?->setColor(['r' => 0.0, 'g' => 0.5, 'b' => 1.0]);

MacrosFileWriter::write($library, '/path/to/Macros');
```

The same `Reader::read()` / `Writer::write()` pattern applies to every global library file. See [doc/api/](doc/api/) for the full set.

---

## CLI Tools

Every supported file type ships with an inspector script in [`bin/`](bin/):

```bash
php bin/parse-song.php                  path/to/song.pro
php bin/parse-playlist.php              path/to/playlist.proplaylist
php bin/parse-theme.php                 path/to/ThemeFolder
php bin/parse-macros.php                ~/Library/.../Macros
php bin/parse-labels.php                ~/Library/.../Labels
php bin/parse-groups.php                ~/Library/.../Groups
php bin/parse-clear-groups.php          ~/Library/.../ClearGroups
php bin/parse-ccli.php                  ~/Library/.../CCLI
php bin/parse-messages.php              ~/Library/.../Messages
php bin/parse-timers.php                ~/Library/.../Timers
php bin/parse-stage.php                 ~/Library/.../Stage
php bin/parse-workspace.php             ~/Library/.../Workspace
php bin/parse-props.php                 ~/Library/.../Props
php bin/parse-test-patterns.php         ~/Library/.../TestPatterns
php bin/parse-calendar.php              ~/Library/.../Calendar
php bin/parse-key-mappings.php          ~/Library/.../KeyMappings
php bin/parse-communication-devices.php ~/Library/.../CommunicationDevices
```

Example output for `parse-song.php`:

```text
Song: Amazing Grace
UUID: A1B2C3D4-...

CCLI Metadata:
  Song Title: Amazing Grace
  Author: John Newton
  Copyright Year: 1779
  Display: yes

Groups (3):
  [1] Verse 1 (2 slides)
      Slide 1: Amazing grace, how sweet the sound / That saved a wretch like me
      Slide 2: I once was lost, but now am found
  [2] Chorus (1 slide)
      Slide 1: My chains are gone, I have been set free
  ...

Arrangements (1):
  [1] normal: Verse 1 -> Chorus -> Verse 1 -> Chorus
```

---

## Documentation

Full documentation lives in [`doc/`](doc/) — start with **[doc/INDEX.md](doc/INDEX.md)**.

### API reference

| Topic | Document |
|-------|----------|
| Songs (`.pro`) | [doc/api/song.md](doc/api/song.md) |
| Playlists (`.proplaylist`) | [doc/api/playlist.md](doc/api/playlist.md) |
| Bundles (`.probundle`) | [doc/api/bundle.md](doc/api/bundle.md) |
| Themes (folder) | [doc/api/theme.md](doc/api/theme.md) |
| Macros library | [doc/api/macros.md](doc/api/macros.md) |
| Labels library | [doc/api/labels.md](doc/api/labels.md) |
| Groups library | [doc/api/groups.md](doc/api/groups.md) |
| ClearGroups library | [doc/api/clear-groups.md](doc/api/clear-groups.md) |
| CCLI settings | [doc/api/ccli.md](doc/api/ccli.md) |
| Messages library | [doc/api/messages.md](doc/api/messages.md) |
| Timers library | [doc/api/timers.md](doc/api/timers.md) |
| Stage layouts | [doc/api/stage.md](doc/api/stage.md) |
| Workspace | [doc/api/workspace.md](doc/api/workspace.md) |
| Props library | [doc/api/props.md](doc/api/props.md) |
| TestPatterns | [doc/api/test-patterns.md](doc/api/test-patterns.md) |
| Calendar | [doc/api/calendar.md](doc/api/calendar.md) |
| KeyMappings | [doc/api/key-mappings.md](doc/api/key-mappings.md) |
| CommunicationDevices | [doc/api/communication-devices.md](doc/api/communication-devices.md) |

### Binary format specifications

| Format | Document |
|--------|----------|
| `.pro` (songs) | [doc/formats/pp_song_spec.md](doc/formats/pp_song_spec.md) |
| `.proplaylist` | [doc/formats/pp_playlist_spec.md](doc/formats/pp_playlist_spec.md) |
| `.probundle` | [doc/formats/pp_bundle_spec.md](doc/formats/pp_bundle_spec.md) |

### Search by keyword

Looking for something specific? Use the keyword index: [doc/keywords.md](doc/keywords.md).

---

## Project Structure

```text
.
├── bin/                   # 18 CLI tools (parse-*.php scripts)
├── src/                   # PHP source (wrappers, readers, writers, generators)
├── generated/             # Auto-generated protobuf PHP classes (Rv\Data\…)
├── proto/                 # Vendored .proto files (greyshirtguy/ProPresenter7-Proto, Proto 19beta + extras)
├── tests/                 # PHPUnit test suite (369 tests)
├── doc/
│   ├── INDEX.md           # Documentation entry point
│   ├── keywords.md        # Keyword search index
│   ├── CONTRIBUTING.md    # Documentation guidelines
│   ├── api/               # PHP API documentation
│   ├── formats/           # Binary file format specifications
│   ├── internal/          # Development notes (learnings, decisions, issues)
│   └── reference_samples/ # Reference files used by tests (real-world songs)
├── composer.json
├── phpunit.xml
├── LICENSE
└── README.md
```

### Key classes

| Class | Purpose |
|-------|---------|
| `ProPresenter\Parser\Song` | Top-level song wrapper (groups + slides + arrangements) |
| `ProPresenter\Parser\Group` | Song part (verse, chorus, …) |
| `ProPresenter\Parser\Slide` | Single slide with text, label, macro, media |
| `ProPresenter\Parser\TextElement` | Text element with RTF + plain-text accessors |
| `ProPresenter\Parser\Arrangement` | Group order for a performance |
| `ProPresenter\Parser\PlaylistArchive` | `.proplaylist` ZIP wrapper |
| `ProPresenter\Parser\PresentationBundle` | `.probundle` ZIP wrapper |
| `ProPresenter\Parser\ThemeBundle` | Theme folder wrapper |
| `ProPresenter\Parser\ProFileReader` / `Writer` / `Generator` | `.pro` IO |
| `ProPresenter\Parser\ProPlaylistReader` / `Writer` / `Generator` | `.proplaylist` IO |
| `ProPresenter\Parser\ProBundleReader` / `Writer` | `.probundle` IO |
| `ProPresenter\Parser\Zip64Fixer` | Repairs ProPresenter's broken ZIP64 EOCD headers |
| `ProPresenter\Parser\RtfExtractor` | Standalone CocoaRTF → plain-text converter |

---

## Development

### Running the tests

```bash
composer install
composer test
```

You should see:

```text
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

OK (369 tests, 1298 assertions)
```

The test suite includes:

- **Unit tests** — every wrapper class
- **Integration tests** — readers + writers round-tripping reference files
- **Mass validation** — parses every `.pro` fixture in `doc/reference_samples/all-songs/` (`tests/MassValidationTest.php`)
- **Binary fidelity tests** — verifies byte-perfect round-trips for global library files

### Reference samples

Real ProPresenter files used by the tests live in [`doc/reference_samples/`](doc/reference_samples/). They are exported from production worship environments and cover edge cases (translations, missing arrangements, ZIP64 quirks, German Unicode, embedded media).

### Regenerating sample bundles

Some test fixtures are generated procedurally:

```bash
php bin/regen-test-bundles.php
```

---

## Compatibility & Caveats

- **Verified against** ProPresenter 7.16+ on macOS. Files generated by this library open cleanly in ProPresenter 7.
- **Round-trip fidelity** — global library files (`Macros`, `Labels`, `Groups`, …) round-trip byte-for-byte. Songs do **not**: ProPresenter's protobuf schema contains undocumented fields that are dropped on re-encode. The library preserves logical content perfectly, but raw bytes will differ. See [doc/internal/issues.md](doc/internal/issues.md) for the gory details.
- **ZIP64 quirk** — ProPresenter exports `.proplaylist` and `.probundle` files with a 98-byte ZIP64 header offset bug. `Zip64Fixer` patches this in memory before parsing. Files written by this library use clean standard ZIPs.
- **RTF** — slide text is stored as CocoaRTF (Windows-1252 with `\'xx` hex escapes for non-ASCII). `getPlainText()` decodes this; the generator produces clean RTF that PP7 accepts.
- **macOS-centric paths** — ProPresenter uses `file://` URLs with absolute paths in some fields. For portable bundles, use `'bundleRelative' => true` on media slides (this sets `ROOT_CURRENT_RESOURCE` so PP7 resolves media relative to the archive).

---

## Contributing

Contributions are welcome! Please:

1. Open an issue describing the change before sending a PR for anything non-trivial.
2. Follow the documentation guidelines in [doc/CONTRIBUTING.md](doc/CONTRIBUTING.md).
3. Add a test for any new behavior — TDD is the convention here.
4. Run `composer test` before submitting.
5. Keep changes focused; avoid unrelated refactors.

---

## License

This project is released under the [MIT License](LICENSE).

The bundled `.proto` files in [`proto/`](proto/) are derived from [greyshirtguy/ProPresenter7-Proto](https://github.com/greyshirtguy/ProPresenter7-Proto), Proto 19beta (dumped from ProPresenter v19 beta build 318767123) plus a few extras (`calendar`, `keyMappings`, plus three legacy analytics protos retained from the 7.16.2 set), also distributed under the MIT License.

---

## Credits

- **[Renewed Vision](https://renewedvision.com/)** — for ProPresenter, an excellent presentation tool.
- **[greyshirtguy](https://github.com/greyshirtguy/ProPresenter7-Proto)** — for reverse-engineering the ProPresenter 7 protobuf schema, without which this library would not exist.
- **[Google Protocol Buffers](https://protobuf.dev/)** — for the underlying serialization format.

ProPresenter is a trademark of Renewed Vision, LLC. This project is not affiliated with or endorsed by Renewed Vision.

# Song Parser API

> PHP module for reading, modifying, and generating ProPresenter `.pro` song files.

## Quick Reference

```php
use ProPresenter\Parser\ProFileReader;
use ProPresenter\Parser\ProFileWriter;
use ProPresenter\Parser\ProFileGenerator;

// Read
$song = ProFileReader::read('path/to/song.pro');

// Modify
$song->setName("New Name");
ProFileWriter::write($song, 'output.pro');

// Generate
$song = ProFileGenerator::generate('Song Name', $groups, $arrangements, $ccli);
```

---

## Reading Songs

```php
use ProPresenter\Parser\ProFileReader;

$song = ProFileReader::read('path/to/song.pro');
```

### Metadata Access

```php
// Basic info
$song->getName();        // "Amazing Grace"
$song->getUuid();        // "A1B2C3D4-..."

// CCLI metadata
$song->getCcliAuthor();          // "Joel Houston, Matt Crocker"
$song->getCcliSongTitle();       // "Oceans (Where Feet May Fail)"
$song->getCcliPublisher();       // "2012 Hillsong Music Publishing"
$song->getCcliCopyrightYear();   // 2012
$song->getCcliSongNumber();      // 6428767
$song->getCcliDisplay();         // true

// Other metadata
$song->getCategory();                   // ""
$song->getNotes();                      // ""
$song->getSelectedArrangementUuid();    // "uuid-string"
```

---

## Groups

Groups are song parts (Verse 1, Chorus, Bridge, etc.).

```php
foreach ($song->getGroups() as $group) {
    $group->getName();        // "Verse 1"
    $group->getUuid();        // "E5F6G7H8-..."
    $group->getColor();       // ['r' => 1.0, 'g' => 0.0, 'b' => 0.0, 'a' => 1.0] or null
    $group->getSlideUuids();  // ["uuid1", "uuid2", ...]
}

// Get specific group
$chorus = $song->getGroupByName("Chorus");

// Get slides for a group
$slides = $song->getSlidesForGroup($group);
```

---

## Slides

Slides are individual presentation frames.

```php
foreach ($song->getSlides() as $slide) {
    $slide->getUuid();
    $slide->getPlainText();   // Extracted from first text element
    $slide->getLabel();       // Optional cue label/title
}

// Access all text elements
foreach ($slide->getTextElements() as $textElement) {
    $textElement->getName();       // "Orginal", "Deutsch", etc.
    $textElement->getRtfData();    // Raw RTF bytes
    $textElement->getPlainText();  // Extracted plain text
}
```

### Translations

Multiple text elements per slide indicate translations.

```php
if ($slide->hasTranslation()) {
    $translation = $slide->getTranslation();
    $translation->getPlainText();  // Translated text
}
```

### Macros

```php
if ($slide->hasMacro()) {
    $slide->getMacroName();           // "Lied 1.Folie"
    $slide->getMacroUuid();           // "20C1DFDE-..."
    $slide->getMacroCollectionName(); // "--MAIN--"
    $slide->getMacroCollectionUuid(); // "8D02FC57-..."
}
```

### Media

```php
if ($slide->hasMedia()) {
    $slide->getMediaUrl();     // "file:///Users/me/Pictures/slide.jpg"
    $slide->getMediaUuid();    // "uuid-string"
    $slide->getMediaFormat();  // "JPG"
}
```

---

## Arrangements

Arrangements define group order for presentations.

```php
foreach ($song->getArrangements() as $arrangement) {
    $arrangement->getName();       // "normal"
    $arrangement->getGroupUuids(); // ["uuid1", "uuid2", "uuid1", ...] (can repeat)
}

// Resolve groups in arrangement order
$groups = $song->getGroupsForArrangement($arrangement);
foreach ($groups as $group) {
    echo $group->getName();
}
```

---

## Modifying Songs

```php
use ProPresenter\Parser\ProFileWriter;

// Metadata
$song->setName("New Song Title");
$song->setCategory("Worship");
$song->setNotes("Use acoustic intro");

// CCLI
$song->setCcliAuthor("Author Name");
$song->setCcliSongTitle("Song Title");
$song->setCcliPublisher("Publisher");
$song->setCcliCopyrightYear(2024);
$song->setCcliSongNumber(12345);
$song->setCcliDisplay(true);

// Group names
$group = $song->getGroupByName("Verse 1");
$group->setName("Strophe 1");

// Slide labels/macros
$slide = $song->getSlides()[0];
$slide->setLabel('New Label');
$slide->setMacro(
    'Macro Name',
    'macro-uuid',
    '--MAIN--',
    'collection-uuid'
);
$slide->removeMacro();

// Write
ProFileWriter::write($song, 'output.pro');
```

---

## Generating Songs

```php
use ProPresenter\Parser\ProFileGenerator;

$song = ProFileGenerator::generate(
    'Amazing Grace',
    [
        [
            'name' => 'Verse 1',
            'color' => [0.13, 0.59, 0.95, 1.0],  // RGBA floats
            'slides' => [
                ['text' => 'Amazing grace, how sweet the sound'],
                ['text' => 'That saved a wretch like me', 'translation' => 'Der mich Verlornen fand'],
            ],
        ],
        [
            'name' => 'Chorus',
            'color' => [0.95, 0.27, 0.27, 1.0],
            'slides' => [
                ['text' => 'I once was lost, but now am found'],
            ],
        ],
        [
            'name' => 'Media',
            'color' => [0.2, 0.2, 0.2, 1.0],
            'slides' => [
                ['media' => 'file:///Users/me/Pictures/slide.jpg', 'format' => 'JPG', 'label' => 'slide.jpg'],
            ],
        ],
    ],
    [
        ['name' => 'normal', 'groupNames' => ['Verse 1', 'Chorus', 'Verse 1']],
    ],
    [
        'author' => 'John Newton',
        'song_title' => 'Amazing Grace',
        'copyright_year' => 1779,
    ]
);

// Generate and write in one call
ProFileGenerator::generateAndWrite('output.pro', 'Song Name', $groups, $arrangements, $ccli);
```

### Slide Options

```php
// Text only
['text' => 'Lyrics here']

// Text with translation
['text' => 'English lyrics', 'translation' => 'Deutsche Lyrics']

// Text with macro
['text' => 'Lyrics', 'macro' => ['name' => 'Macro Name', 'uuid' => 'macro-uuid']]

// Media slide
['media' => 'file:///path/to/image.jpg', 'format' => 'JPG', 'label' => 'image.jpg']

// Media with macro
['media' => 'file:///path/to/video.mp4', 'format' => 'MP4', 'label' => 'video.mp4', 'macro' => ['name' => 'Macro', 'uuid' => 'uuid']]
```

---

## CLI Tool

```bash
php bin/parse-song.php path/to/song.pro
```

Output includes:
- Song metadata (name, UUID, CCLI info)
- Groups with slide counts
- Slides with text content and translations
- Arrangements with group order

---

## Error Handling

```php
try {
    $song = ProFileReader::read('song.pro');
} catch (\RuntimeException $e) {
    // File not found, empty file, or invalid protobuf
    echo "Error: " . $e->getMessage();
}
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/Song.php` | Top-level song wrapper |
| `src/Group.php` | Group (song part) wrapper |
| `src/Slide.php` | Slide wrapper with text access |
| `src/TextElement.php` | Text element with RTF extraction |
| `src/Arrangement.php` | Arrangement wrapper |
| `src/RtfExtractor.php` | RTF to plain text converter |
| `src/ProFileReader.php` | Reads `.pro` files |
| `src/ProFileWriter.php` | Writes `.pro` files |
| `src/ProFileGenerator.php` | Generates `.pro` files |
| `bin/parse-song.php` | CLI tool |

---

## See Also

- [Format Specification](../formats/pp_song_spec.md) — Binary format details
- [Playlist API](playlist.md) — `.proplaylist` file handling
- [Bundle API](bundle.md) — `.probundle` file handling (Song objects inside bundles)

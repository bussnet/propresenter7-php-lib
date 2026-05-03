# Playlist Parser API

> PHP module for reading, modifying, and generating ProPresenter `.proplaylist` files.

## Quick Reference

```php
use ProPresenter\Parser\ProPlaylistReader;
use ProPresenter\Parser\ProPlaylistWriter;
use ProPresenter\Parser\ProPlaylistGenerator;

// Read
$archive = ProPlaylistReader::read('path/to/playlist.proplaylist');

// Modify
$archive->setName("New Playlist Name");
ProPlaylistWriter::write($archive, 'output.proplaylist');

// Generate
$archive = ProPlaylistGenerator::generate('Playlist Name', $entries, $metadata);
```

---

## Reading Playlists

```php
use ProPresenter\Parser\ProPlaylistReader;

$archive = ProPlaylistReader::read('path/to/playlist.proplaylist');
```

### Metadata Access

```php
$archive->getName();   // Playlist name
$archive->getUuid();   // Playlist UUID
$archive->getNotes();  // Playlist notes
```

---

## Entries

Entries are playlist items (songs, headers, placeholders).

```php
foreach ($archive->getEntries() as $entry) {
    $entry->getType();   // 'song', 'header', 'placeholder', 'cue'
    $entry->getName();   // Entry display name
    $entry->getUuid();   // Entry UUID
}
```

### Song Entries (Presentations)

```php
if ($entry->getType() === 'presentation') {
    $entry->getDocumentPath();       // "file:///path/to/song.pro"
    $entry->getDocumentFilename();   // "song.pro"
    $entry->getArrangementName();    // "normal"
    $entry->getArrangementUuid();    // "uuid-string"
}
```

### Header Entries

```php
if ($entry->getType() === 'header') {
    $entry->getHeaderColor();  // [r, g, b, a] RGBA floats
}
```

### Embedded Songs

Playlists can contain embedded `.pro` files. Access them lazily:

```php
if ($entry->isEmbedded()) {
    $song = $archive->getEmbeddedSong($entry);
    $song->getName();
    foreach ($song->getGroups() as $group) {
        echo $group->getName();
    }
}
```

---

## Embedded Files

```php
// List embedded .pro files
$proFiles = $archive->getEmbeddedProFiles();
// ['Song1.pro' => $bytes, 'Song2.pro' => $bytes]

// List embedded media files
$mediaFiles = $archive->getEmbeddedMediaFiles();
// ['Users/me/Pictures/slide.jpg' => $bytes]

// Get specific embedded song
$song = $archive->getEmbeddedSong($entry);
```

---

## Modifying Playlists

```php
use ProPresenter\Parser\ProPlaylistWriter;

$archive->setName("New Playlist Name");
$archive->setNotes("Updated notes");

ProPlaylistWriter::write($archive, 'output.proplaylist');
```

---

## Generating Playlists

```php
use ProPresenter\Parser\ProPlaylistGenerator;

$archive = ProPlaylistGenerator::generate(
    'Sunday Service',
    [
        [
            'type' => 'header',
            'name' => 'Worship',
            'color' => [0.95, 0.27, 0.27, 1.0],
        ],
        [
            'type' => 'presentation',
            'name' => 'Amazing Grace',
            'path' => 'file:///path/to/amazing-grace.pro',
            'arrangement' => 'normal',
        ],
        [
            'type' => 'presentation',
            'name' => 'Oceans',
            'path' => 'file:///path/to/oceans.pro',
            'arrangement' => 'verse-only',
        ],
        [
            'type' => 'placeholder',
            'name' => 'TBD',
        ],
    ],
    ['notes' => 'Sunday morning service']
);

// Generate and write in one call
ProPlaylistGenerator::generateAndWrite(
    'output.proplaylist',
    'Playlist Name',
    $entries,
    $metadata
);
```

### Entry Types

```php
// Header (section divider)
['type' => 'header', 'name' => 'Section Name', 'color' => [r, g, b, a]]

// Presentation (song reference)
['type' => 'presentation', 'name' => 'Song Name', 'path' => 'file:///...', 'arrangement' => 'name']

// Placeholder (empty slot)
['type' => 'placeholder', 'name' => 'TBD']
```

---

## CLI Tool

```bash
php bin/parse-playlist.php path/to/playlist.proplaylist
```

Output includes:
- Playlist metadata (name, UUID, notes)
- Entries with type-specific details
- Embedded file counts

---

## Error Handling

```php
try {
    $archive = ProPlaylistReader::read('playlist.proplaylist');
} catch (\RuntimeException $e) {
    // File not found, empty file, invalid ZIP, or invalid protobuf
    echo "Error: " . $e->getMessage();
}
```

---

## ZIP64 Notes

ProPresenter exports playlists with a broken ZIP64 header (98-byte offset discrepancy). The reader automatically fixes this before parsing. The writer produces clean standard ZIPs without the bug.

See [Format Specification](../formats/pp_playlist_spec.md) Section 4 for details.

---

## Key Files

| File | Purpose |
|------|---------|
| `src/PlaylistArchive.php` | Top-level playlist wrapper |
| `src/PlaylistEntry.php` | Entry wrapper (song/header/placeholder) |
| `src/PlaylistNode.php` | Playlist node wrapper |
| `src/ProPlaylistReader.php` | Reads `.proplaylist` files |
| `src/ProPlaylistWriter.php` | Writes `.proplaylist` files |
| `src/ProPlaylistGenerator.php` | Generates `.proplaylist` files |
| `src/Zip64Fixer.php` | Fixes ProPresenter ZIP64 header bug |
| `bin/parse-playlist.php` | CLI tool |

---

## See Also

- [Format Specification](../formats/pp_playlist_spec.md) — Binary format details
- [Song API](song.md) — `.pro` file handling
- [Bundle API](bundle.md) — `.probundle` file handling (similar ZIP pattern)

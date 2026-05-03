# Bundle Parser API

> PHP module for reading, modifying, and writing ProPresenter `.probundle` files.

## Quick Reference

```php
use ProPresenter\Parser\ProBundleReader;
use ProPresenter\Parser\ProBundleWriter;
use ProPresenter\Parser\PresentationBundle;

// Read
$bundle = ProBundleReader::read('path/to/presentation.probundle');

// Access
$bundle->getName();           // Presentation name
$bundle->getSong();           // Song wrapper
$bundle->getMediaFiles();     // ['filename' => bytes, ...]

// Write
ProBundleWriter::write($bundle, 'output.probundle');
```

---

## Reading Bundles

```php
use ProPresenter\Parser\ProBundleReader;

$bundle = ProBundleReader::read('path/to/presentation.probundle');
```

The reader automatically applies `Zip64Fixer` to handle ProPresenter's broken ZIP64 headers. Works with both PP7-exported bundles and library-generated bundles.

### Metadata Access

```php
$bundle->getName();            // Presentation name (from embedded Song)
$bundle->getProFilename();     // "SongName.pro" (filename inside archive)
$bundle->getMediaFileCount();  // Number of media files
```

---

## Presentation Access

```php
// Get the Song wrapper (same API as ProFileReader)
$song = $bundle->getSong();
$song->getName();
$song->getUuid();
$song->getGroups();
$song->getSlides();
$song->getArrangements();

// Get the raw protobuf Presentation
$presentation = $bundle->getPresentation();
```

The `Song` object returned by `getSong()` has the same API as songs from `ProFileReader::read()`. See [Song API](song.md) for full details.

---

## Media Files

```php
// All media files: filename => raw bytes
$mediaFiles = $bundle->getMediaFiles();
foreach ($mediaFiles as $filename => $bytes) {
    echo "$filename: " . strlen($bytes) . " bytes\n";
}

// Check if a specific media file exists
if ($bundle->hasMediaFile('background.png')) {
    $bytes = $bundle->getMediaFile('background.png');
}

// Count
$bundle->getMediaFileCount();  // 0, 1, 2, ...
```

Media files are stored as flat filenames (no directories). The writer automatically flattens any paths to `basename()`.

---

## Creating Bundles

Build a `PresentationBundle` from a `Song` and media files:

```php
use ProPresenter\Parser\PresentationBundle;
use ProPresenter\Parser\ProFileGenerator;
use ProPresenter\Parser\ProBundleWriter;

// Generate a song with a media slide (bundleRelative for portable bundles)
$song = ProFileGenerator::generate(
    'My Presentation',
    [
        [
            'name' => 'Background',
            'color' => [0.2, 0.2, 0.2, 1.0],
            'slides' => [
                [
                    'media' => 'background.png',
                    'format' => 'png',
                    'label' => 'background.png',
                    'bundleRelative' => true,
                ],
            ],
        ],
    ],
    [['name' => 'normal', 'groupNames' => ['Background']]],
);

// Read the media file
$imageBytes = file_get_contents('/path/to/background.png');

// Create the bundle (flat filenames)
$bundle = new PresentationBundle(
    $song,
    'My Presentation.pro',
    ['background.png' => $imageBytes],
);

// Write to disk
ProBundleWriter::write($bundle, 'output.probundle');
```

### Media Path Convention

Media entries use **flat filenames** (no directories):

```php
$mediaFiles = [
    'background.png' => $pngBytes,
    'intro.mp4' => $mp4Bytes,
];
```

The writer flattens any paths to `basename()` automatically. The `.pro` protobuf uses `ROOT_CURRENT_RESOURCE` so PP7 resolves media relative to the bundle — no absolute paths needed.

### `bundleRelative` Slide Option

Set `'bundleRelative' => true` on media slides to use `ROOT_CURRENT_RESOURCE` instead of absolute filesystem paths:

```php
// For bundles (portable — works on any machine)
['media' => 'image.png', 'format' => 'png', 'bundleRelative' => true]

// For standalone .pro files (uses absolute path with filesystem root detection)
['media' => 'file:///Users/me/Downloads/image.png', 'format' => 'png']
```

---

## Writing Bundles

```php
use ProPresenter\Parser\ProBundleWriter;

ProBundleWriter::write($bundle, 'output.probundle');
```

The writer:
- Creates a standard ZIP archive (deflate compression)
- **Flattens media entries to `basename()`** — no directories in the ZIP
- Writes media entries first, `.pro` file last
- Uses atomic write (temp file + rename) for safety

---

## Round-Trip Example

```php
use ProPresenter\Parser\ProBundleReader;
use ProPresenter\Parser\ProBundleWriter;

// Read
$bundle = ProBundleReader::read('input.probundle');

// Inspect
echo "Name: " . $bundle->getName() . "\n";
echo "Media: " . $bundle->getMediaFileCount() . " files\n";

// Modify the presentation
$song = $bundle->getSong();
$song->setName("Modified Presentation");

// Write back
ProBundleWriter::write($bundle, 'output.probundle');
```

---

## Error Handling

```php
try {
    $bundle = ProBundleReader::read('presentation.probundle');
} catch (\InvalidArgumentException $e) {
    // File not found or empty path
    echo "Error: " . $e->getMessage();
} catch (\RuntimeException $e) {
    // Empty file, invalid ZIP, no .pro file found, or invalid protobuf
    echo "Error: " . $e->getMessage();
}
```

### Error Cases

| Condition | Exception | Message Pattern |
|-----------|-----------|-----------------|
| File not found | `InvalidArgumentException` | `Bundle file not found: ...` |
| Empty file | `RuntimeException` | `Bundle file is empty: ...` |
| Invalid ZIP | `RuntimeException` | `Failed to open bundle archive: ...` |
| No `.pro` entry | `RuntimeException` | `No .pro file found in bundle archive: ...` |
| Target dir missing (write) | `InvalidArgumentException` | `Target directory does not exist: ...` |

---

## Key Files

| File | Purpose |
|------|---------|
| `src/PresentationBundle.php` | Bundle wrapper (Song + media files) |
| `src/ProBundleReader.php` | Reads `.probundle` files (with Zip64Fixer) |
| `src/ProBundleWriter.php` | Writes `.probundle` files (standard ZIP) |
| `src/ProFileGenerator.php` | Generates `.pro` files with media support |
| `src/Zip64Fixer.php` | Fixes ProPresenter ZIP64 header bug |
| `doc/reference_samples/TestBild.probundle` | Generated reference file (PP7-verified) |
| `doc/reference_samples/RestBildExportFromPP.probundle` | PP7-exported reference file |

---

## See Also

- [Format Specification](../formats/pp_bundle_spec.md) -- Binary format details
- [Song API](song.md) -- `.pro` file handling (same Song object inside bundles)
- [Playlist API](playlist.md) -- `.proplaylist` file handling (similar ZIP pattern)

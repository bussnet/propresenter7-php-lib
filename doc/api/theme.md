# Theme Bundle API

> PHP module for reading, modifying, and writing folder-based ProPresenter themes.

## Quick Reference

```php
use ProPresenter\Parser\ThemeFileReader;
use ProPresenter\Parser\ThemeFileWriter;

$theme = ThemeFileReader::read('/path/to/theme-folder');

foreach ($theme->getSlides() as $slide) {
    $slide->getName();       // KeyVisual, Liedtext, ...
    $slide->getBaseSlide();  // ?\Rv\Data\Slide
}

foreach ($theme->getAssets() as $asset) {
    $asset->getName();
    $asset->getSize();
    $asset->getMimeType();
}

ThemeFileWriter::write($theme, '/path/to/output-folder');
```

---

## Folder Layout

A theme is a directory, not a single protobuf file:

```text
SampleTheme/
├── Theme          # Rv\Data\Template\Document protobuf
└── Assets/
    ├── BACKGROUND.jpg
    ├── BAUCHBIND_STREAM.jpg
    └── KEY_VISUAL.jpg
```

The `Theme` file is a `Rv\Data\Template\Document` from `template.proto`.
Its slides are named theme layouts.

---

## ThemeBundle

```php
$theme->getDocument();
$theme->getSlides();
$theme->getSlideByName('KeyVisual');
$theme->addSlide($slide);
$theme->removeSlide('KeyVisual');
$theme->getAssets();
$theme->getAssetByName('BACKGROUND.jpg');
$theme->addAsset('NEW.jpg', $bytes);
$theme->removeAsset('NEW.jpg');
$theme->count();
$theme->getAssetCount();
```

---

## ThemeSlide

```php
$slide->getName();
$slide->setName('Liedtext');
$slide->getBaseSlide();
$slide->getProto();
```

---

## ThemeAsset

```php
$asset->getName();
$asset->getBytes();
$asset->getSize();
$asset->getMimeType(); // image/jpeg, image/png, ...
```

MIME type detection is extension-based and best-effort.

---

## Writing Themes

`ThemeFileWriter::write()` creates the target folder if needed, writes the
serialized `Theme` protobuf, creates `Assets/`, writes every `ThemeAsset`, and
removes stale files from `Assets/` that are not present in the bundle.

---

## CLI Tool

```bash
php bin/parse-theme.php /path/to/theme-folder
```

The CLI prints slide names plus asset filenames, sizes, and MIME types.

---

## Key Files

| File | Purpose |
|------|---------|
| `src/ThemeBundle.php` | Top-level theme wrapper |
| `src/ThemeFileReader.php` | Reads a theme folder |
| `src/ThemeFileWriter.php` | Writes a theme folder and cleans stale assets |
| `src/ThemeSlide.php` | Single template slide wrapper |
| `src/ThemeAsset.php` | Single asset value object |
| `bin/parse-theme.php` | CLI summary tool |

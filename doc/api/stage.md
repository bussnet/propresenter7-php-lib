# Stage Library API

> PHP module for reading, modifying, and writing the global ProPresenter `Stage` file.

## Quick Reference

```php
use ProPresenter\Parser\StageFileReader;
use ProPresenter\Parser\StageFileWriter;

$library = StageFileReader::read('/path/to/Stage');

foreach ($library->getLayouts() as $layout) {
    $layout->getName();
    $layout->getUuid();
    $layout->getSlide(); // ?\Rv\Data\Slide
}

StageFileWriter::write($library, '/path/to/Stage');
```

---

## File Layout

The `Stage` file is the protobuf-serialised `Rv\Data\Stage\Document` from
`stage.proto`.

| Field | Type | Description |
|-------|------|-------------|
| `application_info` | `ApplicationInfo` | ProPresenter metadata |
| `layouts` | repeated `Stage.Layout` | Stage display layouts |

---

## StageLibrary

```php
$library->getDocument();
$library->getLayouts();
$library->getLayoutByUuid('0455...'); // case-insensitive
$library->getLayoutByName('Default StageDisplay');
$library->addLayout($layout);
$library->removeLayout($uuid);
$library->count();
$library->getApplicationInfo();
```

---

## StageLayout

```php
$layout->getName();
$layout->setName('New name');
$layout->getUuid();
$layout->setUuid('...');
$layout->getSlide();
$layout->getProto();
```

The slide is exposed as the raw `Rv\Data\Slide` protobuf because stage layouts
can contain complex arrangements.

---

## CLI Tool

```bash
php bin/parse-stage.php /path/to/Stage
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/StageFileReader.php` | Reads the `Stage` file |
| `src/StageFileWriter.php` | Writes the `Stage` file |
| `src/StageLibrary.php` | Document wrapper and indexes |
| `src/StageLayout.php` | Single layout wrapper |
| `bin/parse-stage.php` | CLI summary tool |

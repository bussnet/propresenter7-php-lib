# Workspace Library API

> PHP module for reading, modifying, and writing the ProPresenter `Workspace` file.

## Quick Reference

```php
use ProPresenter\Parser\WorkspaceFileReader;
use ProPresenter\Parser\WorkspaceFileWriter;

$library = WorkspaceFileReader::read('/path/to/Workspace');

foreach ($library->getScreens() as $screen) {
    $screen->getName();
    $screen->getUuid();
    $screen->getScreenType();
}

WorkspaceFileWriter::write($library, '/path/to/Workspace');
```

---

## File Layout

The `Workspace` file is `Rv\Data\ProPresenterWorkspace` from `proworkspace.proto`.
Its `pro_screens` entries are `Rv\Data\ProPresenterScreen` messages from
`proscreen.proto`.

---

## WorkspaceLibrary

```php
$library->getDocument();
$library->getScreens();
$library->getScreenByName('StageDisplay');
$library->getScreenByUuid('C86D...'); // case-insensitive
$library->addScreen($screen);
$library->removeScreen($uuid);
$library->count();
$library->getAudienceLooks();
$library->getMasks();
$library->getVideoInputs();
$library->getSelectedLibraryName();
$library->setSelectedLibraryName('Library');
```

---

## Screen

```php
$screen->getName();
$screen->setName('New name');
$screen->getUuid();
$screen->setUuid('...');
$screen->getScreenType();
$screen->getIndex();
$screen->getProto();
```

Use `getProto()` for detailed arrangement, background, and screen geometry data.

---

## CLI Tool

```bash
php bin/parse-workspace.php /path/to/Workspace
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/WorkspaceFileReader.php` | Reads the `Workspace` file |
| `src/WorkspaceFileWriter.php` | Writes the `Workspace` file |
| `src/WorkspaceLibrary.php` | Document wrapper and indexes |
| `src/Screen.php` | Single screen wrapper |
| `bin/parse-workspace.php` | CLI summary tool |

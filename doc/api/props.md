# Props Library API

> PHP module for reading, modifying, and writing the global ProPresenter `Props` file.

## Quick Reference

```php
use ProPresenter\Parser\PropsFileReader;
use ProPresenter\Parser\PropsFileWriter;

$library = PropsFileReader::read('/path/to/Props');

foreach ($library->getProps() as $prop) {
    $prop->getName();
    $prop->getUuid();
    $prop->isEnabled();
}

PropsFileWriter::write($library, '/path/to/Props');
```

---

## File Layout

The `Props` file is `Rv\Data\PropDocument` from `propDocument.proto`.
Each prop is stored as a `Rv\Data\Cue` in the document's `cues` field.

---

## PropLibrary

```php
$library->getDocument();
$library->getProps();
$library->getPropByUuid('1FB2...'); // case-insensitive
$library->getPropByName('Props #1');
$library->addProp($prop);
$library->removeProp($uuid);
$library->count();
$library->getApplicationInfo();
```

---

## Prop

```php
$prop->getName();
$prop->setName('Lower Third');
$prop->getUuid();
$prop->setUuid('...');
$prop->isEnabled();
$prop->setEnabled(true);
$prop->getCompletionTime();
$prop->getActions();
$prop->getProto();
```

Use `getProto()` for full Cue/action access.

---

## CLI Tool

```bash
php bin/parse-props.php /path/to/Props
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/PropsFileReader.php` | Reads the `Props` file |
| `src/PropsFileWriter.php` | Writes the `Props` file |
| `src/PropLibrary.php` | Document wrapper and indexes |
| `src/Prop.php` | Single Cue/prop wrapper |
| `bin/parse-props.php` | CLI summary tool |

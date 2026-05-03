# CCLI Library API

> PHP module for reading and writing the global ProPresenter `CCLI` file (raw
> protobuf, no extension) that controls copyright display settings.

## Quick Reference

```php
use ProPresenter\Parser\CCLIFileReader;
use ProPresenter\Parser\CCLIFileWriter;

$library = CCLIFileReader::read('/path/to/CCLI');

$library->isCCLIDisplayEnabled(); // bool
$library->getCCLILicense();       // string
$library->getDisplayType();       // int enum value
$library->getTemplate();          // ?\Rv\Data\Template\Slide

$library->setCCLILicense('1234567');
CCLIFileWriter::write($library, '/path/to/CCLI');
```

---

## File Layout

The `CCLI` file is the protobuf-serialised
[`CCLIDocument`](../../proto/ccli.proto):

| Field | Type | Description |
|-------|------|-------------|
| `application_info` | `ApplicationInfo` | ProPresenter writer metadata |
| `enable_ccli_display` | bool | Whether copyright info is shown |
| `ccli_license` | string | CCLI license number |
| `display_type` | `CCLIDocument.DisplayType` | First, last, first+last, or all slides |
| `template` | `Template.Slide` | Text/template styling for display |

---

## Reading

```php
use ProPresenter\Parser\CCLIFileReader;

$library = CCLIFileReader::read('/Users/me/.../CCLI');
```

Throws `InvalidArgumentException` for missing files and `RuntimeException` for
empty / unreadable files.

---

## CCLILibrary

Top-level wrapper around `Rv\Data\CCLIDocument`. This is a single-document
configuration file; `count()` returns `1` when read successfully.

```php
$library->count();
$library->isCCLIDisplayEnabled();
$library->setCCLIDisplayEnabled(true);
$library->getCCLILicense();
$library->setCCLILicense('1234567');
$library->getDisplayType();
$library->setDisplayType(3);
$library->getTemplate();
$library->setTemplate($slideOrNull);
$library->getDocument();                  // \Rv\Data\CCLIDocument
```

---

## CLI Tool

```bash
php bin/parse-ccli.php /path/to/CCLI
```

Output:

```
CCLI (1):
  [1] enabled=yes :: license=(empty) :: display_type=0 :: template=yes
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/CCLILibrary.php` | Document-level wrapper |
| `src/CCLIFileReader.php` | Reads the `CCLI` file |
| `src/CCLIFileWriter.php` | Writes the `CCLI` file |
| `bin/parse-ccli.php` | CLI tool |
| `proto/ccli.proto` | Protobuf schema |
| `generated/Rv/Data/CCLIDocument.php` | Generated message class |

---

## Scope Notes

The wrapper preserves template data and application metadata by mutating the
generated protobuf in place. It does not inspect or render the slide template.

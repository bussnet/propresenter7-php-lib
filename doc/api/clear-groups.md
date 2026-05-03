# ClearGroups Library API

> PHP module for reading and writing the global ProPresenter `ClearGroups` file
> (raw protobuf, no extension) and exposing each clear group definition.

## Quick Reference

```php
use ProPresenter\Parser\ClearGroupsFileReader;
use ProPresenter\Parser\ClearGroupsFileWriter;

$library = ClearGroupsFileReader::read('/path/to/ClearGroups');

foreach ($library->getGroups() as $group) {
    $group->getName();      // "Alles ausblenden"
    $group->getUuid();      // "A91C6AFE-..."
    $group->getImageType(); // 11
    $group->getColorHex();  // "#FFFFFF" | null
}

ClearGroupsFileWriter::write($library, '/path/to/ClearGroups');
```

---

## File Layout

The `ClearGroups` file is the protobuf-serialised
[`ClearGroupsDocument`](../../proto/clearGroups.proto):

| Field | Type | Description |
|-------|------|-------------|
| `application_info` | `ApplicationInfo` | ProPresenter writer metadata |
| `groups` | repeated `ClearGroupsDocument.ClearGroup` | Clear button definitions |

Each `ClearGroup` carries:

| Field | Type | Description |
|-------|------|-------------|
| `uuid` | `UUID` | Stable identifier |
| `name` | string | Display name |
| `layer_targets` | repeated `Action.ClearType` | Layers cleared by the button |
| `is_hidden_in_preview` | bool | Whether preview UI hides the button |
| `image_data` | bytes | Custom icon payload |
| `image_type` | enum | Built-in icon identifier |
| `is_icon_tinted` | bool | Whether `icon_tint_color` applies |
| `icon_tint_color` | `Color` | RGBA float channels in 0..1 |
| `timeline_targets` | repeated `Action.ContentDestination` | Timeline destinations |
| `clear_presentation_next_slide` | bool | Also clear the queued next slide |

---

## Reading

```php
use ProPresenter\Parser\ClearGroupsFileReader;

$library = ClearGroupsFileReader::read('/Users/me/.../ClearGroups');
```

Throws `InvalidArgumentException` for missing files and `RuntimeException` for
empty / unreadable files.

---

## ClearGroupsLibrary

Top-level wrapper around `Rv\Data\ClearGroupsDocument`. Indexes groups by UUID
(case-insensitive) and name.

```php
$library->getGroups();                         // ClearGroupDefinition[]
$library->count();                             // int
$library->getClearGroupByUuid('A91C...');      // ?ClearGroupDefinition
$library->getClearGroupByName('Alles ...');    // ?ClearGroupDefinition
$library->addClearGroup('Name', 'UUID');       // ClearGroupDefinition
$library->removeClearGroup('UUID');            // bool
$library->getDocument();                       // \Rv\Data\ClearGroupsDocument
```

---

## ClearGroupDefinition

```php
$group->getName();
$group->setName('New Name');
$group->getUuid();
$group->setUuid('...');
$group->getLayerTargets();
$group->getImageType();
$group->getColor();       // ['r'=>..,'g'=>..,'b'=>..,'a'=>..] | null
$group->getColorHex();    // "#RRGGBB" uppercase, alpha dropped, or null
$group->getProto();       // \Rv\Data\ClearGroupsDocument\ClearGroup
```

Color channels are floats in 0..1 as ProPresenter stores them. `getColorHex()`
clamps and rounds each channel to 8 bits before formatting.

---

## CLI Tool

```bash
php bin/parse-clear-groups.php /path/to/ClearGroups
```

Output:

```
ClearGroups (1):
  [1] Alles ausblenden :: A91C6AFE-098F-4559-B2CF-D8373C589589 :: image_type=11 :: #FFFFFF
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/ClearGroupsLibrary.php` | Document-level wrapper with UUID/name lookups |
| `src/ClearGroupDefinition.php` | Single clear group wrapper |
| `src/ClearGroupsFileReader.php` | Reads the `ClearGroups` file |
| `src/ClearGroupsFileWriter.php` | Writes the `ClearGroups` file |
| `bin/parse-clear-groups.php` | CLI tool |
| `proto/clearGroups.proto` | Protobuf schema |
| `generated/Rv/Data/ClearGroupsDocument.php` | Generated message class |

---

## Scope Notes

The wrapper preserves unknown / uninterpreted protobuf data by mutating the
generated message in place and serialising it back. It does not execute clear
actions or modify slide content.

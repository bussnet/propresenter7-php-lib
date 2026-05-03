# KeyMappings Library API

> PHP module for reading and writing the global ProPresenter `KeyMappings` file
> (raw protobuf, no extension) and exposing configured hot-key mappings.

## Quick Reference

```php
use ProPresenter\Parser\KeyMappingsFileReader;
use ProPresenter\Parser\KeyMappingsFileWriter;

$library = KeyMappingsFileReader::read('/path/to/KeyMappings');

foreach ($library->getMappings() as $mapping) {
    $mapping->getName();   // string
    $mapping->getUuid();   // string
    $mapping->getHotKey(); // ?\Rv\Data\HotKey
    $mapping->getTarget(); // raw bytes
}

KeyMappingsFileWriter::write($library, '/path/to/KeyMappings');
```

---

## File Layout

The `KeyMappings` file is the protobuf-serialised
[`KeyMappingsDocument`](../../proto/keyMappings.proto):

| Field | Type | Description |
|-------|------|-------------|
| `application_info` | `ApplicationInfo` | ProPresenter writer metadata |
| `mappings` | repeated `KeyMappingsDocument.Mapping` | Configured key bindings |

Each `Mapping` carries:

| Field | Type | Description |
|-------|------|-------------|
| `uuid` | `UUID` | Optional stable mapping identifier |
| `hot_key` | `HotKey` | Key combo that fires the action |
| `target` | bytes | Raw target reference |
| `name` | string | Optional display name |

---

## Reading

```php
use ProPresenter\Parser\KeyMappingsFileReader;

$library = KeyMappingsFileReader::read('/Users/me/.../KeyMappings');
```

Throws `InvalidArgumentException` for missing files and `RuntimeException` for
empty / unreadable files.

---

## KeyMappingsLibrary

Top-level wrapper around `Rv\Data\KeyMappingsDocument`. Indexes mappings by UUID
(case-insensitive) and name.

```php
$library->getMappings();
$library->count();
$library->getMappingByUuid('...');
$library->getMappingByName('Macro trigger');
$library->addMapping('Macro trigger', 'UUID', $targetBytes);
$library->removeMapping('UUID');
$library->getApplicationInfo();
$library->setApplicationInfo($infoOrNull);
$library->getDocument(); // \Rv\Data\KeyMappingsDocument
```

---

## KeyMapping

```php
$mapping->getName();
$mapping->setName('New Name');
$mapping->getUuid();
$mapping->setUuid('...');
$mapping->getHotKey();
$mapping->setHotKey($hotKeyOrNull);
$mapping->getTarget();
$mapping->setTarget($bytes);
$mapping->getProto(); // \Rv\Data\KeyMappingsDocument\Mapping
```

Targets are raw bytes because ProPresenter may encode several internal target
types here. Keeping bytes opaque preserves round-trip safety.

---

## CLI Tool

```bash
php bin/parse-key-mappings.php /path/to/KeyMappings
```

Output for the reference sample:

```
KeyMappings (0):
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/KeyMappingsLibrary.php` | Document-level wrapper with UUID/name lookups |
| `src/KeyMapping.php` | Single mapping wrapper |
| `src/KeyMappingsFileReader.php` | Reads the `KeyMappings` file |
| `src/KeyMappingsFileWriter.php` | Writes the `KeyMappings` file |
| `bin/parse-key-mappings.php` | CLI tool |
| `proto/keyMappings.proto` | Protobuf schema |
| `generated/Rv/Data/KeyMappingsDocument.php` | Generated message class |

---

## Scope Notes

The reference sample contains only `ApplicationInfo` and no mappings. The API
still supports mapping additions/removals so configured user files can be edited
and round-tripped safely.

# Groups Library API

> PHP module for the global ProPresenter `Groups` file (raw protobuf, no
> extension). Exposes every named group definition (UUID, name, color,
> hot key) used to organise slides across songs and presentations.

## Quick Reference

```php
use ProPresenter\Parser\GroupsFileReader;
use ProPresenter\Parser\GroupsFileWriter;

$library = GroupsFileReader::read('/path/to/Groups');

foreach ($library->getGroups() as $group) {
    $group->getName();      // "Verse 1"
    $group->getUuid();      // "1D85C82C-EC82-44D8-8ED0-7742D46242C0"
    $group->getColorHex();  // "#0077CC" | null
}

$library->addGroup('Bridge', '...uuid...');
GroupsFileWriter::write($library, '/path/to/Groups');
```

---

## File Layout

The `Groups` file is the protobuf-serialised
[`ProGroupsDocument`](../../proto/groups.proto):

| Field | Type | Description |
|-------|------|-------------|
| `groups` | repeated `Group` | Library group definitions (UUID, name, color, hotKey) |

Each `Group` carries:

| Field | Type | Description |
|-------|------|-------------|
| `uuid` | `UUID` | Stable identifier referenced by song-level cue groups |
| `name` | string | Display name (e.g. "Verse 1") |
| `color` | `Color` (optional) | RGBA float channels |
| `hotKey` | `HotKey` (optional) | Keyboard shortcut binding |
| `application_group_identifier` | `UUID` (optional) | Parent application group |
| `application_group_name` | string (optional) | Parent application group name |

Groups are identified by UUID; names should be unique but the format does
not enforce it.

---

## Reading

```php
use ProPresenter\Parser\GroupsFileReader;

$library = GroupsFileReader::read('/Users/me/.../Groups');
```

Throws `InvalidArgumentException` for missing files and `RuntimeException`
for empty / unreadable files.

---

## Writing

```php
use ProPresenter\Parser\GroupsFileWriter;

GroupsFileWriter::write($library, '/Users/me/.../Groups');
```

The writer serialises the underlying `ProGroupsDocument` back to bytes and
saves them. The unmodified reference sample round-trips byte-for-byte.

---

## GroupLibrary

Top-level wrapper around `Rv\Data\ProGroupsDocument`. Indexes groups by
UUID (case-insensitive) and by name for fast lookup.

```php
$library->getGroups();                   // GroupDefinition[]
$library->count();                       // int
$library->getGroupByUuid('1D85C82C-...'); // ?GroupDefinition (case-insensitive)
$library->getGroupByName('Verse 1');     // ?GroupDefinition

$library->addGroup('Bridge', '...uuid...');     // GroupDefinition
$library->removeGroup('...uuid...');             // bool

$library->getDocument();                 // \Rv\Data\ProGroupsDocument
```

If the same UUID or name appears more than once the first occurrence wins
for lookups; every entry is preserved in `getGroups()` in document order.

---

## GroupDefinition

```php
$group->getUuid();                      // "1D85C82C-EC82-44D8-8ED0-7742D46242C0"
$group->setUuid('...');                 // self
$group->getName();                      // "Verse 1"
$group->setName('Verse 2');             // self
$group->getColor();                     // ['r'=>..,'g'=>..,'b'=>..,'a'=>..] | null
$group->getColorHex();                  // "#0077CC" | null
$group->setColor(['r'=>1, 'g'=>0, 'b'=>0]); // self
$group->getHotKey();                    // ?\Rv\Data\HotKey
$group->getApplicationGroupName();      // string
$group->getApplicationGroupUuid();      // string
$group->getProto();                     // \Rv\Data\Group (raw protobuf)
```

The `GroupDefinition` class name is intentionally distinct from the
existing `Group` class which wraps song-level `CueGroup` objects (slide
references, not library definitions).

---

## CLI Tool

```bash
php bin/parse-groups.php /path/to/Groups
```

Output:

```
Groups (29):
  [1] Vers :: 4E9D56A2-7E96-4975-97CC-44982257EF8A :: #0077CC
  [2] Verse 1 :: 1D85C82C-EC82-44D8-8ED0-7742D46242C0 :: #0077CC
  ...
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/GroupLibrary.php` | Document-level wrapper with name / UUID lookup |
| `src/GroupDefinition.php` | Single library group (distinct from `Group` / `CueGroup`) |
| `src/GroupsFileReader.php` | Reads the `Groups` file |
| `src/GroupsFileWriter.php` | Writes the `Groups` file |
| `bin/parse-groups.php` | CLI tool |
| `proto/groups.proto` | Protobuf schema |
| `generated/Rv/Data/ProGroupsDocument.php` | Generated message class |
| `generated/Rv/Data/Group.php` | Generated group message class |

---

## Naming Disambiguation

The codebase has two `Group`-shaped classes for two different scopes:

| Class | Scope | Wraps |
|-------|-------|-------|
| `Group` | Song-level slide collection | `Rv\Data\Presentation\CueGroup` |
| `GroupDefinition` | Library-level group definition | `Rv\Data\Group` |

Songs reference library groups by UUID. The two classes co-exist because
ProPresenter's data model has the same name in both places.

---

## Scope Notes

This module covers reading and writing the `Groups` document. Wiring up
hot keys to actions and editing application group hierarchies are out of
scope; reach for `getHotKey()` / `getProto()` to inspect them when needed.

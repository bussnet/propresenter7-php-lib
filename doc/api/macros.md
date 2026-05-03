# Macros Library API

> PHP module for reading the global ProPresenter `Macros` file (raw protobuf,
> no extension) and exposing each macro's name, UUID, and collection
> membership.

## Quick Reference

```php
use ProPresenter\Parser\MacrosFileReader;
use ProPresenter\Parser\MacrosFileWriter;

$library = MacrosFileReader::read('/path/to/Macros');

foreach ($library->getMacros() as $macro) {
    $macro->getName();        // "Gottesdienst START"
    $macro->getUuid();        // "FA0602E4-EDA2-4457-BB62-68AA17184217"
    $macro->getColor();       // ['r'=>..,'g'=>..,'b'=>..,'a'=>..] | null
    $macro->getImageType();   // int — see ImageType enum
    $macro->getImageData();   // bytes — custom icon (empty for built-ins)

    foreach ($library->getCollectionsForMacro($macro) as $collection) {
        $collection->getName();
        $collection->getUuid();
    }
}

// Modify and persist
$library->addMacro('NewMacro', '...uuid...');
$library->getMacroByName('NewMacro')?->setColor(['r'=>0.5, 'g'=>0, 'b'=>1]);
MacrosFileWriter::write($library, '/path/to/Macros');
```

---

## File Layout

The `Macros` file is the protobuf-serialised
[`MacrosDocument`](../../proto/macros.proto):

| Field | Type | Description |
|-------|------|-------------|
| `application_info` | message | ProPresenter version + flags that wrote the file |
| `macros` | repeated `Macro` | Definitions: UUID, name, color, actions, icon, startup flag |
| `macro_collections` | repeated `MacroCollection` | UUID, name, ordered list of `macro_id` references |

Macros and collections live at the document root. Membership is by UUID
reference — a macro may appear in zero, one, or multiple collections.

---

## Reading

```php
use ProPresenter\Parser\MacrosFileReader;

$library = MacrosFileReader::read('/Users/me/.../Macros');
```

Throws `InvalidArgumentException` for missing files and `RuntimeException` for
empty / unreadable files.

---

## Writing

```php
use ProPresenter\Parser\MacrosFileWriter;

MacrosFileWriter::write($library, '/Users/me/.../Macros');
```

Serialises the underlying `MacrosDocument` to bytes. Round-trip preserves the
overall byte length; field ordering can vary slightly because the protobuf
PHP runtime is not guaranteed to be canonical.

---

## MacroLibrary

Top-level wrapper around `Rv\Data\MacrosDocument`. Indexes macros and
collections for fast lookup.

```php
$library->getMacros();                      // Macro[]
$library->getMacroByUuid('FA06...');        // ?Macro (case-insensitive)
$library->getMacroByName('Lied 1.Folie');   // ?Macro

$library->getCollections();                 // MacroCollection[]
$library->getCollectionByUuid('8D02...');   // ?MacroCollection (case-insensitive)
$library->getCollectionByName('Ablauf');    // ?MacroCollection

// Cross-reference helpers
$library->getMacrosForCollection($collection);  // Macro[] in declared order
$library->getCollectionsForMacro($macro);       // MacroCollection[] (membership)

// Mutators
$library->addMacro('NewMacro', '...uuid...');           // Macro
$library->removeMacro('...uuid...');                     // bool
$library->addCollection('NewCollection', '...uuid...'); // MacroCollection
$library->removeCollection('...uuid...');                // bool

$library->getDocument();   // \Rv\Data\MacrosDocument (raw protobuf)
```

---

## Macro

```php
$macro->getUuid();                // "FA0602E4-..."
$macro->setUuid('...');           // self
$macro->getName();                // "Gottesdienst START"
$macro->setName('...');           // self
$macro->getColor();               // ['r'=>..,'g'=>..,'b'=>..,'a'=>..] | null
$macro->setColor(['r'=>0.5,'g'=>0,'b'=>1]); // self (or null to clear)
$macro->getTriggerOnStartup();    // bool
$macro->setTriggerOnStartup(true); // self
$macro->getActionCount();         // int — number of attached Action entries
$macro->getImageType();           // int — see Rv\Data\MacrosDocument\Macro\ImageType
$macro->setImageType(...);        // self — pass an ImageType enum value
$macro->getImageData();           // string — custom icon bytes (empty for built-ins)
$macro->setImageData($pngBytes);  // self — set a custom icon
$macro->getProto();               // \Rv\Data\MacrosDocument\Macro
```

Action payloads are not unwrapped by this library; reach for `getProto()` and
walk `getActions()` directly when needed.

---

## MacroCollection

```php
$collection->getUuid();              // "8D02FC57-..."
$collection->setUuid('...');         // self
$collection->getName();              // "Ablauf"
$collection->setName('...');         // self
$collection->getMacroUuids();        // string[] — referenced macro UUIDs in order
$collection->setMacroUuids(['...']); // self — replace all referenced UUIDs
$collection->addMacroUuid('...');    // self — append a single reference
$collection->getProto();             // \Rv\Data\MacrosDocument\MacroCollection
```

Items use a protobuf `oneof ItemType`; only `macro_id` is currently defined.
Items without a populated reference are skipped.

---

## CLI Tool

```bash
php bin/parse-macros.php /path/to/Macros
```

Output:

```
Macros (24):
  [1] Gottesdienst START :: FA0602E4-EDA2-4457-BB62-68AA17184217 (1 action) [in: Ablauf]
  ...

Collections (3):
  [1] Ablauf :: 8D02FC57-83F8-4042-9B90-81C229728426 (12 macros)
      1. Gottesdienst START :: FA0602E4-EDA2-4457-BB62-68AA17184217
      ...
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/MacroLibrary.php` | Document-level wrapper with lookup + add / remove helpers |
| `src/Macro.php` | Single macro wrapper with setters |
| `src/MacroCollection.php` | Collection wrapper with setters |
| `src/MacrosFileReader.php` | Reads the `Macros` file |
| `src/MacrosFileWriter.php` | Writes the `Macros` file |
| `bin/parse-macros.php` | CLI tool |
| `proto/macros.proto` | Protobuf schema |
| `generated/Rv/Data/MacrosDocument.php` | Generated message classes |

---

## Scope Notes

Action editing (the inner `repeated Action actions` field on each macro) and
slide-side macro references on `.pro` files (see `Slide::getMacroUuid()` /
`Slide::setMacro()`) are out of scope. This module covers the global
`Macros` document only; reach for `getProto()->getActions()` for raw action
inspection.

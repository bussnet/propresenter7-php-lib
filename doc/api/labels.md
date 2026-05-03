# Labels Library API

> PHP module for reading the global ProPresenter `Labels` file (raw protobuf,
> no extension) and exposing each label's name and UI color.

## Quick Reference

```php
use ProPresenter\Parser\LabelsFileReader;
use ProPresenter\Parser\LabelsFileWriter;

$library = LabelsFileReader::read('/path/to/Labels');

foreach ($library->getLabels() as $label) {
    $label->getName();      // "KeyVisual Beamer"
    $label->hasColor();     // bool
    $label->getColor();     // ['r'=>0.0,'g'=>0.408,'b'=>0.702,'a'=>1.0] | null
    $label->getColorHex();  // "#0068B3" | null
}

// Modify and persist
$library->addLabel('NewLabel', ['r' => 1.0, 'g' => 0.5, 'b' => 0.0]);
$beamer = $library->getLabelByName('KeyVisual Beamer');
$beamer?->setColorHex('#FF8800');
$library->removeLabel('Wiederholen');

LabelsFileWriter::write($library, '/path/to/Labels');
```

---

## File Layout

The `Labels` file is the protobuf-serialised
[`ProLabelsDocument`](../../proto/labels.proto):

| Field | Type | Description |
|-------|------|-------------|
| `labels` | repeated `Action.Label` | Definitions: text + optional color |

Each `Action.Label` carries:

| Field | Type | Description |
|-------|------|-------------|
| `text` | string | Display name (exposed as `getName()` on the wrapper) |
| `color` | `Color` (optional) | RGBA float channels in 0..1; absent for system / "no color" labels |

Labels are identified by name only — there is no UUID. Slides reference
labels by name from inside `.pro` files.

---

## Reading

```php
use ProPresenter\Parser\LabelsFileReader;

$library = LabelsFileReader::read('/Users/me/.../Labels');
```

Throws `InvalidArgumentException` for missing files and `RuntimeException` for
empty / unreadable files.

---

## Writing

```php
use ProPresenter\Parser\LabelsFileWriter;

LabelsFileWriter::write($library, '/Users/me/.../Labels');
```

Serialises the underlying `ProLabelsDocument` to bytes. The unmodified
reference sample round-trips byte-for-byte.

---

## LabelLibrary

Top-level wrapper around `Rv\Data\ProLabelsDocument`. Indexes labels by name
for fast lookup.

```php
$library->getLabels();                       // Label[]
$library->count();                           // int
$library->getLabelByName('Szene 1');         // ?Label  (case-sensitive)
$library->findLabelByName('szene 1');        // ?Label  (case-insensitive)

$library->addLabel('NewLabel', ['r'=>1, 'g'=>0, 'b'=>0]); // ?Label
$library->removeLabel('OldLabel');                          // bool
$library->getDocument();                                    // \Rv\Data\ProLabelsDocument
```

If the same name appears more than once in the source document the first
occurrence wins for both lookup helpers; every entry is preserved in
`getLabels()` in document order.

---

## Label

```php
$label->getName();          // "KeyVisual Beamer"  (proto field is `text`)
$label->setName('Renamed'); // self
$label->hasColor();         // bool — was a Color message present?
$label->getColor();         // ['r'=>..,'g'=>..,'b'=>..,'a'=>..] | null
$label->getColorHex();      // "#RRGGBB" uppercase, alpha dropped, or null
$label->setColor(['r'=>1, 'g'=>0, 'b'=>0]); // self
$label->setColor(null);     // clears the color (UI falls back to default)
$label->setColorHex('#FF8800'); // accepts #RRGGBB or #RRGGBBAA
$label->getProto();         // \Rv\Data\Action\Label (raw protobuf)
```

Color channels are floats in 0..1 as ProPresenter stores them. `getColorHex()`
clamps and rounds each channel to 8 bits before formatting.

A label can legitimately exist without a `color` message. Treat that as
"use the default UI color", not as black. The reference sample's first four
labels (`Leere Folie`, `Instrumental`, `Wiederholen`, `Gesprochenes Wort`)
hit this case.

---

## CLI Tool

```bash
php bin/parse-labels.php /path/to/Labels
```

Output:

```
Labels (15):
  [1] Leere Folie :: (no color)
  [2] Instrumental :: (no color)
  [3] Wiederholen :: (no color)
  [4] Gesprochenes Wort :: (no color)
  [5] KeyVisual Stream & Beamer mit Countdown :: #CC298B  rgba(0.800, 0.161, 0.545, 1.000)
  [6] KeyVisual Stream & Beamer mit Jingle :: #7600CC  rgba(0.463, 0.000, 0.800, 1.000)
  ...
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/LabelLibrary.php` | Document-level wrapper with name lookups + add / remove helpers |
| `src/Label.php` | Single label wrapper (name, color, hex) with setters |
| `src/LabelsFileReader.php` | Reads the `Labels` file |
| `src/LabelsFileWriter.php` | Writes the `Labels` file |
| `bin/parse-labels.php` | CLI tool |
| `proto/labels.proto` | Protobuf schema (just imports `Action.Label`) |
| `proto/action.proto` | Defines the inner `Action.Label` message |
| `generated/Rv/Data/ProLabelsDocument.php` | Generated message class |
| `generated/Rv/Data/Action/Label.php` | Generated label message class |

---

## Scope Notes

Editing slide-side label references on `.pro` files (cross-document fan-out)
and syncing labels across devices are out of scope; this module only covers
the global `Labels` document.

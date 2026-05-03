# TestPatterns Library API

> PHP module for reading and writing the global ProPresenter `TestPatterns` file
> (raw protobuf, no extension), including selected test-pattern state and saved
> pattern definitions.

## Quick Reference

```php
use ProPresenter\Parser\TestPatternsFileReader;
use ProPresenter\Parser\TestPatternsFileWriter;

$library = TestPatternsFileReader::read('/path/to/TestPatterns');

$library->getDisplayLocation();      // 3
$library->getSpecificScreenUuid();   // "BCDE1115-..."
$library->getPatterns();             // TestPatternData[]

TestPatternsFileWriter::write($library, '/path/to/TestPatterns');
```

---

## File Layout

The `TestPatterns` file is the protobuf-serialised
[`TestPatternDocument`](../../proto/testPattern.proto):

| Field | Type | Description |
|-------|------|-------------|
| `state` | `TestPatternDocument.TestPatternStateData` | Current test-pattern display state |
| `patterns` | repeated `TestPatternDocument.TestPatternData` | Saved pattern definitions |

`TestPatternStateData` includes selected pattern UUID/name, display location,
specific screen UUID, identify-screen flag, logo type, and optional user logo.

---

## Reading

```php
use ProPresenter\Parser\TestPatternsFileReader;

$library = TestPatternsFileReader::read('/Users/me/.../TestPatterns');
```

Throws `InvalidArgumentException` for missing files and `RuntimeException` for
empty / unreadable files.

---

## TestPatternsLibrary

Top-level wrapper around `Rv\Data\TestPatternDocument`. Indexes saved pattern
definitions by UUID (case-insensitive) and localization key.

```php
$library->getState();
$library->setState($stateOrNull);
$library->getSelectedPatternUuid();
$library->getSelectedPatternNameLocalizationKey();
$library->getDisplayLocation();
$library->getSpecificScreenUuid();
$library->getPatterns();
$library->count();
$library->getPatternByUuid('...');
$library->getPatternByName('Test Pattern');
$library->addPattern('Test Pattern', 'UUID');
$library->removePattern('UUID');
$library->getDocument(); // \Rv\Data\TestPatternDocument
```

---

## CLI Tool

```bash
php bin/parse-test-patterns.php /path/to/TestPatterns
```

Output:

```
TestPatterns (0):
  State: selected=(none) :: name=(none) :: display_location=3 :: screen=BCDE1115-AD40-4BA4-A33A-BFFE3E87223B
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/TestPatternsLibrary.php` | Document-level wrapper with state accessors |
| `src/TestPatternsFileReader.php` | Reads the `TestPatterns` file |
| `src/TestPatternsFileWriter.php` | Writes the `TestPatterns` file |
| `bin/parse-test-patterns.php` | CLI tool |
| `proto/testPattern.proto` | Protobuf schema |
| `generated/Rv/Data/TestPatternDocument.php` | Generated message class |

---

## Scope Notes

The wrapper exposes `TestPatternData` and `TestPatternStateData` protobufs
directly. It does not render test patterns or interpret nested property oneofs.

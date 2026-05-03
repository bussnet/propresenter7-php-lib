# Calendar Library API

> PHP module for reading and writing the global ProPresenter `Calendar` file
> (raw protobuf, no extension) and preserving scheduled macro events.

## Quick Reference

```php
use ProPresenter\Parser\CalendarFileReader;

$library = CalendarFileReader::read('/path/to/Calendar');

foreach ($library->getEvents() as $event) {
    $event->getName();
    $event->getStartTimeSeconds();
    $event->getActionData(); // raw bytes
}
```

---

## File Layout

The `Calendar` file is the protobuf-serialised
[`CalendarDocument`](../../proto/calendar.proto):

| Field | Type | Description |
|-------|------|-------------|
| `events` | repeated `CalendarDocument.Event` | Scheduled events |
| `mode` | `uint32` | Opaque document mode/source flag |

Each event includes UUID, name, start/end timestamps, opaque `flags`, and two
raw bytes fields: `action_data` (field 8) and `macro_data` (field 9). Those
bytes are intentionally not decoded by this wrapper.

---

## Reading

```php
$library = CalendarFileReader::read('/Users/me/.../Calendar');
```

Throws `InvalidArgumentException` for missing files and `RuntimeException` for
empty / unreadable files.

---

## CalendarLibrary

```php
$library->getEvents();              // CalendarEvent[]
$library->count();                  // int
$library->getEventByUuid($uuid);    // ?CalendarEvent (case-insensitive)
$library->getEventByName($name);    // ?CalendarEvent
$library->addEvent($name, $uuid);   // CalendarEvent
$library->removeEvent($uuid);       // bool
$library->getMode();                // int
$library->setMode(1);               // void
$library->getDocument();            // \Rv\Data\CalendarDocument
```

---

## CalendarEvent

```php
$event->getUuid();
$event->setUuid($uuid);
$event->getName();
$event->setName($name);
$event->getStartTime();
$event->setStartTime($timestamp);
$event->getStartTimeSeconds();
$event->setStartTimeSeconds($seconds);
$event->getEndTime();
$event->getEndTimeSeconds();
$event->getFlags();
$event->setFlags($bytes);
$event->getActionData(); // raw protobuf bytes
$event->setActionData($bytes);
$event->getMacroData();  // raw protobuf bytes
$event->setMacroData($bytes);
$event->getProto();
```

---

## CLI Tool

```bash
php bin/parse-calendar.php /path/to/Calendar
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/CalendarLibrary.php` | Document wrapper with UUID / name indexes |
| `src/CalendarEvent.php` | Single event wrapper |
| `src/CalendarFileReader.php` | Reads the `Calendar` file |
| `src/CalendarFileWriter.php` | Writes the `Calendar` file |
| `bin/parse-calendar.php` | CLI summary tool |
| `proto/calendar.proto` | Protobuf schema |
| `generated/Rv/Data/CalendarDocument.php` | Generated document class |

---

## Scope Notes

`action_data` and `macro_data` are raw protobuf byte strings. They are exposed
directly for byte-preserving edits and round trips; semantic decoding belongs in
a future schema-specific module.

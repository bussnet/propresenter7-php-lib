# Timers Library API

> PHP module for reading and writing the global ProPresenter `Timers` file
> (raw protobuf, no extension), including the top-level clock format.

## Quick Reference

```php
use ProPresenter\Parser\TimersFileReader;

$library = TimersFileReader::read('/path/to/Timers');
$library->getClockFormat(); // "HH:mm"

foreach ($library->getTimers() as $timer) {
    $timer->getName();
    $timer->isCountdown();
    $timer->getDurationSeconds();
}
```

---

## File Layout

The `Timers` file is the protobuf-serialised
[`TimersDocument`](../../proto/timers.proto):

| Field | Type | Description |
|-------|------|-------------|
| `application_info` | `ApplicationInfo` | ProPresenter metadata |
| `clock` | `Clock` | Global clock display settings |
| `timers` | repeated `Timer` | Timer definitions |

---

## Reading

```php
$library = TimersFileReader::read('/Users/me/.../Timers');
```

Throws `InvalidArgumentException` for missing files and `RuntimeException` for
empty / unreadable files.

---

## TimersLibrary

```php
$library->getTimers();              // Timer[]
$library->count();                  // int
$library->getTimerByUuid($uuid);    // ?Timer (case-insensitive)
$library->getTimerByName($name);    // ?Timer
$library->addTimer($name, $uuid);   // Timer
$library->removeTimer($uuid);       // bool
$library->getClockFormat();         // string
$library->setClockFormat('HH:mm');  // void
$library->getApplicationInfo();     // ?\Rv\Data\ApplicationInfo
$library->getDocument();            // \Rv\Data\TimersDocument
```

---

## Timer

```php
$timer->getUuid();
$timer->setUuid($uuid);
$timer->getName();
$timer->setName($name);
$timer->getConfiguration();     // ?\Rv\Data\Timer\Configuration
$timer->isCountdown();
$timer->isCountdownToTime();
$timer->isElapsedTime();
$timer->getDurationSeconds();   // ?int for countdown timers
$timer->getProto();             // \Rv\Data\Timer
```

---

## CLI Tool

```bash
php bin/parse-timers.php /path/to/Timers
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/TimersLibrary.php` | Document wrapper with UUID / name indexes |
| `src/Timer.php` | Single timer wrapper |
| `src/TimersFileReader.php` | Reads the `Timers` file |
| `src/TimersFileWriter.php` | Writes the `Timers` file |
| `bin/parse-timers.php` | CLI summary tool |
| `generated/Rv/Data/TimersDocument.php` | Generated document class |

---

## Scope Notes

Timer configuration is exposed as the generated protobuf sub-message. Helper
methods cover the oneof timer type and countdown duration without hiding raw
access for callers that need advanced fields.

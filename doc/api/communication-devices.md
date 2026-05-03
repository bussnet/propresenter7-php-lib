# CommunicationDevices Library API

> PHP module for reading and writing the global ProPresenter
> `CommunicationDevices` file. Unlike most config files in this project, this
> file is JSON, not protobuf.

## Quick Reference

```php
use ProPresenter\Parser\CommunicationDevice;
use ProPresenter\Parser\CommunicationDevicesFileReader;
use ProPresenter\Parser\CommunicationDevicesFileWriter;

$library = CommunicationDevicesFileReader::read('/path/to/CommunicationDevices');
$library->addDevice((new CommunicationDevice())->setId('device-1')->setName('Router'));
CommunicationDevicesFileWriter::write($library, '/path/to/CommunicationDevices');
```

---

## File Layout

`CommunicationDevices` is a JSON array. The reference sample is `[]`, so the
wrapper preserves arbitrary object fields while exposing forward-looking common
fields: `id`, `name`, `type`, and `address`.

---

## Reading

```php
$library = CommunicationDevicesFileReader::read('/Users/me/.../CommunicationDevices');
```

Throws `InvalidArgumentException` for missing files and `RuntimeException` for
unreadable files or invalid JSON.

---

## CommunicationDevicesLibrary

```php
CommunicationDevicesLibrary::fromJson($json); // CommunicationDevicesLibrary
$library->toJson();                           // string
$library->getDocument();                      // raw decoded array
$library->getDevices();                       // CommunicationDevice[]
$library->addDevice($device);                 // CommunicationDevice
$library->removeDevice($id);                  // bool
$library->count();                            // int
```

---

## CommunicationDevice

```php
$device->getId();
$device->setId($id);
$device->getName();
$device->setName($name);
$device->getType();
$device->setType($type);
$device->getAddress();
$device->setAddress($address);
$device->toArray(); // full decoded JSON object
```

---

## CLI Tool

```bash
php bin/parse-communication-devices.php /path/to/CommunicationDevices
```

Empty files print a useful `(none configured)` summary.

---

## Key Files

| File | Purpose |
|------|---------|
| `src/CommunicationDevicesLibrary.php` | JSON-array wrapper |
| `src/CommunicationDevice.php` | Single JSON device value object |
| `src/CommunicationDevicesFileReader.php` | Reads and validates JSON |
| `src/CommunicationDevicesFileWriter.php` | Writes compact JSON |
| `bin/parse-communication-devices.php` | CLI summary tool |

---

## Scope Notes

Because only an empty sample is available, unknown JSON fields are preserved in
each device's backing array. The writer uses compact JSON with unescaped
slashes / Unicode for stable semantic round trips.

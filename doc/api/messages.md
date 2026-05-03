# Messages Library API

> PHP module for reading and writing the global ProPresenter `Messages` file
> (raw protobuf, no extension) and exposing each message definition.

## Quick Reference

```php
use ProPresenter\Parser\MessagesFileReader;
use ProPresenter\Parser\MessagesFileWriter;

$library = MessagesFileReader::read('/path/to/Messages');

foreach ($library->getMessages() as $message) {
    $message->getTitle();
    $message->getUuid();
    $message->getMessageText();
}

$library->addMessage('Lobby Notice', '11111111-1111-1111-1111-111111111111');
MessagesFileWriter::write($library, '/path/to/Messages');
```

---

## File Layout

The `Messages` file is the protobuf-serialised
[`MessageDocument`](../../proto/messages.proto):

| Field | Type | Description |
|-------|------|-------------|
| `application_info` | `ApplicationInfo` | ProPresenter metadata |
| `messages` | repeated `Message` | Message definitions in document order |

---

## Reading

```php
$library = MessagesFileReader::read('/Users/me/.../Messages');
```

Throws `InvalidArgumentException` for missing files and `RuntimeException` for
empty / unreadable files.

---

## MessageLibrary

```php
$library->getMessages();                    // Message[]
$library->count();                          // int
$library->getMessageByUuid($uuid);          // ?Message (case-insensitive)
$library->getMessageByName($title);         // ?Message (case-sensitive title)
$library->addMessage($title, $uuid);        // Message
$library->removeMessage($uuid);             // bool
$library->getApplicationInfo();             // ?\Rv\Data\ApplicationInfo
$library->getDocument();                    // \Rv\Data\MessageDocument
```

---

## Message

```php
$message->getUuid();
$message->setUuid($uuid);
$message->getTitle();
$message->setTitle($title);
$message->getTimeToRemove();
$message->setTimeToRemove($seconds);
$message->isVisibleOnNetwork();
$message->setVisibleOnNetwork(true);
$message->getMessageText();
$message->setMessageText($text);
$message->getClearType();
$message->setClearType($enumValue);
$message->getTokens();      // raw repeated Token protos
$message->getProto();       // \Rv\Data\Message
```

---

## CLI Tool

```bash
php bin/parse-messages.php /path/to/Messages
```

---

## Key Files

| File | Purpose |
|------|---------|
| `src/MessageLibrary.php` | Document wrapper with title / UUID indexes |
| `src/Message.php` | Single message wrapper |
| `src/MessagesFileReader.php` | Reads the `Messages` file |
| `src/MessagesFileWriter.php` | Writes the `Messages` file |
| `bin/parse-messages.php` | CLI summary tool |
| `generated/Rv/Data/MessageDocument.php` | Generated document class |

---

## Scope Notes

Tokens and token values are preserved as raw generated protobuf objects. The
wrapper exposes them for advanced callers but does not interpret template
rendering semantics.

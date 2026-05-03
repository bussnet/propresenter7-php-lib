<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\ApplicationInfo;
use Rv\Data\Message as MessageProto;
use Rv\Data\MessageDocument;
use Rv\Data\UUID;

class MessageLibrary
{
    /** @var Message[] */
    private array $messages = [];

    /** @var array<string, Message> */
    private array $messagesByUuid = [];

    /** @var array<string, Message> */
    private array $messagesByName = [];

    public function __construct(
        private readonly MessageDocument $document,
    ) {
        $this->rebuildIndex();
    }

    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function count(): int
    {
        return count($this->messages);
    }

    public function getMessageByUuid(string $uuid): ?Message
    {
        return $this->messagesByUuid[strtoupper($uuid)] ?? null;
    }

    public function getMessageByName(string $name): ?Message
    {
        return $this->messagesByName[$name] ?? null;
    }

    public function addMessage(string $title, string $uuid): Message
    {
        $proto = new MessageProto();
        $uuidProto = new UUID();
        $uuidProto->setString($uuid);
        $proto->setUuid($uuidProto);
        $proto->setTitle($title);

        $existing = iterator_to_array($this->document->getMessages());
        $existing[] = $proto;
        $this->document->setMessages($existing);
        $this->rebuildIndex();

        return $this->getMessageByUuid($uuid) ?? new Message($proto);
    }

    public function removeMessage(string $uuid): bool
    {
        $needle = strtoupper($uuid);
        $kept = [];
        $removed = false;
        foreach ($this->document->getMessages() as $proto) {
            $current = strtoupper($proto->getUuid()?->getString() ?? '');
            if (!$removed && $current === $needle) {
                $removed = true;
                continue;
            }
            $kept[] = $proto;
        }

        if (!$removed) {
            return false;
        }

        $this->document->setMessages($kept);
        $this->rebuildIndex();

        return true;
    }

    public function getApplicationInfo(): ?ApplicationInfo
    {
        return $this->document->getApplicationInfo();
    }

    public function getDocument(): MessageDocument
    {
        return $this->document;
    }

    private function rebuildIndex(): void
    {
        $this->messages = [];
        $this->messagesByUuid = [];
        $this->messagesByName = [];

        foreach ($this->document->getMessages() as $proto) {
            $message = new Message($proto);
            $this->messages[] = $message;

            $uuid = strtoupper($message->getUuid());
            if ($uuid !== '') {
                $this->messagesByUuid[$uuid] = $message;
            }

            $title = $message->getTitle();
            if ($title !== '') {
                $this->messagesByName[$title] ??= $message;
            }
        }
    }
}

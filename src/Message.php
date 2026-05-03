<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Message as MessageProto;
use Rv\Data\UUID;

class Message
{
    public function __construct(
        private readonly MessageProto $message,
    ) {
    }

    public function getUuid(): string
    {
        return $this->message->getUuid()?->getString() ?? '';
    }

    public function setUuid(string $uuid): self
    {
        $proto = new UUID();
        $proto->setString($uuid);
        $this->message->setUuid($proto);

        return $this;
    }

    public function getTitle(): string
    {
        return $this->message->getTitle();
    }

    public function setTitle(string $title): self
    {
        $this->message->setTitle($title);

        return $this;
    }

    public function getTimeToRemove(): float
    {
        return $this->message->getTimeToRemove();
    }

    public function setTimeToRemove(float $timeToRemove): self
    {
        $this->message->setTimeToRemove($timeToRemove);

        return $this;
    }

    public function isVisibleOnNetwork(): bool
    {
        return $this->message->getVisibleOnNetwork();
    }

    public function setVisibleOnNetwork(bool $visibleOnNetwork): self
    {
        $this->message->setVisibleOnNetwork($visibleOnNetwork);

        return $this;
    }

    public function getMessageText(): string
    {
        return $this->message->getMessageText();
    }

    public function setMessageText(string $messageText): self
    {
        $this->message->setMessageText($messageText);

        return $this;
    }

    public function getClearType(): int
    {
        return $this->message->getClearType();
    }

    public function setClearType(int $clearType): self
    {
        $this->message->setClearType($clearType);

        return $this;
    }

    /**
     * @return mixed raw repeated \Rv\Data\Message\Token protos
     */
    public function getTokens(): mixed
    {
        return $this->message->getTokens();
    }

    public function getProto(): MessageProto
    {
        return $this->message;
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Google\Protobuf\Internal\RepeatedField;
use Rv\Data\Cue;
use Rv\Data\UUID;

class Prop
{
    public function __construct(
        private readonly Cue $cue,
    ) {
    }

    public function getUuid(): string
    {
        return $this->cue->getUuid()?->getString() ?? '';
    }

    public function setUuid(string $uuid): self
    {
        $proto = new UUID();
        $proto->setString($uuid);
        $this->cue->setUuid($proto);

        return $this;
    }

    public function getName(): string
    {
        return $this->cue->getName();
    }

    public function setName(string $name): self
    {
        $this->cue->setName($name);

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->cue->getIsEnabled();
    }

    public function setEnabled(bool $enabled): self
    {
        $this->cue->setIsEnabled($enabled);

        return $this;
    }

    public function getCompletionTime(): float
    {
        return $this->cue->getCompletionTime();
    }

    public function setCompletionTime(float $completionTime): self
    {
        $this->cue->setCompletionTime($completionTime);

        return $this;
    }

    public function getActions(): RepeatedField
    {
        return $this->cue->getActions();
    }

    public function getProto(): Cue
    {
        return $this->cue;
    }
}

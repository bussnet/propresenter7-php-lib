<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Slide;
use Rv\Data\Stage\Layout as LayoutProto;
use Rv\Data\UUID;

class StageLayout
{
    public function __construct(
        private readonly LayoutProto $layout,
    ) {
    }

    public function getUuid(): string
    {
        return $this->layout->getUuid()?->getString() ?? '';
    }

    public function setUuid(string $uuid): self
    {
        $proto = new UUID();
        $proto->setString($uuid);
        $this->layout->setUuid($proto);

        return $this;
    }

    public function getName(): string
    {
        return $this->layout->getName();
    }

    public function setName(string $name): self
    {
        $this->layout->setName($name);

        return $this;
    }

    public function getSlide(): ?Slide
    {
        return $this->layout->getSlide();
    }

    public function getProto(): LayoutProto
    {
        return $this->layout;
    }
}

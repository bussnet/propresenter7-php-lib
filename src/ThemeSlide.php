<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Slide as BaseSlide;
use Rv\Data\Template\Slide as TemplateSlide;

class ThemeSlide
{
    public function __construct(
        private readonly TemplateSlide $slide,
    ) {
    }

    public function getName(): string
    {
        return $this->slide->getName();
    }

    public function setName(string $name): self
    {
        $this->slide->setName($name);

        return $this;
    }

    public function getBaseSlide(): ?BaseSlide
    {
        return $this->slide->getBaseSlide();
    }

    public function getProto(): TemplateSlide
    {
        return $this->slide;
    }
}

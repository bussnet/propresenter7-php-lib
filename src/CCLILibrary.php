<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\ApplicationInfo;
use Rv\Data\CCLIDocument;
use Rv\Data\Template\Slide;

class CCLILibrary
{
    public function __construct(
        private readonly CCLIDocument $document,
    ) {
    }

    public function count(): int
    {
        return 1;
    }

    public function isCCLIDisplayEnabled(): bool
    {
        return $this->document->getEnableCcliDisplay();
    }

    public function setCCLIDisplayEnabled(bool $enabled): self
    {
        $this->document->setEnableCcliDisplay($enabled);

        return $this;
    }

    public function getCCLILicense(): string
    {
        return $this->document->getCcliLicense();
    }

    public function setCCLILicense(string $license): self
    {
        $this->document->setCcliLicense($license);

        return $this;
    }

    public function getDisplayType(): int
    {
        return $this->document->getDisplayType();
    }

    public function setDisplayType(int $displayType): self
    {
        $this->document->setDisplayType($displayType);

        return $this;
    }

    public function getTemplate(): ?Slide
    {
        if (!$this->document->hasTemplate()) {
            return null;
        }

        return $this->document->getTemplate();
    }

    public function setTemplate(?Slide $template): self
    {
        if ($template === null) {
            $this->document->clearTemplate();

            return $this;
        }

        $this->document->setTemplate($template);

        return $this;
    }

    public function getApplicationInfo(): ?ApplicationInfo
    {
        return $this->document->getApplicationInfo();
    }

    public function setApplicationInfo(?ApplicationInfo $applicationInfo): self
    {
        if ($applicationInfo === null) {
            $this->document->clearApplicationInfo();

            return $this;
        }

        $this->document->setApplicationInfo($applicationInfo);

        return $this;
    }

    public function getDocument(): CCLIDocument
    {
        return $this->document;
    }
}

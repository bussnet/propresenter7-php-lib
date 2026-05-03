<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Color;
use Rv\Data\MacrosDocument\Macro as MacroProto;
use Rv\Data\UUID;

class Macro
{
    public function __construct(
        private readonly MacroProto $macro,
    ) {
    }

    public function getUuid(): string
    {
        return $this->macro->getUuid()?->getString() ?? '';
    }

    public function setUuid(string $uuid): self
    {
        $proto = new UUID();
        $proto->setString($uuid);
        $this->macro->setUuid($proto);

        return $this;
    }

    public function getName(): string
    {
        return $this->macro->getName();
    }

    public function setName(string $name): self
    {
        $this->macro->setName($name);

        return $this;
    }

    /**
     * @return array{r: float, g: float, b: float, a: float}|null
     */
    public function getColor(): ?array
    {
        if (!$this->macro->hasColor()) {
            return null;
        }

        $color = $this->macro->getColor();

        return [
            'r' => $color->getRed(),
            'g' => $color->getGreen(),
            'b' => $color->getBlue(),
            'a' => $color->getAlpha(),
        ];
    }

    /**
     * @param array{r: float, g: float, b: float, a?: float}|null $color
     */
    public function setColor(?array $color): self
    {
        if ($color === null) {
            $this->macro->clearColor();

            return $this;
        }

        $proto = new Color();
        $proto->setRed((float) $color['r']);
        $proto->setGreen((float) $color['g']);
        $proto->setBlue((float) $color['b']);
        $proto->setAlpha((float) ($color['a'] ?? 1.0));
        $this->macro->setColor($proto);

        return $this;
    }

    public function getTriggerOnStartup(): bool
    {
        return $this->macro->getTriggerOnStartup();
    }

    public function setTriggerOnStartup(bool $value): self
    {
        $this->macro->setTriggerOnStartup($value);

        return $this;
    }

    public function getActionCount(): int
    {
        return count($this->macro->getActions());
    }

    public function getImageType(): int
    {
        return $this->macro->getImageType();
    }

    public function setImageType(int $value): self
    {
        $this->macro->setImageType($value);

        return $this;
    }

    /**
     * Custom icon bytes (PNG/JPG). Empty when ProPresenter uses one of the
     * built-in `image_type` icons.
     */
    public function getImageData(): string
    {
        return $this->macro->getImageData();
    }

    public function setImageData(string $bytes): self
    {
        $this->macro->setImageData($bytes);

        return $this;
    }

    public function getProto(): MacroProto
    {
        return $this->macro;
    }
}

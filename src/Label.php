<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use Rv\Data\Action\Label as LabelProto;
use Rv\Data\Color;

class Label
{
    public function __construct(
        private readonly LabelProto $label,
    ) {
    }

    public function getName(): string
    {
        return $this->label->getText();
    }

    public function setName(string $name): self
    {
        $this->label->setText($name);

        return $this;
    }

    public function hasColor(): bool
    {
        return $this->label->hasColor();
    }

    /**
     * @return array{r: float, g: float, b: float, a: float}|null
     */
    public function getColor(): ?array
    {
        if (!$this->label->hasColor()) {
            return null;
        }

        $color = $this->label->getColor();

        return [
            'r' => $color->getRed(),
            'g' => $color->getGreen(),
            'b' => $color->getBlue(),
            'a' => $color->getAlpha(),
        ];
    }

    public function getColorHex(): ?string
    {
        $color = $this->getColor();
        if ($color === null) {
            return null;
        }

        return sprintf(
            '#%02X%02X%02X',
            (int) round(max(0.0, min(1.0, $color['r'])) * 255),
            (int) round(max(0.0, min(1.0, $color['g'])) * 255),
            (int) round(max(0.0, min(1.0, $color['b'])) * 255),
        );
    }

    /**
     * Set the label's color. Pass `null` to remove the color (the UI will
     * fall back to its default tint).
     *
     * @param array{r: float, g: float, b: float, a?: float}|null $color
     */
    public function setColor(?array $color): self
    {
        if ($color === null) {
            $this->label->clearColor();

            return $this;
        }

        $proto = new Color();
        $proto->setRed((float) $color['r']);
        $proto->setGreen((float) $color['g']);
        $proto->setBlue((float) $color['b']);
        $proto->setAlpha((float) ($color['a'] ?? 1.0));
        $this->label->setColor($proto);

        return $this;
    }

    /**
     * Convenience setter: accepts a `#RRGGBB` or `#RRGGBBAA` hex value and
     * applies it to the label. Alpha defaults to 1.0 when missing.
     */
    public function setColorHex(string $hex): self
    {
        $hex = ltrim($hex, '#');
        if (!preg_match('/^[0-9A-Fa-f]{6}([0-9A-Fa-f]{2})?$/', $hex)) {
            throw new InvalidArgumentException(sprintf('Invalid hex color: %s', $hex));
        }

        $r = hexdec(substr($hex, 0, 2)) / 255.0;
        $g = hexdec(substr($hex, 2, 2)) / 255.0;
        $b = hexdec(substr($hex, 4, 2)) / 255.0;
        $a = strlen($hex) === 8 ? hexdec(substr($hex, 6, 2)) / 255.0 : 1.0;

        return $this->setColor(['r' => $r, 'g' => $g, 'b' => $b, 'a' => $a]);
    }

    public function getProto(): LabelProto
    {
        return $this->label;
    }
}

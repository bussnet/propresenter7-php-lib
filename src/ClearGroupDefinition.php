<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\ClearGroupsDocument\ClearGroup as ClearGroupProto;
use Rv\Data\Color;
use Rv\Data\UUID;

class ClearGroupDefinition
{
    public function __construct(
        private readonly ClearGroupProto $group,
    ) {
    }

    public function getUuid(): string
    {
        return $this->group->getUuid()?->getString() ?? '';
    }

    public function setUuid(string $uuid): self
    {
        $proto = new UUID();
        $proto->setString($uuid);
        $this->group->setUuid($proto);

        return $this;
    }

    public function getName(): string
    {
        return $this->group->getName();
    }

    public function setName(string $name): self
    {
        $this->group->setName($name);

        return $this;
    }

    /**
     * @return int[]
     */
    public function getLayerTargets(): array
    {
        return iterator_to_array($this->group->getLayerTargets());
    }

    /**
     * @param int[] $targets
     */
    public function setLayerTargets(array $targets): self
    {
        $this->group->setLayerTargets($targets);

        return $this;
    }

    public function isHiddenInPreview(): bool
    {
        return $this->group->getIsHiddenInPreview();
    }

    public function setHiddenInPreview(bool $hidden): self
    {
        $this->group->setIsHiddenInPreview($hidden);

        return $this;
    }

    public function getImageData(): string
    {
        return $this->group->getImageData();
    }

    public function setImageData(string $imageData): self
    {
        $this->group->setImageData($imageData);

        return $this;
    }

    public function getImageType(): int
    {
        return $this->group->getImageType();
    }

    public function setImageType(int $imageType): self
    {
        $this->group->setImageType($imageType);

        return $this;
    }

    public function isIconTinted(): bool
    {
        return $this->group->getIsIconTinted();
    }

    public function setIconTinted(bool $tinted): self
    {
        $this->group->setIsIconTinted($tinted);

        return $this;
    }

    /**
     * @return array{r: float, g: float, b: float, a: float}|null
     */
    public function getColor(): ?array
    {
        if (!$this->group->hasIconTintColor()) {
            return null;
        }

        $color = $this->group->getIconTintColor();

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
     * @param array{r: float, g: float, b: float, a?: float}|null $color
     */
    public function setColor(?array $color): self
    {
        if ($color === null) {
            $this->group->clearIconTintColor();

            return $this;
        }

        $proto = new Color();
        $proto->setRed((float) $color['r']);
        $proto->setGreen((float) $color['g']);
        $proto->setBlue((float) $color['b']);
        $proto->setAlpha((float) ($color['a'] ?? 1.0));
        $this->group->setIconTintColor($proto);

        return $this;
    }

    /**
     * @return int[]
     */
    public function getTimelineTargets(): array
    {
        return iterator_to_array($this->group->getTimelineTargets());
    }

    /**
     * @param int[] $targets
     */
    public function setTimelineTargets(array $targets): self
    {
        $this->group->setTimelineTargets($targets);

        return $this;
    }

    public function clearsPresentationNextSlide(): bool
    {
        return $this->group->getClearPresentationNextSlide();
    }

    public function setClearPresentationNextSlide(bool $clear): self
    {
        $this->group->setClearPresentationNextSlide($clear);

        return $this;
    }

    public function getProto(): ClearGroupProto
    {
        return $this->group;
    }
}

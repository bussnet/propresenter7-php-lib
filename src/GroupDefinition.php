<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Color;
use Rv\Data\Group as GroupProto;
use Rv\Data\HotKey;
use Rv\Data\UUID;

class GroupDefinition
{
    public function __construct(
        private readonly GroupProto $group,
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
     * @return array{r: float, g: float, b: float, a: float}|null
     */
    public function getColor(): ?array
    {
        if (!$this->group->hasColor()) {
            return null;
        }

        $color = $this->group->getColor();

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
            $this->group->clearColor();

            return $this;
        }

        $proto = new Color();
        $proto->setRed((float) $color['r']);
        $proto->setGreen((float) $color['g']);
        $proto->setBlue((float) $color['b']);
        $proto->setAlpha((float) ($color['a'] ?? 1.0));
        $this->group->setColor($proto);

        return $this;
    }

    public function getHotKey(): ?HotKey
    {
        return $this->group->getHotKey();
    }

    public function getApplicationGroupName(): string
    {
        return $this->group->getApplicationGroupName();
    }

    public function getApplicationGroupUuid(): string
    {
        return $this->group->getApplicationGroupIdentifier()?->getString() ?? '';
    }

    public function getProto(): GroupProto
    {
        return $this->group;
    }
}

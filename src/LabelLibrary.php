<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Action\Label as LabelProto;
use Rv\Data\Color;
use Rv\Data\ProLabelsDocument;

class LabelLibrary
{
    /** @var Label[] */
    private array $labels = [];

    /** @var array<string, Label> */
    private array $labelsByName = [];

    /** @var array<string, Label> */
    private array $labelsByNameLower = [];

    public function __construct(
        private readonly ProLabelsDocument $document,
    ) {
        foreach ($this->document->getLabels() as $labelProto) {
            $this->register(new Label($labelProto));
        }
    }

    /**
     * @return Label[]
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    public function count(): int
    {
        return count($this->labels);
    }

    public function getLabelByName(string $name): ?Label
    {
        return $this->labelsByName[$name] ?? null;
    }

    public function findLabelByName(string $name): ?Label
    {
        return $this->labelsByNameLower[strtolower($name)] ?? null;
    }

    /**
     * Append a brand-new label to the document.
     *
     * @param array{r: float, g: float, b: float, a?: float}|null $color
     */
    public function addLabel(string $name, ?array $color = null): Label
    {
        $proto = new LabelProto();
        $proto->setText($name);
        if ($color !== null) {
            $colorProto = new Color();
            $colorProto->setRed((float) $color['r']);
            $colorProto->setGreen((float) $color['g']);
            $colorProto->setBlue((float) $color['b']);
            $colorProto->setAlpha((float) ($color['a'] ?? 1.0));
            $proto->setColor($colorProto);
        }

        $existing = iterator_to_array($this->document->getLabels());
        $existing[] = $proto;
        $this->document->setLabels($existing);

        $label = new Label($proto);
        $this->register($label);

        return $label;
    }

    /**
     * Remove a label by its current name. Returns true when something was
     * removed.
     */
    public function removeLabel(string $name): bool
    {
        $kept = [];
        $removed = false;
        foreach ($this->document->getLabels() as $proto) {
            if (!$removed && $proto->getText() === $name) {
                $removed = true;
                continue;
            }
            $kept[] = $proto;
        }

        if (!$removed) {
            return false;
        }

        $this->document->setLabels($kept);
        $this->rebuildIndex();

        return true;
    }

    public function getDocument(): ProLabelsDocument
    {
        return $this->document;
    }

    private function register(Label $label): void
    {
        $this->labels[] = $label;

        $name = $label->getName();
        if ($name === '') {
            return;
        }

        $this->labelsByName[$name] ??= $label;
        $this->labelsByNameLower[strtolower($name)] ??= $label;
    }

    private function rebuildIndex(): void
    {
        $this->labels = [];
        $this->labelsByName = [];
        $this->labelsByNameLower = [];
        foreach ($this->document->getLabels() as $proto) {
            $this->register(new Label($proto));
        }
    }
}

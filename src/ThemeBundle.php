<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Template\Document;
use Rv\Data\Template\Slide as TemplateSlide;

class ThemeBundle
{
    /** @var ThemeSlide[] */
    private array $slides = [];

    /** @var array<string, ThemeSlide> */
    private array $slidesByName = [];

    /** @var array<string, ThemeAsset> */
    private array $assetsByName = [];

    /**
     * @param ThemeAsset[] $assets
     */
    public function __construct(
        private readonly Document $document,
        array $assets = [],
    ) {
        foreach ($assets as $asset) {
            $this->assetsByName[$asset->getName()] = $asset;
        }
        $this->rebuildSlideIndex();
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    /** @return ThemeSlide[] */
    public function getSlides(): array
    {
        return $this->slides;
    }

    public function getSlideByName(string $name): ?ThemeSlide
    {
        return $this->slidesByName[$name] ?? null;
    }

    public function addSlide(ThemeSlide|TemplateSlide $slide): ThemeSlide
    {
        $proto = $slide instanceof ThemeSlide ? $slide->getProto() : $slide;
        $existing = iterator_to_array($this->document->getSlides());
        $existing[] = $proto;
        $this->document->setSlides($existing);
        $this->rebuildSlideIndex();

        return $this->slides[array_key_last($this->slides)];
    }

    public function removeSlide(string $name): bool
    {
        $kept = [];
        $removed = false;
        foreach ($this->document->getSlides() as $proto) {
            if (!$removed && $proto->getName() === $name) {
                $removed = true;
                continue;
            }
            $kept[] = $proto;
        }

        if (!$removed) {
            return false;
        }

        $this->document->setSlides($kept);
        $this->rebuildSlideIndex();

        return true;
    }

    /** @return ThemeAsset[] */
    public function getAssets(): array
    {
        return array_values($this->assetsByName);
    }

    public function getAssetByName(string $name): ?ThemeAsset
    {
        return $this->assetsByName[$name] ?? null;
    }

    public function addAsset(string $name, string $bytes): ThemeAsset
    {
        $asset = new ThemeAsset(basename($name), $bytes);
        $this->assetsByName[$asset->getName()] = $asset;

        return $asset;
    }

    public function removeAsset(string $name): bool
    {
        if (!isset($this->assetsByName[$name])) {
            return false;
        }

        unset($this->assetsByName[$name]);

        return true;
    }

    public function count(): int
    {
        return count($this->slides);
    }

    public function getAssetCount(): int
    {
        return count($this->assetsByName);
    }

    private function rebuildSlideIndex(): void
    {
        $this->slides = [];
        $this->slidesByName = [];
        foreach ($this->document->getSlides() as $proto) {
            $slide = new ThemeSlide($proto);
            $this->slides[] = $slide;
            if ($slide->getName() !== '') {
                $this->slidesByName[$slide->getName()] ??= $slide;
            }
        }
    }
}

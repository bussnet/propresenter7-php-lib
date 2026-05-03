<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Action;
use Rv\Data\Action\ActionType;
use Rv\Data\Action\MacroType;
use Rv\Data\CollectionElementType;
use Rv\Data\Cue;
use Rv\Data\UUID;

/**
 * Read wrapper around a protobuf Cue representing a slide.
 *
 * Navigates the protobuf path:
 *   Cue → actions[0] → slide → presentation → base_slide → elements[]
 *
 * Provides clean access to text elements, plain text, and translations.
 */
class Slide
{
    /** @var TextElement[]|null Cached text elements (lazy) */
    private ?array $textElements = null;

    /** @var TextElement[]|null Cached all elements (lazy) */
    private ?array $allElements = null;

    public function __construct(
        private readonly Cue $cue,
    ) {
    }

    /**
     * UUID string of this slide's cue.
     */
    public function getUuid(): string
    {
        return $this->cue->getUuid()?->getString() ?? '';
    }

    /**
     * All TextElement wrappers for elements that have text data.
     * Skips shapes, media, and other non-text elements.
     *
     * @return TextElement[]
     */
    public function getTextElements(): array
    {
        if ($this->textElements === null) {
            $this->textElements = array_values(
                array_filter(
                    $this->getAllElements(),
                    fn (TextElement $el) => $el->hasText()
                )
            );
        }

        return $this->textElements;
    }

    /**
     * All TextElement wrappers for ALL elements (including non-text).
     *
     * @return TextElement[]
     */
    public function getAllElements(): array
    {
        if ($this->allElements === null) {
            $this->allElements = [];

            foreach ($this->getSlideElements() as $slideElement) {
                $graphicsElement = $slideElement->getElement();
                if ($graphicsElement !== null) {
                    $this->allElements[] = new TextElement($graphicsElement);
                }
            }
        }

        return $this->allElements;
    }

    /**
     * Plain text from the first text element.
     */
    public function getPlainText(): string
    {
        $textElements = $this->getTextElements();
        if (empty($textElements)) {
            return '';
        }

        return $textElements[0]->getPlainText();
    }

    public function setPlainText(string $text): void
    {
        $textElements = $this->getTextElements();
        if (!isset($textElements[0])) {
            return;
        }

        $textElements[0]->setPlainText($text);
    }

    /**
     * Whether this slide has a translation (2+ text elements).
     */
    public function hasTranslation(): bool
    {
        return count($this->getTextElements()) >= 2;
    }

    /**
     * The translation TextElement (second text element), or null if none.
     */
    public function getTranslation(): ?TextElement
    {
        $textElements = $this->getTextElements();
        return $textElements[1] ?? null;
    }

    public function setTranslation(string $text): void
    {
        $textElements = $this->getTextElements();
        if (!isset($textElements[1])) {
            return;
        }

        $textElements[1]->setPlainText($text);
    }

    public function getLabel(): string
    {
        return $this->cue->getName();
    }

    public function setLabel(string $label): void
    {
        $this->cue->setName($label);
    }

    public function hasMacro(): bool
    {
        return $this->findMacroAction() !== null;
    }

    public function getMacroName(): ?string
    {
        $macro = $this->findMacroAction();
        return $macro?->getMacro()?->getIdentification()?->getParameterName();
    }

    public function getMacroUuid(): ?string
    {
        $macro = $this->findMacroAction();
        return $macro?->getMacro()?->getIdentification()?->getParameterUuid()?->getString();
    }

    public function getMacroCollectionName(): ?string
    {
        $macro = $this->findMacroAction();
        return $macro?->getMacro()?->getIdentification()?->getParentCollection()?->getParameterName();
    }

    public function getMacroCollectionUuid(): ?string
    {
        $macro = $this->findMacroAction();
        return $macro?->getMacro()?->getIdentification()?->getParentCollection()?->getParameterUuid()?->getString();
    }

    public function setMacro(string $name, string $uuid, string $collectionName = '--MAIN--', string $collectionUuid = ''): void
    {
        $parentCollectionUuid = new UUID();
        $parentCollectionUuid->setString($collectionUuid);

        $parentCollection = new CollectionElementType();
        $parentCollection->setParameterName($collectionName);
        $parentCollection->setParameterUuid($parentCollectionUuid);

        $macroUuid = new UUID();
        $macroUuid->setString($uuid);

        $identification = new CollectionElementType();
        $identification->setParameterName($name);
        $identification->setParameterUuid($macroUuid);
        $identification->setParentCollection($parentCollection);

        $macroType = new MacroType();
        $macroType->setIdentification($identification);

        $existingMacroAction = $this->findMacroAction();
        if ($existingMacroAction !== null) {
            $existingMacroAction->setType(ActionType::ACTION_TYPE_MACRO);
            $existingMacroAction->setMacro($macroType);
            $existingMacroAction->setIsEnabled(true);
            return;
        }

        $macroAction = new Action();
        $macroAction->setUuid(new UUID());
        $macroAction->setType(ActionType::ACTION_TYPE_MACRO);
        $macroAction->setMacro($macroType);
        $macroAction->setIsEnabled(true);

        $actions = [];
        foreach ($this->cue->getActions() as $action) {
            $actions[] = $action;
        }
        $actions[] = $macroAction;
        $this->cue->setActions($actions);
    }

    public function removeMacro(): void
    {
        $filteredActions = [];
        foreach ($this->cue->getActions() as $action) {
            if ($action->getType() !== ActionType::ACTION_TYPE_MACRO) {
                $filteredActions[] = $action;
            }
        }

        $this->cue->setActions($filteredActions);
    }

    public function hasMedia(): bool
    {
        return $this->findMediaAction() !== null;
    }

    public function getMediaUrl(): ?string
    {
        $media = $this->findMediaAction();
        return $media?->getMedia()?->getElement()?->getUrl()?->getAbsoluteString();
    }

    public function getMediaUuid(): ?string
    {
        $media = $this->findMediaAction();
        return $media?->getMedia()?->getElement()?->getUuid()?->getString();
    }

    public function getMediaFormat(): ?string
    {
        $media = $this->findMediaAction();
        return $media?->getMedia()?->getElement()?->getMetadata()?->getFormat();
    }

    /**
     * Access the underlying protobuf Cue.
     */
    public function getCue(): Cue
    {
        return $this->cue;
    }

    /**
     * Navigate the protobuf path to get Slide\Element[] from the Cue.
     *
     * Path: Cue → actions[0] → getSlide() → getPresentation() → getBaseSlide() → getElements()
     *
     * @return \Rv\Data\Slide\Element[]|\Google\Protobuf\Internal\RepeatedField
     */
    private function getSlideElements(): iterable
    {
        $firstAction = null;
        foreach ($this->cue->getActions() as $action) {
            $firstAction = $action;
            break;
        }

        if ($firstAction === null) {
            return [];
        }

        $slideType = $firstAction->getSlide();
        if ($slideType === null) {
            return [];
        }

        $presentationSlide = $slideType->getPresentation();
        if ($presentationSlide === null) {
            return [];
        }

        $baseSlide = $presentationSlide->getBaseSlide();
        if ($baseSlide === null) {
            return [];
        }

        return $baseSlide->getElements();
    }

    private function findMacroAction(): ?Action
    {
        foreach ($this->cue->getActions() as $action) {
            if ($action->getType() === ActionType::ACTION_TYPE_MACRO) {
                return $action;
            }
        }

        return null;
    }

    private function findMediaAction(): ?Action
    {
        foreach ($this->cue->getActions() as $action) {
            if ($action->getType() === ActionType::ACTION_TYPE_MEDIA) {
                return $action;
            }
        }

        return null;
    }
}

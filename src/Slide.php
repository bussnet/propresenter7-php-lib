<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Action;
use Rv\Data\Action\ActionType;
use Rv\Data\Action\LayerType;
use Rv\Data\Action\MacroType;
use Rv\Data\CollectionElementType;
use Rv\Data\Cue;
use Rv\Data\Graphics\Element as GraphicsElement;
use Rv\Data\Graphics\Text\VerticalAlignment;
use Rv\Data\Media;
use Rv\Data\Slide\Element\DataLink\ClockText;
use Rv\Data\Slide\Element\DataLink\TimerText;
use Rv\Data\Slide\Element\DataLink\VisibilityLink\Condition\TimerVisibility;
use Rv\Data\Slide\Element\DataLink\VisibilityLink\Condition\TimerVisibility\TimerVisibilityCriterion;
use Rv\Data\Timer\Format as TimerFormat;
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
        if (! isset($textElements[0])) {
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
        if (! isset($textElements[1])) {
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

    public function hasBackgroundMedia(): bool
    {
        return $this->findBackgroundMediaAction() !== null;
    }

    public function getBackgroundMediaUrl(): ?string
    {
        $media = $this->findBackgroundMediaAction();

        return $media?->getMedia()?->getElement()?->getUrl()?->getAbsoluteString();
    }

    public function getBackgroundMediaFormat(): ?string
    {
        $media = $this->findBackgroundMediaAction();

        return $media?->getMedia()?->getElement()?->getMetadata()?->getFormat();
    }

    /**
     * Whether this slide carries an IMAGE CONTENT ELEMENT (a slide element whose
     * fill is an image), as generated from the `image` slideData key. This is
     * independent of the background media ACTION.
     */
    public function hasImageElement(): bool
    {
        return $this->findImageElementMedia() !== null;
    }

    /**
     * Bundle-relative URL of the first image content element, or null.
     */
    public function getImageElementUrl(): ?string
    {
        return $this->findImageElementMedia()?->getUrl()?->getAbsoluteString();
    }

    /**
     * Media format (e.g. "JPG") of the first image content element, or null.
     */
    public function getImageElementFormat(): ?string
    {
        return $this->findImageElementMedia()?->getMetadata()?->getFormat();
    }

    /**
     * Bounds of the first plain TEXT element (the element carrying the slide
     * text, not a clock/timer/image element), or null when the slide has none.
     *
     * @return array{x: float, y: float, width: float, height: float}|null
     */
    public function getTextElementBounds(): ?array
    {
        $bounds = $this->findPlainTextGraphicsElement()?->getBounds();

        if ($bounds === null) {
            return null;
        }

        return [
            'x' => $bounds->getOrigin()?->getX() ?? 0.0,
            'y' => $bounds->getOrigin()?->getY() ?? 0.0,
            'width' => $bounds->getSize()?->getWidth() ?? 0.0,
            'height' => $bounds->getSize()?->getHeight() ?? 0.0,
        ];
    }

    /**
     * Horizontal alignment ("left"|"center"|"right") of the first plain TEXT
     * element, read back from its RTF paragraph control word.
     */
    public function getTextElementAlign(): ?string
    {
        $rtf = $this->findPlainTextGraphicsElement()?->getText()?->getRtfData();

        if ($rtf === null || $rtf === '') {
            return null;
        }

        if (str_contains($rtf, '\ql')) {
            return 'left';
        }

        if (str_contains($rtf, '\qr')) {
            return 'right';
        }

        return 'center';
    }

    /**
     * Vertical alignment ("top"|"middle"|"bottom") of the first plain TEXT
     * element.
     */
    public function getTextElementVerticalAlign(): ?string
    {
        $text = $this->findPlainTextGraphicsElement()?->getText();

        if ($text === null) {
            return null;
        }

        return match ($text->getVerticalAlignment()) {
            VerticalAlignment::VERTICAL_ALIGNMENT_TOP => 'top',
            VerticalAlignment::VERTICAL_ALIGNMENT_BOTTOM => 'bottom',
            default => 'middle',
        };
    }

    /**
     * First graphics element that carries text and is neither a clock/timer
     * DataLink element nor an image element.
     */
    private function findPlainTextGraphicsElement(): ?GraphicsElement
    {
        foreach ($this->getSlideElements() as $slideElement) {
            $graphicsElement = $slideElement->getElement();

            if ($graphicsElement === null) {
                continue;
            }

            if (($graphicsElement->getText()?->getRtfData() ?? '') === '') {
                continue;
            }

            if ($graphicsElement->getFill()?->getMedia() !== null) {
                continue;
            }

            if (count($slideElement->getDataLinks()) > 0) {
                continue;
            }

            return $graphicsElement;
        }

        return null;
    }

    /**
     * Whether this slide carries a live wall-clock DataLink element.
     */
    public function hasClock(): bool
    {
        return $this->findClockText() !== null;
    }

    /**
     * Raw ClockText::clock_format_string of the first clock element, or null.
     * In real ProPresenter files this is the verbatim RTF body template token
     * `${clock}` — NOT a time format pattern.
     */
    public function getClockFormat(): ?string
    {
        return $this->findClockText()?->getClockFormatString();
    }

    /**
     * Whether this slide carries a timer/countdown DataLink element.
     */
    public function hasTimer(): bool
    {
        return $this->findTimerText() !== null;
    }

    /**
     * Raw TimerText::timer_format_string of the first timer element, or null.
     * In real ProPresenter files this is the verbatim RTF body template token
     * `${timer}` — NOT a time format pattern. The real format lives in the
     * structured Timer\Format message, see getTimerFormatMessage().
     */
    public function getTimerFormat(): ?string
    {
        return $this->findTimerText()?->getTimerFormatString();
    }

    /**
     * Structured Timer\Format message of the first timer element, or null. This
     * is the message ProPresenter actually renders the countdown from.
     */
    public function getTimerFormatMessage(): ?TimerFormat
    {
        return $this->findTimerText()?->getTimerFormat();
    }

    /**
     * Name of the timer bound to the first timer element, or null.
     */
    public function getTimerName(): ?string
    {
        return $this->findTimerText()?->getTimerName();
    }

    /**
     * UUID of the timer bound to the first timer element, or null.
     */
    public function getTimerUuid(): ?string
    {
        return $this->findTimerText()?->getTimerUuid()?->getString();
    }

    /**
     * Text colour of the first text element as an [r, g, b] triple with 0..255
     * components, or null when the slide carries no readable text colour.
     *
     * @return array{int, int, int}|null
     */
    public function getTextColor(): ?array
    {
        return ($this->getTextElements()[0] ?? null)?->getTextColor();
    }

    /**
     * Text colour of the first timer element as an [r, g, b] triple with 0..255
     * components, or null when the slide carries no timer element.
     *
     * @return array{int, int, int}|null
     */
    public function getTimerColor(): ?array
    {
        return $this->getTimerElement()?->getTextColor();
    }

    /**
     * Text outline (Kontur) of the first text element, or null when the slide
     * carries no outlined text.
     *
     * @return array{color: array{int, int, int}, width: float}|null
     */
    public function getTextOutline(): ?array
    {
        return ($this->getTextElements()[0] ?? null)?->getOutline();
    }

    /**
     * Text outline (Kontur) of the first timer element, or null.
     *
     * @return array{color: array{int, int, int}, width: float}|null
     */
    public function getTimerOutline(): ?array
    {
        return $this->getTimerElement()?->getOutline();
    }

    /**
     * The first timer-bound element of this slide as a TextElement wrapper, or
     * null when the slide carries no timer. Gives callers access to the timer's
     * raw RTF (font table, colour tables) without exposing the protobuf.
     */
    public function getTimerElement(): ?TextElement
    {
        foreach ($this->getSlideElements() as $slideElement) {
            foreach ($slideElement->getDataLinks() as $dataLink) {
                if ($dataLink->getTimerText() === null) {
                    continue;
                }

                $graphicsElement = $slideElement->getElement();
                if ($graphicsElement === null) {
                    continue;
                }

                return new TextElement($graphicsElement);
            }
        }

        return null;
    }

    /**
     * Whether this slide carries a timer-bound VisibilityLink DataLink, i.e. an
     * element whose visibility depends on the state of a ProPresenter timer.
     */
    public function hasTimerVisibilityCondition(): bool
    {
        return $this->findTimerVisibility() !== null;
    }

    /**
     * Visibility criterion of the first timer visibility condition as a readable
     * string ('hasTimeRemaining' | 'hasExpired' | 'isRunning' | 'notRunning'),
     * or null when the slide carries no such condition.
     */
    public function getTimerVisibilityCriterion(): ?string
    {
        $timerVisibility = $this->findTimerVisibility();

        if ($timerVisibility === null) {
            return null;
        }

        return match ($timerVisibility->getVisibilityCriterion()) {
            TimerVisibilityCriterion::TIMER_VISIBILITY_CRITERION_HAS_TIME_REMAINING => 'hasTimeRemaining',
            TimerVisibilityCriterion::TIMER_VISIBILITY_CRITERION_HAS_EXPIRED => 'hasExpired',
            TimerVisibilityCriterion::TIMER_VISIBILITY_CRITERION_IS_RUNNING => 'isRunning',
            TimerVisibilityCriterion::TIMER_VISIBILITY_CRITERION_NOT_RUNNING => 'notRunning',
            default => null,
        };
    }

    /**
     * UUID of the timer the first visibility condition is bound to, or null.
     */
    public function getTimerVisibilityTimerUuid(): ?string
    {
        return $this->findTimerVisibility()?->getTimerUuid()?->getString();
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
        return $this->findMediaActionByLayerType(LayerType::LAYER_TYPE_FOREGROUND);
    }

    private function findBackgroundMediaAction(): ?Action
    {
        return $this->findMediaActionByLayerType(LayerType::LAYER_TYPE_BACKGROUND);
    }

    private function findMediaActionByLayerType(int $layerType): ?Action
    {
        foreach ($this->cue->getActions() as $action) {
            if ($action->getType() === ActionType::ACTION_TYPE_MEDIA && $action->getMedia()?->getLayerType() === $layerType) {
                return $action;
            }
        }

        return null;
    }

    /**
     * First slide element whose graphics fill carries image media, or null.
     */
    private function findImageElementMedia(): ?Media
    {
        foreach ($this->getSlideElements() as $slideElement) {
            $media = $slideElement->getElement()?->getFill()?->getMedia();
            if ($media !== null && $media->hasImage()) {
                return $media;
            }
        }

        return null;
    }

    /**
     * First ClockText DataLink across all slide elements, or null.
     */
    private function findClockText(): ?ClockText
    {
        foreach ($this->getSlideElements() as $slideElement) {
            foreach ($slideElement->getDataLinks() as $dataLink) {
                $clockText = $dataLink->getClockText();
                if ($clockText !== null) {
                    return $clockText;
                }
            }
        }

        return null;
    }

    /**
     * First TimerText DataLink across all slide elements, or null.
     */
    private function findTimerText(): ?TimerText
    {
        foreach ($this->getSlideElements() as $slideElement) {
            foreach ($slideElement->getDataLinks() as $dataLink) {
                $timerText = $dataLink->getTimerText();
                if ($timerText !== null) {
                    return $timerText;
                }
            }
        }

        return null;
    }

    /**
     * First timer-bound VisibilityLink condition across all slide elements.
     */
    private function findTimerVisibility(): ?TimerVisibility
    {
        foreach ($this->getSlideElements() as $slideElement) {
            foreach ($slideElement->getDataLinks() as $dataLink) {
                $visibilityLink = $dataLink->getVisibilityLink();
                if ($visibilityLink === null) {
                    continue;
                }

                foreach ($visibilityLink->getConditions() as $condition) {
                    $timerVisibility = $condition->getTimerVisibility();
                    if ($timerVisibility !== null) {
                        return $timerVisibility;
                    }
                }
            }
        }

        return null;
    }
}

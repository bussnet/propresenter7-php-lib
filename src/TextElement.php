<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Graphics\Element as GraphicsElement;
use Rv\Data\Graphics\Text;

/**
 * Read wrapper around a Graphics\Element that contains text.
 *
 * Provides clean OOP access to element name and RTF text data.
 * Wraps the protobuf Graphics\Element without modifying its structure.
 */
class TextElement
{
    public function __construct(
        private readonly GraphicsElement $element,
    ) {
    }

    /**
     * User-defined element name (e.g. "Orginal", "Deutsch").
     */
    public function getName(): string
    {
        return $this->element->getName();
    }

    /**
     * Whether this element contains text data.
     */
    public function hasText(): bool
    {
        return $this->element->hasText();
    }

    /**
     * Raw RTF data from the text field.
     */
    public function getRtfData(): string
    {
        if (! $this->element->hasText()) {
            return '';
        }

        return $this->element->getText()->getRtfData();
    }

    /**
     * Set RTF data on the underlying protobuf text field.
     * Creates the Text object if it doesn't exist.
     */
    public function setRtfData(string $rtfData): void
    {
        $text = $this->element->getText();
        if ($text === null) {
            $text = new Text();
            $this->element->setText($text);
        }

        $text->setRtfData($rtfData);
    }

    /**
     * Extract plain text from RTF using RtfExtractor.
     */
    public function getPlainText(): string
    {
        $rtf = $this->getRtfData();
        if ($rtf === '') {
            return '';
        }

        return RtfExtractor::toPlainText($rtf);
    }

    public function setPlainText(string $text): void
    {
        $rtf = $this->getRtfData();
        if ($rtf === '') {
            return;
        }

        $marker = '\\CocoaLigature0 ';
        $start = strrpos($rtf, $marker);
        if ($start === false) {
            return;
        }

        $textStart = $start + strlen($marker);
        $textEnd = strrpos($rtf, '}');
        if ($textEnd === false || $textEnd < $textStart) {
            return;
        }

        $encodedText = self::encodePlainTextForRtf($text);
        $updatedRtf = substr($rtf, 0, $textStart).$encodedText.substr($rtf, $textEnd);
        $this->setRtfData($updatedRtf);
    }

    /**
     * The text colour of this element as an [r, g, b] triple with 0..255
     * components, read from the second RTF colour table entry (the one the
     * body references via \cf2). Returns null when the element carries no RTF
     * or the colour table cannot be parsed.
     *
     * @return array{int, int, int}|null
     */
    public function getTextColor(): ?array
    {
        $rtf = $this->getRtfData();
        if ($rtf === '') {
            return null;
        }

        if (preg_match('/\{\\\\colortbl;(.*?)\}/s', $rtf, $tableMatch) !== 1) {
            return null;
        }

        $entries = array_values(array_filter(explode(';', $tableMatch[1]), static fn (string $entry): bool => trim($entry) !== ''));

        // Entry 0 is \cf1, entry 1 is \cf2 — the one the body uses.
        $entry = $entries[1] ?? null;
        if ($entry === null) {
            return null;
        }

        if (preg_match('/\\\\red(\d+)\\\\green(\d+)\\\\blue(\d+)/', $entry, $colorMatch) !== 1) {
            return null;
        }

        return [(int) $colorMatch[1], (int) $colorMatch[2], (int) $colorMatch[3]];
    }

    /**
     * Access the underlying protobuf Graphics\Element.
     */
    public function getGraphicsElement(): GraphicsElement
    {
        return $this->element;
    }

    private static function encodePlainTextForRtf(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = strtr($text, [
            'ü' => "\\'fc",
            'ö' => "\\'f6",
            'ä' => "\\'e4",
            'ß' => "\\'df",
            'Ü' => "\\'dc",
            'Ö' => "\\'d6",
            'Ä' => "\\'c4",
        ]);

        return str_replace("\n", "\\\n", $text);
    }
}

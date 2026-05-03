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
        if (!$this->element->hasText()) {
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
        $updatedRtf = substr($rtf, 0, $textStart) . $encodedText . substr($rtf, $textEnd);
        $this->setRtfData($updatedRtf);
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

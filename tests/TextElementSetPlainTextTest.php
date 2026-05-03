<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\TextElement;
use Rv\Data\Graphics\Element as GraphicsElement;
use Rv\Data\Graphics\Text;

class TextElementSetPlainTextTest extends TestCase
{
    private const SAMPLE_RTF = '{\rtf1\ansi\ansicpg1252\cocoartf2761\cocoasubrtf580{\fonttbl\f0\fswiss\fcharset0 Helvetica;}{\colortbl;\red255\green255\blue255;}{\*\expandedcolortbl;;\cssrgb\c100000\c100000\c100000;}\pard\tx560\tx1120\tx1680\tx2240\tx2800\tx3360\tx3920\tx4480\tx5040\tx5600\tx6160\tx6720\pardirnatural\qc\partightenfactor0\f0\fs228 \cf1 \CocoaLigature0 Initial Text}';

    #[Test]
    public function setPlainTextReplacesOnlyTextPortionAndPreservesFormatting(): void
    {
        $textElement = new TextElement($this->makeGraphicsElementWithRtf(self::SAMPLE_RTF));

        $textElement->setPlainText('Updated Text');

        $updatedRtf = $textElement->getRtfData();
        $marker = '\\CocoaLigature0 ';
        $textStart = strrpos($updatedRtf, $marker);

        $this->assertNotFalse($textStart);
        $this->assertStringStartsWith(substr(self::SAMPLE_RTF, 0, $textStart + strlen($marker)), $updatedRtf);
        $this->assertStringEndsWith('}', $updatedRtf);
        $this->assertStringContainsString('Updated Text', $updatedRtf);
        $this->assertStringNotContainsString('Initial Text', $updatedRtf);
    }

    #[Test]
    public function setPlainTextConvertsNewlineToRtfSoftReturn(): void
    {
        $textElement = new TextElement($this->makeGraphicsElementWithRtf(self::SAMPLE_RTF));

        $textElement->setPlainText("Line 1\nLine 2");

        $updatedRtf = $textElement->getRtfData();
        $this->assertStringContainsString("Line 1\\\nLine 2", $updatedRtf);
        $this->assertSame("Line 1\nLine 2", $textElement->getPlainText());
    }

    #[Test]
    public function setPlainTextEncodesGermanSpecialCharactersAsRtfHexEscapes(): void
    {
        $textElement = new TextElement($this->makeGraphicsElementWithRtf(self::SAMPLE_RTF));

        $textElement->setPlainText('üöäßÜÖÄ');

        $updatedRtf = $textElement->getRtfData();
        $this->assertStringContainsString("\\'fc", $updatedRtf);
        $this->assertStringContainsString("\\'f6", $updatedRtf);
        $this->assertStringContainsString("\\'e4", $updatedRtf);
        $this->assertStringContainsString("\\'df", $updatedRtf);
        $this->assertStringContainsString("\\'dc", $updatedRtf);
        $this->assertStringContainsString("\\'d6", $updatedRtf);
        $this->assertStringContainsString("\\'c4", $updatedRtf);
        $this->assertSame('üöäßÜÖÄ', $textElement->getPlainText());
    }

    #[Test]
    public function setPlainTextIsSafeWhenElementHasNoText(): void
    {
        $graphicsElement = new GraphicsElement();
        $graphicsElement->setName('Shape');
        $textElement = new TextElement($graphicsElement);

        $textElement->setPlainText('Should do nothing');

        $this->assertFalse($textElement->hasText());
        $this->assertSame('', $textElement->getRtfData());
        $this->assertSame('', $textElement->getPlainText());
    }

    private function makeGraphicsElementWithRtf(string $rtf): GraphicsElement
    {
        $graphicsElement = new GraphicsElement();
        $graphicsElement->setName('Orginal');

        $text = new Text();
        $text->setRtfData($rtf);
        $graphicsElement->setText($text);

        return $graphicsElement;
    }
}

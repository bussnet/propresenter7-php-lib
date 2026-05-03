<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ProPresenter\Parser\TextElement;
use Rv\Data\Graphics\Element as GraphicsElement;
use Rv\Data\Graphics\Text;

class TextElementTest extends TestCase
{
    private function makeGraphicsElement(string $name, ?string $rtfData = null): GraphicsElement
    {
        $element = new GraphicsElement();
        $element->setName($name);

        if ($rtfData !== null) {
            $text = new Text();
            $text->setRtfData($rtfData);
            $element->setText($text);
        }

        return $element;
    }

    // ─── getName() ───

    #[Test]
    public function getNameReturnsElementName(): void
    {
        $graphicsElement = $this->makeGraphicsElement('Orginal');
        $textElement = new TextElement($graphicsElement);

        $this->assertSame('Orginal', $textElement->getName());
    }

    #[Test]
    public function getNameReturnsDeutschForTranslationElement(): void
    {
        $graphicsElement = $this->makeGraphicsElement('Deutsch');
        $textElement = new TextElement($graphicsElement);

        $this->assertSame('Deutsch', $textElement->getName());
    }

    // ─── getRtfData() ───

    #[Test]
    public function getRtfDataReturnsRawRtfString(): void
    {
        $rtf = '{\rtf1\ansi test}';
        $graphicsElement = $this->makeGraphicsElement('Orginal', $rtf);
        $textElement = new TextElement($graphicsElement);

        $this->assertSame($rtf, $textElement->getRtfData());
    }

    #[Test]
    public function getRtfDataReturnsEmptyStringWhenNoText(): void
    {
        $graphicsElement = $this->makeGraphicsElement('Shape');
        $textElement = new TextElement($graphicsElement);

        $this->assertSame('', $textElement->getRtfData());
    }

    // ─── setRtfData() ───

    #[Test]
    public function setRtfDataUpdatesUnderlyingProtobuf(): void
    {
        $graphicsElement = $this->makeGraphicsElement('Orginal', '{\rtf1 old}');
        $textElement = new TextElement($graphicsElement);

        $textElement->setRtfData('{\rtf1 new}');

        $this->assertSame('{\rtf1 new}', $textElement->getRtfData());
        // Verify it wrote through to the protobuf
        $this->assertSame('{\rtf1 new}', $graphicsElement->getText()->getRtfData());
    }

    // ─── getPlainText() ───

    #[Test]
    public function getPlainTextExtractsFromRtf(): void
    {
        $rtf = '{\rtf1\ansi\ansicpg1252\cocoartf2761' . "\n"
             . '\cocoatextscaling0\cocoaplatform0{\fonttbl\f0\fnil\fcharset0 HelveticaNeue;}' . "\n"
             . '{\colortbl;\red255\green255\blue255;\red255\green255\blue255;}' . "\n"
             . '{\*\expandedcolortbl;;\csgray\c100000;}' . "\n"
             . '\deftab1680' . "\n"
             . '\pard\pardeftab1680\pardirnatural\qc\partightenfactor0' . "\n"
             . "\n"
             . '\f0\fs84 \cf2 \CocoaLigature0 Vers1.1\\' . "\n"
             . 'Vers1.2}';

        $graphicsElement = $this->makeGraphicsElement('Orginal', $rtf);
        $textElement = new TextElement($graphicsElement);

        $this->assertSame("Vers1.1\nVers1.2", $textElement->getPlainText());
    }

    #[Test]
    public function getPlainTextReturnsEmptyStringWhenNoText(): void
    {
        $graphicsElement = $this->makeGraphicsElement('Shape');
        $textElement = new TextElement($graphicsElement);

        $this->assertSame('', $textElement->getPlainText());
    }

    // ─── hasText() ───

    #[Test]
    public function hasTextReturnsTrueWhenTextExists(): void
    {
        $graphicsElement = $this->makeGraphicsElement('Orginal', '{\rtf1 hello}');
        $textElement = new TextElement($graphicsElement);

        $this->assertTrue($textElement->hasText());
    }

    #[Test]
    public function hasTextReturnsFalseWhenNoText(): void
    {
        $graphicsElement = $this->makeGraphicsElement('Shape');
        $textElement = new TextElement($graphicsElement);

        $this->assertFalse($textElement->hasText());
    }

    // ─── Integration: Real Test.pro data ───

    #[Test]
    public function integrationExtractsVerse1FromTestPro(): void
    {
        $presentation = self::loadPresentation();
        $cues = $presentation->getCues();

        // Find Verse 1 slide by UUID 5A6AF946-30B0-4F40-BE7A-C6429C32868A
        $verse1Cue = null;
        foreach ($cues as $cue) {
            if ($cue->getUuid()?->getString() === '5A6AF946-30B0-4F40-BE7A-C6429C32868A') {
                $verse1Cue = $cue;
                break;
            }
        }
        $this->assertNotNull($verse1Cue, 'Verse 1 cue not found in Test.pro');

        // Navigate: Cue → actions[0] → slide → presentation → base_slide → elements
        $slideType = $verse1Cue->getActions()[0]->getSlide();
        $this->assertNotNull($slideType, 'SlideType not found');

        $presentationSlide = $slideType->getPresentation();
        $this->assertNotNull($presentationSlide, 'PresentationSlide not found');

        $baseSlide = $presentationSlide->getBaseSlide();
        $this->assertNotNull($baseSlide, 'BaseSlide not found');

        $elements = $baseSlide->getElements();
        $this->assertGreaterThan(0, count($elements), 'No elements in slide');

        // First element with text should contain "Vers1.1\nVers1.2"
        $graphicsElement = $elements[0]->getElement();
        $this->assertNotNull($graphicsElement);
        $this->assertTrue($graphicsElement->hasText());

        $textElement = new TextElement($graphicsElement);
        $this->assertSame("Vers1.1\nVers1.2", $textElement->getPlainText());
    }

    private static function loadPresentation(): \Rv\Data\Presentation
    {
        $path = dirname(__DIR__) . '/doc/reference_samples/Test.pro';
        if (!file_exists($path)) {
            self::markTestSkipped("Test.pro not found at: $path");
        }

        $data = file_get_contents($path);
        $presentation = new \Rv\Data\Presentation();
        $presentation->mergeFromString($data);

        return $presentation;
    }
}

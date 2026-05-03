<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ProPresenter\Parser\Slide;
use Rv\Data\Cue;
use Rv\Data\UUID;
use Rv\Data\Action;
use Rv\Data\Action\SlideType;
use Rv\Data\PresentationSlide;
use Rv\Data\Slide as ProtoSlide;
use Rv\Data\Slide\Element as SlideElement;
use Rv\Data\Graphics\Element as GraphicsElement;
use Rv\Data\Graphics\Text;

class SlideTest extends TestCase
{
    /**
     * Build a Cue with one SlideType action containing given graphics elements.
     *
     * @param string $uuid
     * @param GraphicsElement[] $graphicsElements
     */
    private function makeCue(string $uuid, array $graphicsElements): Cue
    {
        $slideElements = [];
        foreach ($graphicsElements as $ge) {
            $se = new SlideElement();
            $se->setElement($ge);
            $slideElements[] = $se;
        }

        $baseSlide = new ProtoSlide();
        $baseSlide->setElements($slideElements);

        $presentationSlide = new PresentationSlide();
        $presentationSlide->setBaseSlide($baseSlide);

        $slideType = new SlideType();
        $slideType->setPresentation($presentationSlide);

        $action = new Action();
        $action->setSlide($slideType);

        $cue = new Cue();
        $cueUuid = new UUID();
        $cueUuid->setString($uuid);
        $cue->setUuid($cueUuid);
        $cue->setActions([$action]);

        return $cue;
    }

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

    // ─── getUuid() ───

    #[Test]
    public function getUuidReturnsCueUuidString(): void
    {
        $cue = $this->makeCue('ABC-123', [
            $this->makeGraphicsElement('Text', '{\rtf1 hello}'),
        ]);
        $slide = new Slide($cue);

        $this->assertSame('ABC-123', $slide->getUuid());
    }

    // ─── getTextElements() ───

    #[Test]
    public function getTextElementsReturnsOnlyElementsWithText(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Text', '{\rtf1 hello}'),
            $this->makeGraphicsElement('Shape'),  // no text
            $this->makeGraphicsElement('Translation', '{\rtf1 world}'),
        ]);
        $slide = new Slide($cue);

        $elements = $slide->getTextElements();
        $this->assertCount(2, $elements);
        $this->assertSame('Text', $elements[0]->getName());
        $this->assertSame('Translation', $elements[1]->getName());
    }

    #[Test]
    public function getTextElementsReturnsEmptyArrayWhenNoTextElements(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Shape'),
        ]);
        $slide = new Slide($cue);

        $this->assertCount(0, $slide->getTextElements());
    }

    // ─── getAllElements() ───

    #[Test]
    public function getAllElementsReturnsAllElementsIncludingNonText(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Text', '{\rtf1 hello}'),
            $this->makeGraphicsElement('Shape'),
        ]);
        $slide = new Slide($cue);

        $elements = $slide->getAllElements();
        $this->assertCount(2, $elements);
    }

    // ─── getPlainText() ───

    #[Test]
    public function getPlainTextReturnsFirstTextElementContent(): void
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

        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Orginal', $rtf),
        ]);
        $slide = new Slide($cue);

        $this->assertSame("Vers1.1\nVers1.2", $slide->getPlainText());
    }

    #[Test]
    public function getPlainTextReturnsEmptyStringWhenNoTextElements(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Shape'),
        ]);
        $slide = new Slide($cue);

        $this->assertSame('', $slide->getPlainText());
    }

    #[Test]
    public function setPlainTextUpdatesFirstTextElement(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Orginal', '{\rtf1\ansi\ansicpg1252\cocoartf2761\f0 \CocoaLigature0 Original}'),
            $this->makeGraphicsElement('Deutsch', '{\rtf1\ansi\ansicpg1252\cocoartf2761\f0 \CocoaLigature0 Translation}'),
        ]);
        $slide = new Slide($cue);

        $slide->setPlainText('Updated Original');

        $this->assertSame('Updated Original', $slide->getTextElements()[0]->getPlainText());
        $this->assertSame('Translation', $slide->getTextElements()[1]->getPlainText());
    }

    #[Test]
    public function setTranslationUpdatesSecondTextElementWhenPresent(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Orginal', '{\rtf1\ansi\ansicpg1252\cocoartf2761\f0 \CocoaLigature0 Original}'),
            $this->makeGraphicsElement('Deutsch', '{\rtf1\ansi\ansicpg1252\cocoartf2761\f0 \CocoaLigature0 Translation}'),
        ]);
        $slide = new Slide($cue);

        $slide->setTranslation('Neue Uebersetzung');

        $this->assertSame('Original', $slide->getTextElements()[0]->getPlainText());
        $this->assertSame('Neue Uebersetzung', $slide->getTextElements()[1]->getPlainText());
    }

    #[Test]
    public function setTranslationDoesNothingWhenNoSecondTextElementExists(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Orginal', '{\rtf1\ansi\ansicpg1252\cocoartf2761\f0 \CocoaLigature0 Original}'),
        ]);
        $slide = new Slide($cue);

        $slide->setTranslation('Ignored');

        $this->assertSame('Original', $slide->getPlainText());
        $this->assertFalse($slide->hasTranslation());
    }

    // ─── hasTranslation() ───

    #[Test]
    public function hasTranslationReturnsTrueWhenMultipleTextElements(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Orginal', '{\rtf1 original}'),
            $this->makeGraphicsElement('Deutsch', '{\rtf1 translated}'),
        ]);
        $slide = new Slide($cue);

        $this->assertTrue($slide->hasTranslation());
    }

    #[Test]
    public function hasTranslationReturnsFalseWhenSingleTextElement(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Orginal', '{\rtf1 original}'),
        ]);
        $slide = new Slide($cue);

        $this->assertFalse($slide->hasTranslation());
    }

    #[Test]
    public function hasTranslationReturnsFalseWhenNoTextElements(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Shape'),
        ]);
        $slide = new Slide($cue);

        $this->assertFalse($slide->hasTranslation());
    }

    // ─── getTranslation() ───

    #[Test]
    public function getTranslationReturnsSecondTextElement(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Orginal', '{\rtf1 original}'),
            $this->makeGraphicsElement('Deutsch', '{\rtf1 translated}'),
        ]);
        $slide = new Slide($cue);

        $translation = $slide->getTranslation();
        $this->assertNotNull($translation);
        $this->assertSame('Deutsch', $translation->getName());
    }

    #[Test]
    public function getTranslationReturnsNullWhenNoTranslation(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Orginal', '{\rtf1 original}'),
        ]);
        $slide = new Slide($cue);

        $this->assertNull($slide->getTranslation());
    }

    // ─── Integration: Real Test.pro ───

    #[Test]
    public function integrationVerse1SingleTextElement(): void
    {
        $presentation = self::loadPresentation();
        $cue = self::findCueByUuid($presentation, '5A6AF946-30B0-4F40-BE7A-C6429C32868A');
        $this->assertNotNull($cue, 'Verse 1 cue not found');

        $slide = new Slide($cue);

        $this->assertSame('5A6AF946-30B0-4F40-BE7A-C6429C32868A', $slide->getUuid());
        $this->assertSame("Vers1.1\nVers1.2", $slide->getPlainText());
        $this->assertFalse($slide->hasTranslation());
        $this->assertNull($slide->getTranslation());
    }

    #[Test]
    public function integrationEndingSlideWithTranslation(): void
    {
        $presentation = self::loadPresentation();
        $cue = self::findCueByUuid($presentation, '562C027E-292E-450A-8DAE-7ABE55E707E0');
        $this->assertNotNull($cue, 'Ending cue not found');

        $slide = new Slide($cue);

        $this->assertSame('562C027E-292E-450A-8DAE-7ABE55E707E0', $slide->getUuid());
        $this->assertTrue($slide->hasTranslation());

        $textElements = $slide->getTextElements();
        $this->assertCount(2, $textElements);

        // First element: "Orginal" with original text
        $this->assertSame('Orginal', $textElements[0]->getName());
        $this->assertSame("Trans Original 1\nTrans Original 2", $textElements[0]->getPlainText());

        // Second element: "Deutsch" with translated text
        $translation = $slide->getTranslation();
        $this->assertNotNull($translation);
        $this->assertSame('Deutsch', $translation->getName());
        $this->assertSame("Translated 1\nTranslated 2", $translation->getPlainText());
    }

    // ─── getCue() ───

    #[Test]
    public function getCueReturnsOriginalProtobufCue(): void
    {
        $cue = $this->makeCue('TEST-UUID', [
            $this->makeGraphicsElement('Text', '{\rtf1 hello}'),
        ]);
        $slide = new Slide($cue);

        $this->assertSame($cue, $slide->getCue());
    }

    // ─── Helpers ───

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

    private static function findCueByUuid(\Rv\Data\Presentation $presentation, string $uuid): ?Cue
    {
        foreach ($presentation->getCues() as $cue) {
            if ($cue->getUuid()?->getString() === $uuid) {
                return $cue;
            }
        }
        return null;
    }
}

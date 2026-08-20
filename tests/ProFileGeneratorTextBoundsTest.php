<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;
use ProPresenter\Parser\Slide;

/**
 * `slideData['textBounds']` / `slideData['textStyle']` place and align the plain
 * TEXT element of a slide. Without them the element keeps the historic
 * full-canvas bounds (150/100 1620x880) and centred alignment.
 */
final class ProFileGeneratorTextBoundsTest extends TestCase
{
    private function firstSlide(array $slideData): Slide
    {
        $song = ProFileGenerator::generate(
            'Bounds Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [$slideData],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
        );

        return new Slide($song->getPresentation()->getCues()[0]);
    }

    #[Test]
    public function text_element_without_bounds_keeps_full_canvas_default(): void
    {
        $slide = $this->firstSlide(['text' => 'Max Mustermann']);

        $this->assertSame(
            ['x' => 150.0, 'y' => 100.0, 'width' => 1620.0, 'height' => 880.0],
            $slide->getTextElementBounds(),
        );
        $this->assertSame('center', $slide->getTextElementAlign());
        $this->assertSame('middle', $slide->getTextElementVerticalAlign());
    }

    #[Test]
    public function explicit_bounds_and_alignment_are_applied(): void
    {
        $slide = $this->firstSlide([
            'text' => 'Max Mustermann',
            'subtitle' => 'Moderation',
            'textBounds' => ['x' => 60, 'y' => 820, 'width' => 600, 'height' => 200],
            'textStyle' => ['align' => 'left', 'verticalAlign' => 'bottom'],
        ]);

        $this->assertSame(
            ['x' => 60.0, 'y' => 820.0, 'width' => 600.0, 'height' => 200.0],
            $slide->getTextElementBounds(),
        );
        $this->assertSame('left', $slide->getTextElementAlign());
        $this->assertSame('bottom', $slide->getTextElementVerticalAlign());

        // Subtitle treatment is unaffected by the placement.
        $rtf = $slide->getTextElements()[0]->getRtfData();
        $this->assertStringContainsString('\b0\fs50', $rtf);
        $this->assertStringContainsString('Moderation', $rtf);
    }

    #[Test]
    public function right_alignment_uses_qr_token(): void
    {
        $slide = $this->firstSlide([
            'text' => 'Max Mustermann',
            'textBounds' => ['x' => 1260, 'y' => 60, 'width' => 600, 'height' => 200],
            'textStyle' => ['align' => 'right', 'verticalAlign' => 'top'],
        ]);

        $this->assertSame('right', $slide->getTextElementAlign());
        $this->assertSame('top', $slide->getTextElementVerticalAlign());
        $this->assertStringContainsString('\qr', $slide->getTextElements()[0]->getRtfData());
    }

    #[Test]
    public function partial_bounds_fall_back_to_defaults_per_key(): void
    {
        $slide = $this->firstSlide([
            'text' => 'Max Mustermann',
            'textBounds' => ['x' => 0, 'y' => 0],
        ]);

        $this->assertSame(
            ['x' => 0.0, 'y' => 0.0, 'width' => 1620.0, 'height' => 880.0],
            $slide->getTextElementBounds(),
        );
    }

    #[Test]
    public function image_only_slide_has_no_text_element_bounds(): void
    {
        $slide = $this->firstSlide([
            'imageOnly' => true,
            'image' => ['path' => 'KEY_VISUAL.jpg', 'format' => 'JPG'],
        ]);

        $this->assertNull($slide->getTextElementBounds());
        $this->assertNull($slide->getTextElementAlign());
        $this->assertNull($slide->getTextElementVerticalAlign());
    }
}

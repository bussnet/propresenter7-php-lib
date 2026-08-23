<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;
use ProPresenter\Parser\Slide;

/**
 * `slideData['textStyle']['color']` colours the plain TEXT element of a slide.
 * Without it the element keeps the historic white colour table, so previously
 * generated files stay byte-identical.
 *
 * The colour is read back from the SECOND colour table entry, which is the one
 * the RTF body references via `\cf2`.
 */
final class ProFileGeneratorTextColorTest extends TestCase
{
    private function firstSlide(array $slideData): Slide
    {
        $song = ProFileGenerator::generate(
            'Color Test',
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
    public function text_element_without_color_defaults_to_white(): void
    {
        $slide = $this->firstSlide(['text' => 'Max Mustermann']);

        $this->assertSame([255, 255, 255], $slide->getTextColor());
    }

    #[Test]
    public function text_element_without_color_keeps_historic_rtf_bytes(): void
    {
        $withoutColor = $this->firstSlide(['text' => 'Max Mustermann']);
        $withWhite = $this->firstSlide([
            'text' => 'Max Mustermann',
            'textStyle' => ['color' => [255, 255, 255]],
        ]);

        $this->assertSame(
            $withoutColor->getTextElements()[0]->getRtfData(),
            $withWhite->getTextElements()[0]->getRtfData(),
        );
        $this->assertStringContainsString(
            '{\colortbl;\red255\green255\blue255;\red255\green255\blue255;}',
            $withoutColor->getTextElements()[0]->getRtfData(),
        );
    }

    #[Test]
    public function explicit_int_color_is_applied_to_the_text_element(): void
    {
        $slide = $this->firstSlide([
            'text' => 'Max Mustermann',
            'textStyle' => ['color' => [255, 200, 0]],
        ]);

        $this->assertSame([255, 200, 0], $slide->getTextColor());
        $this->assertStringContainsString(
            '{\colortbl;\red255\green255\blue255;\red255\green200\blue0;}',
            $slide->getTextElements()[0]->getRtfData(),
        );
    }

    #[Test]
    public function float_scale_color_is_converted_to_0_255_components(): void
    {
        $slide = $this->firstSlide([
            'text' => 'Max Mustermann',
            'textStyle' => ['color' => [1.0, 0.0, 0.5]],
        ]);

        $this->assertSame([255, 0, 128], $slide->getTextColor());
    }

    #[Test]
    public function color_survives_alongside_bounds_and_alignment(): void
    {
        $slide = $this->firstSlide([
            'text' => 'Max Mustermann',
            'subtitle' => 'Moderation',
            'textBounds' => ['x' => 60, 'y' => 820, 'width' => 600, 'height' => 200],
            'textStyle' => ['align' => 'left', 'verticalAlign' => 'bottom', 'color' => [12, 34, 56]],
        ]);

        $this->assertSame([12, 34, 56], $slide->getTextColor());
        $this->assertSame('left', $slide->getTextElementAlign());
        $this->assertSame('bottom', $slide->getTextElementVerticalAlign());
        $this->assertSame(
            ['x' => 60.0, 'y' => 820.0, 'width' => 600.0, 'height' => 200.0],
            $slide->getTextElementBounds(),
        );
    }

    #[Test]
    public function slide_without_text_element_has_no_text_color(): void
    {
        $slide = $this->firstSlide([
            'imageOnly' => true,
            'image' => ['path' => 'KEY_VISUAL.jpg'],
        ]);

        $this->assertNull($slide->getTextColor());
    }

    #[Test]
    public function timer_color_is_read_from_the_timer_element(): void
    {
        $slide = $this->firstSlide([
            'imageOnly' => true,
            'timer' => [
                'timerUuid' => '0E45D0AF-BCC2-4A31-BCFD-0F5A3358E225',
                'timerName' => 'Gottesdienst',
                'format' => 'mm:ss',
                'style' => ['fontSize' => 300, 'bold' => true, 'color' => [255, 200, 0]],
            ],
        ]);

        $this->assertSame([255, 200, 0], $slide->getTimerColor());
    }

    #[Test]
    public function timer_without_explicit_color_defaults_to_white(): void
    {
        $slide = $this->firstSlide([
            'imageOnly' => true,
            'timer' => [
                'timerUuid' => '0E45D0AF-BCC2-4A31-BCFD-0F5A3358E225',
                'timerName' => 'Gottesdienst',
                'format' => 'mm:ss',
            ],
        ]);

        $this->assertSame([255, 255, 255], $slide->getTimerColor());
    }

    #[Test]
    public function slide_without_timer_has_no_timer_color(): void
    {
        $slide = $this->firstSlide(['text' => 'Max Mustermann']);

        $this->assertNull($slide->getTimerColor());
    }
}

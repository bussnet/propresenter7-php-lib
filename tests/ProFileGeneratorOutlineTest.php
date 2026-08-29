<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;
use ProPresenter\Parser\Slide;

/**
 * Text outline (Kontur) support.
 *
 * ProPresenter paints an outline from the RTF stroke traits that sit right
 * after `\cf2`, e.g. `\cf2 \outl0\strokewidth-40 \strokec3 `, referencing a
 * separate colour table entry. A NEGATIVE `\strokewidth` means "outline AND
 * fill"; a positive one would drop the fill. The same information is written to
 * the proto text attributes (`stroke_width` / `stroke_color`), which is what the
 * editor reads back, so both mechanisms are set and kept consistent.
 */
final class ProFileGeneratorOutlineTest extends TestCase
{
    private function firstSlide(array $slideData): Slide
    {
        $song = ProFileGenerator::generate(
            'Outline Test',
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

    private function rtfOf(array $slideData): string
    {
        return $this->firstSlide($slideData)->getTextElements()[0]->getRtfData();
    }

    // --- absent / false => byte-identical output ----------------------------

    #[Test]
    public function no_outline_is_byte_identical_to_no_style(): void
    {
        $this->assertSame(
            $this->rtfOf(['text' => 'Max Mustermann']),
            $this->rtfOf(['text' => 'Max Mustermann', 'textStyle' => ['outline' => false]]),
        );
    }

    #[Test]
    public function a_zero_width_outline_is_byte_identical_to_no_outline(): void
    {
        $this->assertSame(
            $this->rtfOf(['text' => 'Max Mustermann']),
            $this->rtfOf([
                'text' => 'Max Mustermann',
                'textStyle' => ['outline' => ['color' => '#000000', 'width' => 0]],
            ]),
        );
    }

    #[Test]
    public function no_outline_leaves_the_proto_text_attributes_unset(): void
    {
        $element = $this->firstSlide(['text' => 'Max Mustermann'])
            ->getTextElements()[0]
            ->getGraphicsElement();

        $this->assertNull($element->getText()->getAttributes());
    }

    // --- RTF stroke traits --------------------------------------------------

    #[Test]
    public function an_outline_emits_the_rtf_stroke_traits_after_cf2(): void
    {
        $rtf = $this->rtfOf([
            'text' => 'Max Mustermann',
            'textStyle' => ['outline' => ['color' => '#000000', 'width' => 2]],
        ]);

        $this->assertStringContainsString('\cf2 \outl0\strokewidth-40 \strokec3 \CocoaLigature0 Max Mustermann', $rtf);
    }

    #[Test]
    public function the_stroke_width_is_negative_so_the_fill_is_kept(): void
    {
        $rtf = $this->rtfOf([
            'text' => 'Max Mustermann',
            'textStyle' => ['outline' => ['color' => '#000000', 'width' => 1.5]],
        ]);

        $this->assertStringContainsString('\strokewidth-30 ', $rtf);
        $this->assertStringNotContainsString('\strokewidth30 ', $rtf);
    }

    #[Test]
    public function the_outline_colour_is_appended_as_a_third_colour_table_entry(): void
    {
        $rtf = $this->rtfOf([
            'text' => 'Max Mustermann',
            'textStyle' => [
                'color' => [255, 200, 0],
                'outline' => ['color' => [0, 0, 0], 'width' => 2],
            ],
        ]);

        $this->assertStringContainsString(
            '{\colortbl;\red255\green255\blue255;\red255\green200\blue0;\red0\green0\blue0;}',
            $rtf,
        );
        $this->assertStringContainsString('\strokec3 ', $rtf);
    }

    #[Test]
    public function the_expanded_colour_table_mirrors_the_outline_entry(): void
    {
        $rtf = $this->rtfOf([
            'text' => 'Max Mustermann',
            'textStyle' => ['outline' => ['color' => [255, 0, 0], 'width' => 2]],
        ]);

        // Text stays white (\csgray), the outline entry is a real RGB triple.
        $this->assertStringContainsString(
            '{\*\expandedcolortbl;;\csgray\c100000;\csgenericrgb\c100000\c0\c0;}',
            $rtf,
        );
    }

    // --- proto attributes ---------------------------------------------------

    #[Test]
    public function an_outline_sets_the_proto_stroke_width_and_colour(): void
    {
        $attributes = $this->firstSlide([
            'text' => 'Max Mustermann',
            'textStyle' => ['outline' => ['color' => '#FF0000', 'width' => 2]],
        ])->getTextElements()[0]->getGraphicsElement()->getText()->getAttributes();

        $this->assertNotNull($attributes);
        $this->assertSame(2.0, $attributes->getStrokeWidth());
        $this->assertSame(1.0, $attributes->getStrokeColor()->getRed());
        $this->assertSame(0.0, $attributes->getStrokeColor()->getGreen());
        $this->assertSame(0.0, $attributes->getStrokeColor()->getBlue());
    }

    // --- read accessors -----------------------------------------------------

    #[Test]
    public function get_text_outline_returns_null_without_an_outline(): void
    {
        $this->assertNull($this->firstSlide(['text' => 'Max Mustermann'])->getTextOutline());
    }

    #[Test]
    public function get_text_outline_reads_the_outline_back(): void
    {
        $outline = $this->firstSlide([
            'text' => 'Max Mustermann',
            'textStyle' => ['outline' => ['color' => '#0000FF', 'width' => 2]],
        ])->getTextOutline();

        $this->assertSame(['color' => [0, 0, 255], 'width' => 2.0], $outline);
    }

    #[Test]
    public function the_outline_survives_a_write_read_round_trip_through_rtf_only(): void
    {
        // Drop the proto attributes so the RTF fallback path is exercised.
        $slide = $this->firstSlide([
            'text' => 'Max Mustermann',
            'textStyle' => ['outline' => ['color' => [0, 128, 255], 'width' => 3]],
        ]);

        $element = $slide->getTextElements()[0];
        $element->getGraphicsElement()->getText()->setAttributes(null);

        $this->assertSame(['color' => [0, 128, 255], 'width' => 3.0], $element->getOutline());
    }

    // --- translated two-element branch --------------------------------------

    #[Test]
    public function both_elements_of_a_translated_slide_carry_the_outline(): void
    {
        $slide = $this->firstSlide([
            'text' => 'Original',
            'translation' => 'Deutsch',
            'textStyle' => ['outline' => ['color' => '#000000', 'width' => 2]],
        ]);

        $elements = $slide->getTextElements();
        $this->assertCount(2, $elements);

        foreach ($elements as $element) {
            $this->assertStringContainsString('\strokewidth-40 \strokec3 ', $element->getRtfData());
            $this->assertSame(['color' => [0, 0, 0], 'width' => 2.0], $element->getOutline());
        }
    }

    // --- timer element ------------------------------------------------------

    #[Test]
    public function the_timer_element_honours_the_outline(): void
    {
        $slide = $this->firstSlide([
            'imageOnly' => false,
            'timer' => [
                'timerName' => 'Countdown',
                'format' => 'mm:ss',
                'style' => [
                    'fontSize' => 90,
                    'outline' => ['color' => '#000000', 'width' => 2],
                ],
            ],
        ]);

        $this->assertStringContainsString('\strokewidth-40 \strokec3 ', $slide->getTimerElement()->getRtfData());
        $this->assertSame(['color' => [0, 0, 0], 'width' => 2.0], $slide->getTimerOutline());
    }

    #[Test]
    public function a_timer_without_an_outline_keeps_its_historic_rtf(): void
    {
        $withoutOutline = $this->firstSlide([
            'timer' => ['timerName' => 'Countdown', 'style' => ['fontSize' => 90]],
        ])->getTimerElement()->getRtfData();

        $this->assertStringNotContainsString('\strokewidth', $withoutOutline);
        $this->assertStringNotContainsString('\outl', $withoutOutline);
    }
}

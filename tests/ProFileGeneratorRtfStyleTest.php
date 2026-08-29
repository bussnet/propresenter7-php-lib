<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;
use ProPresenter\Parser\Slide;

/**
 * Colour and font treatment of plain TEXT slides.
 *
 * Two bugs are covered here:
 *
 *  1. `{\*\expandedcolortbl}` used to be hardcoded to `\csgray\c100000`
 *     (white). Cocoa/ProPresenter PREFERS the expanded table over `\colortbl`,
 *     so every explicitly coloured text rendered white on screen even though
 *     the `\colortbl` entry — and therefore the parser read-back — was correct.
 *  2. `rtfColorComponents()` guessed the input scale from the magnitude of the
 *     values, so a dark colour whose components are all <= 1 (`#010101`) was
 *     mistaken for a 0..1 float triple and blown up to white.
 *
 * Plus the new optional style parameter on the plain-text RTF template.
 */
final class ProFileGeneratorRtfStyleTest extends TestCase
{
    /**
     * The exact RTF a plain, unstyled, uncoloured text slide has always
     * produced. Any change to this string changes every previously generated
     * file, so it is pinned here verbatim.
     */
    private const HISTORIC_RTF = "{\\rtf1\\ansi\\ansicpg1252\\cocoartf2761\n"
        ."\\cocoatextscaling0\\cocoaplatform0{\\fonttbl\\f0\\fnil\\fcharset0 HelveticaNeue;}\n"
        ."{\\colortbl;\\red255\\green255\\blue255;\\red255\\green255\\blue255;}\n"
        ."{\\*\\expandedcolortbl;;\\csgray\\c100000;}\n"
        ."\\deftab1680\n"
        ."\\pard\\pardeftab1680\\pardirnatural\\qc\\partightenfactor0\n"
        ."\n"
        .'\f0\fs84 \cf2 \CocoaLigature0 Max Mustermann}';

    private function firstSlide(array $slideData): Slide
    {
        $song = ProFileGenerator::generate(
            'Style Test',
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

    // --- (c) no style => byte-identical to the historic output --------------

    #[Test]
    public function plain_text_without_style_is_byte_identical_to_the_historic_rtf(): void
    {
        $this->assertSame(self::HISTORIC_RTF, $this->rtfOf(['text' => 'Max Mustermann']));
    }

    #[Test]
    public function an_empty_style_array_is_byte_identical_to_no_style(): void
    {
        $this->assertSame(
            $this->rtfOf(['text' => 'Max Mustermann']),
            $this->rtfOf(['text' => 'Max Mustermann', 'textStyle' => []]),
        );
    }

    #[Test]
    public function a_white_color_keeps_the_historic_expanded_gray_entry(): void
    {
        // White must keep its `\csgray\c100000` spelling, otherwise every
        // existing white slide would change bytes.
        $this->assertSame(
            self::HISTORIC_RTF,
            $this->rtfOf(['text' => 'Max Mustermann', 'textStyle' => ['color' => [255, 255, 255]]]),
        );
    }

    #[Test]
    public function subtitle_run_without_style_keeps_the_historic_sizes(): void
    {
        $rtf = $this->rtfOf(['text' => 'Max Mustermann', 'subtitle' => 'Moderation']);

        $this->assertStringContainsString('\fs84 \cf2 \CocoaLigature0 Max Mustermann', $rtf);
        $this->assertStringContainsString('\b0\fs50 Moderation', $rtf);
    }

    // --- (a) a non-white colour survives into the expanded table ------------

    #[Test]
    public function a_non_white_color_is_written_to_the_expanded_color_table(): void
    {
        $rtf = $this->rtfOf(['text' => 'Max Mustermann', 'textStyle' => ['color' => [255, 200, 0]]]);

        // Both tables must agree, otherwise Cocoa's preference for the
        // expanded one silently wins and the text renders white.
        $this->assertStringContainsString('{\colortbl;\red255\green255\blue255;\red255\green200\blue0;}', $rtf);
        $this->assertStringContainsString('{\*\expandedcolortbl;;\csgenericrgb\c100000\c78431\c0;}', $rtf);
        $this->assertStringNotContainsString('\csgray\c100000', $rtf);
    }

    #[Test]
    public function the_expanded_color_table_never_stays_white_for_a_colored_text(): void
    {
        $slide = $this->firstSlide(['text' => 'Max Mustermann', 'textStyle' => ['color' => [12, 34, 56]]]);

        $this->assertSame([12, 34, 56], $slide->getTextColor());
        $this->assertStringNotContainsString(
            '{\*\expandedcolortbl;;\csgray\c100000;}',
            $slide->getTextElements()[0]->getRtfData(),
        );
    }

    #[Test]
    public function a_colored_timer_writes_a_matching_expanded_entry(): void
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

        $rtf = $slide->getTimerElement()?->getRtfData() ?? '';
        $this->assertStringContainsString('\csgenericrgb\c100000\c78431\c0', $rtf);
        $this->assertStringNotContainsString('\csgray\c100000', $rtf);
    }

    #[Test]
    public function a_black_color_is_not_turned_into_white(): void
    {
        $slide = $this->firstSlide(['text' => 'Max Mustermann', 'textStyle' => ['color' => [0, 0, 0]]]);

        $this->assertSame([0, 0, 0], $slide->getTextColor());
        $this->assertStringContainsString(
            '{\*\expandedcolortbl;;\csgenericrgb\c0\c0\c0;}',
            $slide->getTextElements()[0]->getRtfData(),
        );
    }

    // --- (b) #010101 must not become white ----------------------------------

    #[Test]
    public function a_very_dark_int_triple_is_not_mistaken_for_a_float_scale(): void
    {
        // The old magnitude heuristic saw r,g,b <= 1 and multiplied by 255.
        $slide = $this->firstSlide(['text' => 'Max Mustermann', 'textStyle' => ['color' => [1, 1, 1]]]);

        $this->assertSame([1, 1, 1], $slide->getTextColor());
        $this->assertNotSame([255, 255, 255], $slide->getTextColor());
    }

    #[Test]
    public function a_very_dark_hex_string_is_not_mistaken_for_a_float_scale(): void
    {
        $slide = $this->firstSlide(['text' => 'Max Mustermann', 'textStyle' => ['color' => '#010101']]);

        $this->assertSame([1, 1, 1], $slide->getTextColor());
    }

    #[Test]
    public function hex_strings_are_accepted_with_and_without_the_leading_hash(): void
    {
        $this->assertSame([255, 200, 0], $this->firstSlide([
            'text' => 'x', 'textStyle' => ['color' => '#FFC800'],
        ])->getTextColor());

        $this->assertSame([255, 200, 0], $this->firstSlide([
            'text' => 'x', 'textStyle' => ['color' => 'ffc800'],
        ])->getTextColor());
    }

    #[Test]
    public function an_invalid_hex_string_falls_back_to_white(): void
    {
        $this->assertSame([255, 255, 255], $this->firstSlide([
            'text' => 'x', 'textStyle' => ['color' => 'not-a-color'],
        ])->getTextColor());
    }

    #[Test]
    public function genuine_float_triples_are_still_scaled_from_0_to_1(): void
    {
        $this->assertSame([255, 0, 128], $this->firstSlide([
            'text' => 'x', 'textStyle' => ['color' => [1.0, 0.0, 0.5]],
        ])->getTextColor());

        // All components <= 1.0 but genuinely floats => 0..1 scale.
        $this->assertSame([3, 3, 3], $this->firstSlide([
            'text' => 'x', 'textStyle' => ['color' => [0.01, 0.01, 0.01]],
        ])->getTextColor());
    }

    #[Test]
    public function out_of_range_components_are_clamped(): void
    {
        $this->assertSame([255, 0, 255], $this->firstSlide([
            'text' => 'x', 'textStyle' => ['color' => [999, -5, 255]],
        ])->getTextColor());
    }

    // --- (d) style applies font name / size / traits ------------------------

    #[Test]
    public function a_style_applies_the_font_name(): void
    {
        $rtf = $this->rtfOf(['text' => 'Max Mustermann', 'textStyle' => ['fontName' => 'Impact']]);

        $this->assertStringContainsString('{\fonttbl\f0\fnil\fcharset0 Impact;}', $rtf);
        $this->assertStringNotContainsString('HelveticaNeue', $rtf);
    }

    #[Test]
    public function a_style_applies_the_font_size_in_half_points(): void
    {
        $rtf = $this->rtfOf(['text' => 'Max Mustermann', 'textStyle' => ['fontSize' => 60]]);

        $this->assertStringContainsString('\fs120 \cf2 \CocoaLigature0 Max Mustermann', $rtf);
        $this->assertStringNotContainsString('\fs84', $rtf);
    }

    #[Test]
    public function a_style_applies_bold_italic_and_underline(): void
    {
        $rtf = $this->rtfOf([
            'text' => 'Max Mustermann',
            'textStyle' => ['bold' => true, 'italic' => true, 'underline' => true],
        ]);

        $this->assertStringContainsString('\b \i \ul \fs84 \cf2 \CocoaLigature0 Max Mustermann', $rtf);
    }

    #[Test]
    public function a_style_can_size_the_subtitle_run_separately(): void
    {
        $rtf = $this->rtfOf([
            'text' => 'Max Mustermann',
            'subtitle' => 'Moderation',
            'textStyle' => ['fontSize' => 60, 'subtitleFontSize' => 20],
        ]);

        $this->assertStringContainsString('\fs120 \cf2 \CocoaLigature0 Max Mustermann', $rtf);
        $this->assertStringContainsString('\b0\fs40 Moderation', $rtf);
    }

    #[Test]
    public function font_style_and_color_combine_on_one_element(): void
    {
        $slide = $this->firstSlide([
            'text' => 'Max Mustermann',
            'textStyle' => [
                'fontName' => 'Impact',
                'fontSize' => 60,
                'bold' => true,
                'color' => [255, 200, 0],
                'align' => 'left',
            ],
        ]);

        $rtf = $slide->getTextElements()[0]->getRtfData();

        $this->assertSame([255, 200, 0], $slide->getTextColor());
        $this->assertSame('left', $slide->getTextElementAlign());
        $this->assertStringContainsString('Impact;', $rtf);
        $this->assertStringContainsString('\b \fs120 \cf2 \CocoaLigature0 Max Mustermann', $rtf);
        $this->assertStringContainsString('\csgenericrgb\c100000\c78431\c0', $rtf);
    }

    #[Test]
    public function the_plain_text_is_still_readable_after_styling(): void
    {
        $slide = $this->firstSlide([
            'text' => 'Größe für Ärzte',
            'textStyle' => ['fontName' => 'Impact', 'fontSize' => 60, 'color' => [12, 34, 56]],
        ]);

        $this->assertSame('Größe für Ärzte', $slide->getPlainText());
    }

    // --- style also reaches the translated (two element) branch -------------

    #[Test]
    public function a_translated_slide_without_style_keeps_its_historic_bytes(): void
    {
        $slide = $this->firstSlide(['text' => 'Original', 'translation' => 'Deutsch']);

        foreach ($slide->getTextElements() as $element) {
            $this->assertStringContainsString('HelveticaNeue', $element->getRtfData());
            $this->assertStringContainsString('\f0\fs84 \cf2 \CocoaLigature0 ', $element->getRtfData());
            $this->assertStringContainsString('{\*\expandedcolortbl;;\csgray\c100000;}', $element->getRtfData());
        }
    }

    #[Test]
    public function a_translated_slide_applies_the_style_to_both_elements(): void
    {
        $slide = $this->firstSlide([
            'text' => 'Original',
            'translation' => 'Deutsch',
            'textStyle' => ['fontName' => 'Impact', 'fontSize' => 60, 'color' => [255, 200, 0]],
        ]);

        $elements = $slide->getTextElements();
        $this->assertCount(2, $elements);

        foreach ($elements as $element) {
            $rtf = $element->getRtfData();
            $this->assertStringContainsString('Impact;', $rtf);
            $this->assertStringContainsString('\fs120 \cf2 \CocoaLigature0 ', $rtf);
            $this->assertSame([255, 200, 0], $element->getTextColor());
            $this->assertStringContainsString('\csgenericrgb\c100000\c78431\c0', $rtf);
        }

        $this->assertSame('Original', $elements[0]->getPlainText());
        $this->assertSame('Deutsch', $elements[1]->getPlainText());
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;

final class NameTagSubtitleRtfTest extends TestCase
{
    /**
     * Pull the RTF data of the first slide's first text element.
     */
    private function firstSlideRtf(array $slideData): string
    {
        $song = ProFileGenerator::generate(
            'Subtitle Test',
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

        $cue = $song->getPresentation()->getCues()[0];
        $baseSlide = $cue->getActions()[0]->getSlide()->getPresentation()->getBaseSlide();
        $elements = $baseSlide->getElements();

        $this->assertNotEmpty($elements, 'Slide must have at least one element');

        return $elements[0]->getElement()->getText()->getRtfData();
    }

    #[Test]
    public function subtitle_renders_as_separate_non_bold_smaller_run(): void
    {
        $rtf = $this->firstSlideRtf([
            'text' => 'Max Mustermann',
            'subtitle' => 'Moderation',
        ]);

        // Main text keeps \fs84.
        $this->assertStringContainsString('\fs84', $rtf);
        $this->assertStringContainsString('Max Mustermann', $rtf);

        // Subtitle is a separate run that is explicitly non-bold (\b0) and smaller (\fs50).
        $this->assertStringContainsString('\b0\fs50', $rtf);
        $this->assertStringContainsString('Moderation', $rtf);

        // The subtitle run must come after the main text.
        $this->assertLessThan(
            strpos($rtf, '\b0\fs50'),
            strpos($rtf, 'Max Mustermann'),
            'Main text must precede the subtitle run',
        );

        // The subtitle run must follow the main \fs84 run.
        $this->assertLessThan(
            strpos($rtf, '\b0\fs50'),
            strpos($rtf, '\fs84'),
            '\fs84 main run must precede the \fs50 subtitle run',
        );
    }

    #[Test]
    public function slide_without_subtitle_is_unchanged_single_run(): void
    {
        $rtf = $this->firstSlideRtf([
            'text' => 'Max Mustermann',
        ]);

        // Exact single-run body identical to the legacy output.
        $this->assertStringContainsString(
            '\f0\fs84 \cf2 \CocoaLigature0 Max Mustermann',
            $rtf,
        );

        // No subtitle styling leaks into lyric/plain slides.
        $this->assertStringNotContainsString('\b0', $rtf);
        $this->assertStringNotContainsString('\fs50', $rtf);
    }

    #[Test]
    public function empty_subtitle_is_treated_as_no_subtitle(): void
    {
        $rtf = $this->firstSlideRtf([
            'text' => 'Max Mustermann',
            'subtitle' => '   ',
        ]);

        $this->assertStringNotContainsString('\b0', $rtf);
        $this->assertStringNotContainsString('\fs50', $rtf);
    }
}

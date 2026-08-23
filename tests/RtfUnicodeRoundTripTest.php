<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;
use ProPresenter\Parser\ProFileReader;
use ProPresenter\Parser\ProFileWriter;

/**
 * End-to-end guard against the "I’m" => "â€™" mojibake: text goes through the
 * generator, is written to a real .pro file, read back and must be identical.
 */
final class RtfUnicodeRoundTripTest extends TestCase
{
    private const SAMPLE = 'I’m – „quote“ … ä ö ü ß 🎵';

    private string $tempDir = '';

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/pro-rtf-roundtrip-'.bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        if ($this->tempDir !== '' && is_dir($this->tempDir)) {
            foreach (glob($this->tempDir.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($this->tempDir);
        }
    }

    private function generateSong(string $text): \ProPresenter\Parser\Song
    {
        return ProFileGenerator::generate(
            'Unicode Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [['text' => $text]],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
        );
    }

    #[Test]
    public function generated_rtf_body_contains_no_raw_high_bytes(): void
    {
        $song = $this->generateSong(self::SAMPLE);
        $cue = $song->getPresentation()->getCues()[0];
        $baseSlide = $cue->getActions()[0]->getSlide()->getPresentation()->getBaseSlide();
        $rtf = $baseSlide->getElements()[0]->getElement()->getText()->getRtfData();

        $this->assertSame(
            0,
            preg_match('/[\x80-\xFF]/', $rtf),
            'An \ansicpg1252 RTF body must be pure ASCII; raw UTF-8 bytes become mojibake in ProPresenter.',
        );

        // The specific byte sequence that caused the bug must not be present.
        $this->assertStringNotContainsString("\xE2\x80\x99", $rtf);
        $this->assertStringContainsString("\\'92", $rtf, 'U+2019 must be the Windows-1252 escape \\\'92');
    }

    #[Test]
    public function text_survives_a_full_write_and_read_round_trip(): void
    {
        $path = $this->tempDir.'/unicode.pro';

        ProFileWriter::write($this->generateSong(self::SAMPLE), $path);

        $slides = ProFileReader::read($path)->getSlides();

        $this->assertCount(1, $slides);
        $this->assertSame(self::SAMPLE, $slides[0]->getPlainText());
    }

    #[Test]
    public function every_tricky_character_survives_the_round_trip(): void
    {
        foreach (['’', 'ä', 'ö', 'ü', 'ß', '–', '„', '“', '…', '€', '🎵', 'Ж'] as $char) {
            $path = $this->tempDir.'/char.pro';
            $text = 'A'.$char.'B';

            ProFileWriter::write($this->generateSong($text), $path);

            $this->assertSame(
                $text,
                ProFileReader::read($path)->getSlides()[0]->getPlainText(),
                'Round trip failed for '.json_encode($char),
            );

            unlink($path);
        }
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\RtfEncoder;
use ProPresenter\Parser\RtfExtractor;

final class RtfEncoderTest extends TestCase
{
    /**
     * Wrap an encoded body in the same CocoaRTF envelope the generator emits.
     */
    private function wrap(string $encodedBody): string
    {
        return "{\\rtf1\\ansi\\ansicpg1252\\cocoartf2761\n"
            ."\\cocoatextscaling0\\cocoaplatform0{\\fonttbl\\f0\\fnil\\fcharset0 HelveticaNeue;}\n"
            ."{\\colortbl;\\red255\\green255\\blue255;\\red255\\green255\\blue255;}\n"
            ."\\pard\\pardeftab1680\\qc\\partightenfactor0\n\n"
            .'\f0\fs84 \cf2 \CocoaLigature0 '.$encodedBody.'}';
    }

    #[Test]
    public function ascii_passes_through_untouched(): void
    {
        $this->assertSame('Hello World 123', RtfEncoder::encode('Hello World 123'));
    }

    #[Test]
    public function german_umlauts_become_windows1252_hex_escapes(): void
    {
        $this->assertSame("\\'fc\\'f6\\'e4\\'df\\'dc\\'d6\\'c4", RtfEncoder::encode('üöäßÜÖÄ'));
    }

    #[Test]
    public function right_single_quote_is_escaped_not_written_as_raw_utf8(): void
    {
        $encoded = RtfEncoder::encode('I’m');

        // This is the whole bug: a raw E2 80 99 in an \ansicpg1252 document
        // is read back by ProPresenter as "â€™".
        $this->assertSame("I\\'92m", $encoded);
        $this->assertSame('', preg_replace('/[\x00-\x7F]/', '', $encoded));
    }

    #[Test]
    public function typographic_punctuation_uses_windows1252_slots(): void
    {
        $this->assertSame("\\'96", RtfEncoder::encode('–'));
        $this->assertSame("\\'97", RtfEncoder::encode('—'));
        $this->assertSame("\\'85", RtfEncoder::encode('…'));
        $this->assertSame("\\'84", RtfEncoder::encode('„'));
        $this->assertSame("\\'93", RtfEncoder::encode('“'));
        $this->assertSame("\\'80", RtfEncoder::encode('€'));
    }

    #[Test]
    public function characters_outside_windows1252_use_unicode_escapes(): void
    {
        // U+0416 CYRILLIC CAPITAL ZHE has no Windows-1252 slot.
        $this->assertSame('\u1046?', RtfEncoder::encode('Ж'));
    }

    #[Test]
    public function emoji_is_encoded_as_a_utf16_surrogate_pair(): void
    {
        // U+1F3B5 => high D83C (-10180 signed), low DFB5 (-8267 signed).
        $this->assertSame('\u-10180?\u-8267?', RtfEncoder::encode('🎵'));
    }

    #[Test]
    public function rtf_structural_characters_are_escaped(): void
    {
        $this->assertSame('\\\\ \\{ \\}', RtfEncoder::encode('\\ { }'));
    }

    #[Test]
    public function newlines_become_rtf_soft_returns(): void
    {
        $this->assertSame("A\\\nB", RtfEncoder::encode("A\nB"));
        $this->assertSame("A\\\nB", RtfEncoder::encode("A\r\nB"));
        $this->assertSame("A\\\nB", RtfEncoder::encode("A\rB"));
    }

    #[Test]
    public function the_encoded_body_never_contains_raw_high_bytes(): void
    {
        $encoded = RtfEncoder::encode('I’m – „quote“ … ä ö ü ß 🎵 € Ж');

        $this->assertSame(
            0,
            preg_match('/[\x80-\xFF]/', $encoded),
            'Encoded RTF body must be pure 7-bit ASCII, otherwise \ansicpg1252 mangles it.',
        );
    }

    #[DataProvider('roundTripProvider')]
    #[Test]
    public function encode_and_extract_round_trips_losslessly(string $source): void
    {
        $this->assertSame($source, RtfExtractor::toPlainText($this->wrap(RtfEncoder::encode($source))));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function roundTripProvider(): array
    {
        return [
            'apostrophe' => ['I’m yours'],
            'german umlauts' => ['Schöne Grüße, Bär'],
            'en dash' => ['Anfang – Ende'],
            'german quotes' => ['Er sagte „Hallo“ zu mir'],
            'ellipsis' => ['Und dann…'],
            'euro' => ['Kostet 5 €'],
            'emoji' => ['Lobpreis 🎵 Anbetung'],
            'cyrillic' => ['Ж und Я'],
            'everything' => ['I’m – „quote“ … ä ö ü ß 🎵'],
            'multiline' => ["Zeile ä\nZeile ’\nZeile 🎵"],
            'ascii only' => ['Plain ASCII text'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

/**
 * Encodes plain UTF-8 text into the body of a CocoaRTF (\ansicpg1252) document.
 *
 * ProPresenter writes and reads its slide text as RTF with an `\ansicpg1252`
 * header. Everything outside of 7-bit ASCII therefore MUST be escaped, either
 * as a Windows-1252 hex escape (`\'xx`) or — for characters that have no
 * Windows-1252 representation — as an RTF unicode escape (`\uNNNN?`).
 *
 * Writing raw UTF-8 bytes into such a document is what produces the classic
 * mojibake: `’` (U+2019, UTF-8 `E2 80 99`) read back as Windows-1252 becomes
 * `â€™`.
 *
 * Characters above the BMP (emoji) are emitted as an RTF surrogate pair, which
 * is how Word and Cocoa write them too.
 */
final class RtfEncoder
{
    /**
     * Unicode codepoint => Windows-1252 byte, for the 0x80..0x9F range where
     * Windows-1252 deviates from Latin-1. The inverse of the table in
     * {@see RtfExtractor}.
     *
     * @var array<int, int>
     */
    private const CP1252_HIGH = [
        0x20AC => 0x80, 0x201A => 0x82, 0x0192 => 0x83, 0x201E => 0x84,
        0x2026 => 0x85, 0x2020 => 0x86, 0x2021 => 0x87, 0x02C6 => 0x88,
        0x2030 => 0x89, 0x0160 => 0x8A, 0x2039 => 0x8B, 0x0152 => 0x8C,
        0x017D => 0x8E, 0x2018 => 0x91, 0x2019 => 0x92, 0x201C => 0x93,
        0x201D => 0x94, 0x2022 => 0x95, 0x2013 => 0x96, 0x2014 => 0x97,
        0x02DC => 0x98, 0x2122 => 0x99, 0x0161 => 0x9A, 0x203A => 0x9B,
        0x0153 => 0x9C, 0x017E => 0x9E, 0x0178 => 0x9F,
    ];

    /**
     * Encode plain text for use inside an RTF body.
     *
     * Line breaks become RTF soft returns (a backslash followed by a literal
     * newline), matching what ProPresenter itself writes.
     */
    public static function encode(string $text): string
    {
        $text = self::normalise($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $out = '';

        foreach (self::codepoints($text) as $codepoint) {
            $out .= self::encodeCodepoint($codepoint);
        }

        return $out;
    }

    /**
     * Ensure the input is valid UTF-8. Invalid byte sequences are repaired
     * rather than silently emitted, so a mis-encoded caller can never poison
     * the generated file.
     */
    private static function normalise(string $text): string
    {
        if ($text === '' || mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }

    /**
     * @return list<int>
     */
    private static function codepoints(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $codepoints = mb_str_split($text, 1, 'UTF-8');

        return array_map(
            static fn (string $char): int => mb_ord($char, 'UTF-8'),
            $codepoints,
        );
    }

    private static function encodeCodepoint(int $codepoint): string
    {
        // Soft return: a backslash followed by a literal newline.
        if ($codepoint === 0x0A) {
            return "\\\n";
        }

        // RTF structural characters must be escaped.
        if ($codepoint === 0x5C || $codepoint === 0x7B || $codepoint === 0x7D) {
            return '\\'.chr($codepoint);
        }

        // Plain 7-bit ASCII passes through untouched.
        if ($codepoint >= 0x20 && $codepoint <= 0x7E) {
            return chr($codepoint);
        }

        // Remaining control characters carry no meaning in a slide body.
        if ($codepoint < 0x20) {
            return '';
        }

        // Latin-1 range shared by Windows-1252 (ä ö ü ß … ).
        if ($codepoint >= 0xA0 && $codepoint <= 0xFF) {
            return self::hexEscape($codepoint);
        }

        // Windows-1252 specials (typographic quotes, dashes, ellipsis, €).
        if (isset(self::CP1252_HIGH[$codepoint])) {
            return self::hexEscape(self::CP1252_HIGH[$codepoint]);
        }

        // Above the BMP (emoji): RTF expects a UTF-16 surrogate pair.
        if ($codepoint > 0xFFFF) {
            $value = $codepoint - 0x10000;
            $high = 0xD800 + ($value >> 10);
            $low = 0xDC00 + ($value & 0x3FF);

            return self::unicodeEscape($high).self::unicodeEscape($low);
        }

        return self::unicodeEscape($codepoint);
    }

    private static function hexEscape(int $byte): string
    {
        return "\\'".str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
    }

    /**
     * RTF stores unicode escapes as SIGNED 16-bit integers and expects an ANSI
     * fallback character right after the number. `?` is the conventional one.
     */
    private static function unicodeEscape(int $codepoint): string
    {
        $signed = $codepoint > 0x7FFF ? $codepoint - 0x10000 : $codepoint;

        return '\u'.$signed.'?';
    }
}

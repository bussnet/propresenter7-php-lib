<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

/**
 * Extracts plain text from ProPresenter's CocoaRTF 2761 format.
 *
 * ProPresenter uses Apple's CocoaRTF variant with consistent structure:
 * - Header: {\rtf1\ansi\ansicpg1252\cocoartf2761 ...}
 * - Font table: {\fonttbl ...}
 * - Color table: {\colortbl ...}
 * - Expanded color table: {\*\expandedcolortbl ...}
 * - Paragraph formatting: \pard...
 * - Text after \CocoaLigature0 (or last formatting command before text)
 * - Soft returns: \\ at end of line → newline in plain text
 * - Hex escapes: \'xx for Windows-1252 encoded characters (German ü ö ä ß etc)
 * - Unicode escapes: \uN? where N is decimal codepoint and ? is ANSI fallback
 */
class RtfExtractor
{
    /**
     * Convert an RTF string to plain text.
     *
     * @param string $rtf The RTF-encoded string from ProPresenter
     * @return string Plain text with \n for soft returns
     */
    public static function toPlainText(string $rtf): string
    {
        $rtf = trim($rtf);

        if ($rtf === '') {
            return '';
        }

        // Not RTF? Return as-is
        if (!str_starts_with($rtf, '{\rtf')) {
            return $rtf;
        }

        // Step 1: Strip nested groups { ... } that contain metadata (fonttbl, colortbl, expandedcolortbl)
        $content = self::stripNestedGroups($rtf);

        // Step 2: Process the remaining content to extract text
        return self::extractText($content);
    }

    /**
     * Remove the outer RTF envelope and all nested brace groups (fonttbl, colortbl, etc).
     * Returns the flat content after group removal.
     */
    private static function stripNestedGroups(string $rtf): string
    {
        // Remove outer braces
        if (str_starts_with($rtf, '{') && str_ends_with($rtf, '}')) {
            $rtf = substr($rtf, 1, -1);
        }

        $result = '';
        $depth = 0;
        $len = strlen($rtf);
        $i = 0;

        while ($i < $len) {
            $ch = $rtf[$i];

            if ($ch === '{') {
                $depth++;
                $i++;
                continue;
            }

            if ($ch === '}') {
                $depth = max(0, $depth - 1);
                $i++;
                continue;
            }

            // Only capture content at depth 0 (outside any nested group)
            if ($depth === 0) {
                $result .= $ch;
            }

            $i++;
        }

        return $result;
    }

    /**
     * Extract plain text from RTF content that has had groups stripped.
     * Processes control words, hex escapes, unicode escapes, and soft returns.
     */
    private static function extractText(string $content): string
    {
        $text = '';
        $len = strlen($content);
        $i = 0;

        while ($i < $len) {
            $ch = $content[$i];

            // Backslash = control sequence
            if ($ch === '\\') {
                $i++;
                if ($i >= $len) {
                    break;
                }

                $next = $content[$i];

                // Soft return: \\ followed by newline → \n in output
                if ($next === "\n" || $next === "\r") {
                    $text .= "\n";
                    $i++;
                    // Skip \r\n combo
                    if ($i < $len && $content[$i] === "\n" && $next === "\r") {
                        $i++;
                    }
                    continue;
                }

                // Hex escape: \'xx (Windows-1252 byte)
                if ($next === '\'') {
                    $i++;
                    if ($i + 1 < $len) {
                        $hex = substr($content, $i, 2);
                        $byte = hexdec($hex);
                        $text .= self::windows1252ToUtf8($byte);
                        $i += 2;
                    }
                    continue;
                }

                // Unicode escape: \uNNNN? (decimal codepoint, ? is ANSI fallback to skip)
                if ($next === 'u' && $i + 1 < $len && (ctype_digit($content[$i + 1]) || $content[$i + 1] === '-')) {
                    $i++; // past 'u'
                    $numStr = '';
                    while ($i < $len && ($content[$i] === '-' || ctype_digit($content[$i]))) {
                        $numStr .= $content[$i];
                        $i++;
                    }
                    $codepoint = (int)$numStr;
                    // Handle negative values (RTF uses signed 16-bit)
                    if ($codepoint < 0) {
                        $codepoint += 65536;
                    }
                    $text .= self::codepointToUtf8($codepoint);
                    // Skip the ANSI fallback character (single char after the number)
                    if ($i < $len && $content[$i] !== '\\' && $content[$i] !== '{' && $content[$i] !== '}') {
                        $i++;
                    }
                    continue;
                }

                // Control word: \word[N] followed by space or non-alpha
                if (ctype_alpha($next)) {
                    $word = $next;
                    $i++;
                    while ($i < $len && ctype_alpha($content[$i])) {
                        $word .= $content[$i];
                        $i++;
                    }
                    // Skip optional numeric parameter
                    if ($i < $len && ($content[$i] === '-' || ctype_digit($content[$i]))) {
                        $i++;
                        while ($i < $len && ctype_digit($content[$i])) {
                            $i++;
                        }
                    }
                    // Space delimiter after control word is consumed (not part of text)
                    if ($i < $len && $content[$i] === ' ') {
                        $i++;
                    }

                    // \par = paragraph break
                    if ($word === 'par') {
                        $text .= "\n";
                    }

                    // All other control words are formatting → skip
                    continue;
                }

                // Escaped special characters: \{ \} \\
                if ($next === '{' || $next === '}') {
                    $text .= $next;
                    $i++;
                    continue;
                }

                if ($next === '\\') {
                    // Literal backslash — but in ProPresenter context this is soft return
                    $text .= "\n";
                    $i++;
                    continue;
                }

                // Other escaped chars: skip the backslash, keep the char
                $i++;
                continue;
            }

            // Regular newlines in RTF source are just whitespace (not meaningful)
            if ($ch === "\n" || $ch === "\r") {
                $i++;
                continue;
            }

            // Regular character → output
            $text .= $ch;
            $i++;
        }

        return trim($text);
    }

    /**
     * Convert a Windows-1252 byte value to UTF-8 string.
     * Windows-1252 is a superset of ISO-8859-1 with extra chars in 0x80-0x9F range.
     */
    private static function windows1252ToUtf8(int $byte): string
    {
        // The 0x80-0x9F range differs from Unicode in Windows-1252
        static $cp1252 = [
            0x80 => 0x20AC, 0x82 => 0x201A, 0x83 => 0x0192, 0x84 => 0x201E,
            0x85 => 0x2026, 0x86 => 0x2020, 0x87 => 0x2021, 0x88 => 0x02C6,
            0x89 => 0x2030, 0x8A => 0x0160, 0x8B => 0x2039, 0x8C => 0x0152,
            0x8E => 0x017D, 0x91 => 0x2018, 0x92 => 0x2019, 0x93 => 0x201C,
            0x94 => 0x201D, 0x95 => 0x2022, 0x96 => 0x2013, 0x97 => 0x2014,
            0x98 => 0x02DC, 0x99 => 0x2122, 0x9A => 0x0161, 0x9B => 0x203A,
            0x9C => 0x0153, 0x9E => 0x017E, 0x9F => 0x0178,
        ];

        $codepoint = $cp1252[$byte] ?? $byte;
        return self::codepointToUtf8($codepoint);
    }

    /**
     * Convert a Unicode codepoint to a UTF-8 encoded string.
     */
    private static function codepointToUtf8(int $codepoint): string
    {
        return mb_chr($codepoint, 'UTF-8');
    }
}

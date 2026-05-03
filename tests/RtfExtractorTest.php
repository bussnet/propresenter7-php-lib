<?php

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ProPresenter\Parser\RtfExtractor;

class RtfExtractorTest extends TestCase
{
    // ─── Real ProPresenter RTF from Test.pro ───

    #[Test]
    public function extractsMultilineTextFromRealProPresenterRtf(): void
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

        $result = RtfExtractor::toPlainText($rtf);

        $this->assertSame("Vers1.1\nVers1.2", $result);
    }

    #[Test]
    public function extractsSingleLineText(): void
    {
        $rtf = '{\rtf1\ansi\ansicpg1252\cocoartf2761' . "\n"
             . '\cocoatextscaling0\cocoaplatform0{\fonttbl\f0\fnil\fcharset0 HelveticaNeue;}' . "\n"
             . '{\colortbl;\red255\green255\blue255;\red255\green255\blue255;}' . "\n"
             . '{\*\expandedcolortbl;;\csgray\c100000;}' . "\n"
             . '\deftab1680' . "\n"
             . '\pard\pardeftab1680\pardirnatural\qc\partightenfactor0' . "\n"
             . "\n"
             . '\f0\fs84 \cf2 \CocoaLigature0 Chorus1\\' . "\n"
             . 'Chorus2}';

        $result = RtfExtractor::toPlainText($rtf);

        $this->assertSame("Chorus1\nChorus2", $result);
    }

    // ─── German characters ───

    #[Test]
    public function extractsGermanCharactersFromRtf(): void
    {
        // Real pattern from "An einem Kreuz hängt Gottes Sohn.pro"
        // Nowdoc preserves backslashes literally - essential for RTF \' hex escapes
        $rtf = <<<'RTF'
        {\rtf1\ansi\ansicpg1252\cocoartf2761
        \cocoatextscaling0\cocoaplatform0{\fonttbl\f0\fnil\fcharset0 AvenirNext-Regular;}
        {\colortbl;\red255\green255\blue255;\red255\green255\blue255;\red30\green30\blue30;}
        {\*\expandedcolortbl;;\csgenericrgb\c100000\c100000\c100000;\csgenericrgb\c11818\c11807\c11716;}
        \pard\slleading-40\pardirnatural\qc\partightenfactor0
        
        \f0\fs120 \cf2 \outl0\strokewidth-40 \strokec3 ist alles, was uns qu\'e4lt, vorbei,\
        denn er, der starb, macht alles neu.}
        RTF;

        $result = RtfExtractor::toPlainText($rtf);

        $this->assertSame("ist alles, was uns quält, vorbei,\ndenn er, der starb, macht alles neu.", $result);
    }

    #[Test]
    public function extractsAllGermanSpecialCharacters(): void
    {
        $rtf = <<<'RTF'
        {\rtf1\ansi\ansicpg1252\cocoartf2761
        \cocoatextscaling0\cocoaplatform0{\fonttbl\f0\fnil\fcharset0 HelveticaNeue;}
        {\colortbl;\red255\green255\blue255;\red255\green255\blue255;}
        {\*\expandedcolortbl;;\csgray\c100000;}
        \pard\pardirnatural\qc\partightenfactor0
        
        \f0\fs84 \cf2 \CocoaLigature0 Gr\'fc\'dfe \'f6ffnen \'e4ndern \'e9l\'e8ve}
        RTF;

        $result = RtfExtractor::toPlainText($rtf);

        $this->assertSame('Grüße öffnen ändern élève', $result);
    }

    // ─── Edge cases ───

    #[Test]
    public function emptyStringReturnsEmpty(): void
    {
        $this->assertSame('', RtfExtractor::toPlainText(''));
    }

    #[Test]
    public function nullishRtfReturnsEmpty(): void
    {
        $this->assertSame('', RtfExtractor::toPlainText('   '));
    }

    #[Test]
    public function rtfWithOnlyFormattingReturnsEmpty(): void
    {
        // RTF with formatting commands but no actual text content
        $rtf = '{\rtf1\ansi\ansicpg1252\cocoartf2761' . "\n"
             . '\cocoatextscaling0\cocoaplatform0{\fonttbl\f0\fnil\fcharset0 HelveticaNeue;}' . "\n"
             . '{\colortbl;\red255\green255\blue255;\red255\green255\blue255;}' . "\n"
             . '{\*\expandedcolortbl;;\csgray\c100000;}' . "\n"
             . '\deftab1680' . "\n"
             . '\pard\pardeftab1680\pardirnatural\qc\partightenfactor0' . "\n"
             . "\n"
             . '\f0\fs84 \cf2 \CocoaLigature0 }';

        $result = RtfExtractor::toPlainText($rtf);

        $this->assertSame('', $result);
    }

    // ─── Translation text box (different font size, same structure) ───

    #[Test]
    public function extractsTranslationText(): void
    {
        // Real translation RTF from Test.pro
        $rtf = '{\rtf1\ansi\ansicpg1252\cocoartf2761' . "\n"
             . '\cocoatextscaling0\cocoaplatform0{\fonttbl\f0\fnil\fcharset0 HelveticaNeue;}' . "\n"
             . '{\colortbl;\red255\green255\blue255;\red255\green255\blue255;}' . "\n"
             . '{\*\expandedcolortbl;;\cssrgb\c100000\c100000\c100000;}' . "\n"
             . '\deftab1680' . "\n"
             . '\pard\pardeftab1680\pardirnatural\qc\partightenfactor0' . "\n"
             . "\n"
             . '\f0\fs80 \cf2 \CocoaLigature0 Translated 1\\' . "\n"
             . 'Translated 2}';

        $result = RtfExtractor::toPlainText($rtf);

        $this->assertSame("Translated 1\nTranslated 2", $result);
    }

    // ─── Unicode escapes ───

    #[Test]
    public function handlesUnicodeEscapes(): void
    {
        // \uN? format where ? is ANSI fallback character
        $rtf = '{\rtf1\ansi\ansicpg1252\cocoartf2761' . "\n"
             . '\cocoatextscaling0\cocoaplatform0{\fonttbl\f0\fnil\fcharset0 HelveticaNeue;}' . "\n"
             . '{\colortbl;\red255\green255\blue255;\red255\green255\blue255;}' . "\n"
             . '{\*\expandedcolortbl;;\csgray\c100000;}' . "\n"
             . '\pard\pardirnatural\qc\partightenfactor0' . "\n"
             . "\n"
             . '\f0\fs84 \cf2 \CocoaLigature0 Praise \u9899? Him}';

        $result = RtfExtractor::toPlainText($rtf);

        // \u9899 = Unicode codepoint 9899 (⚫)  - the ? is ANSI fallback, dropped
        $this->assertSame('Praise ⚫ Him', $result);
    }

    // ─── Stroke/outline formatting (real pattern) ───

    #[Test]
    public function extractsTextWithStrokeFormatting(): void
    {
        // Real pattern from all-songs: extra \outl0\strokewidth-40 \strokec3
        $rtf = '{\rtf1\ansi\ansicpg1252\cocoartf2761' . "\n"
             . '\cocoatextscaling0\cocoaplatform0{\fonttbl\f0\fnil\fcharset0 AvenirNext-Regular;}' . "\n"
             . '{\colortbl;\red255\green255\blue255;\red255\green255\blue255;\red30\green30\blue30;}' . "\n"
             . '{\*\expandedcolortbl;;\csgenericrgb\c100000\c100000\c100000;\csgenericrgb\c11818\c11807\c11716;}' . "\n"
             . '\pard\slleading-40\pardirnatural\qc\partightenfactor0' . "\n"
             . "\n"
             . '\f0\fs120 \cf2 \outl0\strokewidth-40 \strokec3 Hello World}';

        $result = RtfExtractor::toPlainText($rtf);

        $this->assertSame('Hello World', $result);
    }

    // ─── Non-RTF input passes through ───

    #[Test]
    public function nonRtfStringReturnedAsIs(): void
    {
        $this->assertSame('Just plain text', RtfExtractor::toPlainText('Just plain text'));
    }
}

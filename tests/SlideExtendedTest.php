<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\Group;
use ProPresenter\Parser\ProFileReader;
use ProPresenter\Parser\ProFileWriter;
use ProPresenter\Parser\Slide;
use ProPresenter\Parser\Song;

class SlideExtendedTest extends TestCase
{
    private const TEST_WITH_MACRO = __DIR__ . '/../doc/reference_samples/TestMitMakro.pro';
    private const TEST_WITH_MEDIA_AND_MACRO = __DIR__ . '/../doc/reference_samples/TestMitBildernUndMakro.pro';

    #[Test]
    public function testCopyrightSlideHasMacro(): void
    {
        $song = self::readSong(self::TEST_WITH_MACRO);
        $slide = self::getSlideByGroupName($song, 'COPYRIGHT', 0);

        $this->assertTrue($slide->hasMacro());
    }

    #[Test]
    public function testMacroNameAndUuid(): void
    {
        $song = self::readSong(self::TEST_WITH_MACRO);
        $slide = self::getSlideByGroupName($song, 'COPYRIGHT', 0);

        $this->assertSame('Lied 1.Folie', $slide->getMacroName());
        $this->assertSame('20C1DFDE-0FB6-49E5-B90C-E6608D427212', $slide->getMacroUuid());
    }

    #[Test]
    public function testMacroCollectionNameAndUuid(): void
    {
        $song = self::readSong(self::TEST_WITH_MACRO);
        $slide = self::getSlideByGroupName($song, 'COPYRIGHT', 0);

        $this->assertSame('--MAIN--', $slide->getMacroCollectionName());
        $this->assertSame('8D02FC57-83F8-4042-9B90-81C229728426', $slide->getMacroCollectionUuid());
    }

    #[Test]
    public function testRegularSlideHasNoMacro(): void
    {
        $song = self::readSong(self::TEST_WITH_MACRO);
        $slide = self::getSlideByGroupName($song, 'Verse 1', 0);

        $this->assertFalse($slide->hasMacro());
    }

    #[Test]
    public function testSetMacroOnSlide(): void
    {
        $song = self::readSong(self::TEST_WITH_MACRO);
        $slide = self::getSlideByGroupName($song, 'Verse 1', 0);

        $slide->setMacro('Macro Name', '11111111-2222-3333-4444-555555555555', 'Collection Name', 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE');

        $this->assertTrue($slide->hasMacro());
        $this->assertSame('Macro Name', $slide->getMacroName());
        $this->assertSame('11111111-2222-3333-4444-555555555555', $slide->getMacroUuid());
        $this->assertSame('Collection Name', $slide->getMacroCollectionName());
        $this->assertSame('AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE', $slide->getMacroCollectionUuid());
    }

    #[Test]
    public function testRemoveMacro(): void
    {
        $song = self::readSong(self::TEST_WITH_MACRO);
        $slide = self::getSlideByGroupName($song, 'Verse 1', 0);

        $slide->setMacro('Macro Name', '11111111-2222-3333-4444-555555555555');
        $this->assertTrue($slide->hasMacro());

        $slide->removeMacro();
        $this->assertFalse($slide->hasMacro());
    }

    #[Test]
    public function testSetAndRemoveMacroRoundTrip(): void
    {
        $song = self::readSong(self::TEST_WITH_MACRO);
        $slide = self::getSlideByGroupName($song, 'Verse 1', 0);

        $slide->setMacro('Round Trip Macro', 'AAAAAAAA-1111-2222-3333-BBBBBBBBBBBB', '--MAIN--', '8D02FC57-83F8-4042-9B90-81C229728426');

        $tempPath = sys_get_temp_dir() . '/propresenter-slide-extended-' . uniqid('', true) . '.pro';
        try {
            ProFileWriter::write($song, $tempPath);

            $readBack = ProFileReader::read($tempPath);
            $readBackSlide = self::getSlideByGroupName($readBack, 'Verse 1', 0);

            $this->assertTrue($readBackSlide->hasMacro());
            $this->assertSame('Round Trip Macro', $readBackSlide->getMacroName());
            $this->assertSame('AAAAAAAA-1111-2222-3333-BBBBBBBBBBBB', $readBackSlide->getMacroUuid());

            $readBackSlide->removeMacro();
            $this->assertFalse($readBackSlide->hasMacro());
        } finally {
            @unlink($tempPath);
        }
    }

    #[Test]
    public function testImageSlideHasMedia(): void
    {
        $song = self::readSong(self::TEST_WITH_MEDIA_AND_MACRO);
        $slide = $song->getSlides()[0];

        $this->assertTrue($slide->hasMedia());
    }

    #[Test]
    public function testMediaUrlAndFormat(): void
    {
        $song = self::readSong(self::TEST_WITH_MEDIA_AND_MACRO);
        $slide = $song->getSlides()[0];

        $this->assertStringContainsString('file://', (string) $slide->getMediaUrl());
        $this->assertSame('JPG', $slide->getMediaFormat());
    }

    #[Test]
    public function testImageSlideHasNoText(): void
    {
        $song = self::readSong(self::TEST_WITH_MEDIA_AND_MACRO);
        $slide = $song->getSlides()[0];

        $this->assertSame('', $slide->getPlainText());
    }

    #[Test]
    public function testSlideWithLabel(): void
    {
        $song = self::readSong(self::TEST_WITH_MEDIA_AND_MACRO);
        $slide = $song->getSlides()[1];

        $this->assertSame('sample-image.jpg', $slide->getLabel());
    }

    #[Test]
    public function testSlideWithoutLabel(): void
    {
        $song = self::readSong(self::TEST_WITH_MEDIA_AND_MACRO);
        $slide = $song->getSlides()[0];

        $this->assertSame('', $slide->getLabel());
    }

    #[Test]
    public function testImageSlideWithMacro(): void
    {
        $song = self::readSong(self::TEST_WITH_MEDIA_AND_MACRO);
        $slide = $song->getSlides()[1];

        $this->assertTrue($slide->hasMacro());
        $this->assertSame('1:1 - Beamer & Stream', $slide->getMacroName());
    }

    #[Test]
    public function testSetLabel(): void
    {
        $song = self::readSong(self::TEST_WITH_MEDIA_AND_MACRO);
        $slide = $song->getSlides()[1];

        $slide->setLabel('New Label');

        $this->assertSame('New Label', $slide->getLabel());
    }

    private static function readSong(string $path): Song
    {
        if (!file_exists($path)) {
            self::markTestSkipped('Reference file not found: ' . $path);
        }

        return ProFileReader::read($path);
    }

    private static function getSlideByGroupName(Song $song, string $groupName, int $slideIndex): Slide
    {
        $group = $song->getGroupByName($groupName);
        self::assertInstanceOf(Group::class, $group, 'Group not found: ' . $groupName);

        $slides = $song->getSlidesForGroup($group);
        self::assertArrayHasKey($slideIndex, $slides, 'Slide index not found in group: ' . $groupName);

        return $slides[$slideIndex];
    }
}

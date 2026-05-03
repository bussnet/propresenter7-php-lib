<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\PresentationBundle;
use ProPresenter\Parser\ProBundleReader;
use ProPresenter\Parser\ProBundleWriter;
use ProPresenter\Parser\ProFileGenerator;
use ProPresenter\Parser\ProFileReader;
use ProPresenter\Parser\ProFileWriter;

class ProFileGeneratorTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/propresenter-generator-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->tmpDir)) {
            return;
        }

        foreach (scandir($this->tmpDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            @unlink($this->tmpDir . '/' . $entry);
        }

        @rmdir($this->tmpDir);
    }

    #[Test]
    public function testGenerateCreatesValidSong(): void
    {
        $song = ProFileGenerator::generate(
            'Simple Song',
            [
                [
                    'name' => 'Verse 1',
                    'color' => [0.1, 0.2, 0.3, 1.0],
                    'slides' => [
                        ['text' => 'Hello World'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['Verse 1']],
            ],
        );

        $this->assertSame('Simple Song', $song->getName());
        $this->assertCount(1, $song->getGroups());
        $this->assertCount(1, $song->getSlides());
        $this->assertSame('Hello World', $song->getSlides()[0]->getPlainText());

        $arrangement = $song->getArrangementByName('normal');
        $this->assertNotNull($arrangement);
        $groups = $song->getGroupsForArrangement($arrangement);
        $this->assertCount(1, $groups);
        $this->assertSame('Verse 1', $groups[0]->getName());

        // Verify HotKey is present on Group
        $this->assertNotNull($groups[0]->getHotKey());

        // Verify HotKey is present on Cue
        $cues = $song->getPresentation()->getCues();
        $this->assertCount(1, $cues);
        $this->assertNotNull($cues[0]->getHotKey());

        // Verify UUID is uppercase
        $this->assertMatchesRegularExpression('/^[A-F0-9]{8}-[A-F0-9]{4}-4[A-F0-9]{3}-[89AB][A-F0-9]{3}-[A-F0-9]{12}$/', $song->getUuid());
    }

    #[Test]
    public function testGenerateCreatesSeparatePlatformAndApplicationVersions(): void
    {
        $song = ProFileGenerator::generate(
            'Version Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        ['text' => 'Test'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
        );

        $appInfo = $song->getPresentation()->getApplicationInfo();
        $this->assertNotNull($appInfo);

        // Verify platform version
        $platformVersion = $appInfo->getPlatformVersion();
        $this->assertNotNull($platformVersion);
        $this->assertSame(14, $platformVersion->getMajorVersion());
        $this->assertSame(8, $platformVersion->getMinorVersion());
        $this->assertSame(3, $platformVersion->getPatchVersion());
        $this->assertSame('', $platformVersion->getBuild());

        // Verify application version
        $applicationVersion = $appInfo->getApplicationVersion();
        $this->assertNotNull($applicationVersion);
        $this->assertSame(20, $applicationVersion->getMajorVersion());
        $this->assertSame('335544354', $applicationVersion->getBuild());

        // Verify they are different objects
        $this->assertNotSame($platformVersion, $applicationVersion);
    }

    #[Test]
    public function testGenerateWithMultipleGroupsAndArrangements(): void
    {
        $song = ProFileGenerator::generate(
            'Multi Song',
            [
                [
                    'name' => 'Verse 1',
                    'color' => [0.1, 0.2, 0.3, 1.0],
                    'slides' => [
                        ['text' => 'V1.1'],
                        ['text' => 'V1.2'],
                    ],
                ],
                [
                    'name' => 'Chorus',
                    'color' => [0.4, 0.5, 0.6, 1.0],
                    'slides' => [
                        ['text' => 'C1'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['Verse 1', 'Chorus']],
                ['name' => 'short', 'groupNames' => ['Chorus']],
            ],
        );

        $this->assertSame(['Verse 1', 'Chorus'], array_map(fn ($group) => $group->getName(), $song->getGroups()));

        $verse1 = $song->getGroupByName('Verse 1');
        $this->assertNotNull($verse1);
        $verseSlides = $song->getSlidesForGroup($verse1);
        $this->assertSame(['V1.1', 'V1.2'], array_map(fn ($slide) => $slide->getPlainText(), $verseSlides));

        $chorus = $song->getGroupByName('Chorus');
        $this->assertNotNull($chorus);
        $chorusSlides = $song->getSlidesForGroup($chorus);
        $this->assertSame(['C1'], array_map(fn ($slide) => $slide->getPlainText(), $chorusSlides));

        $normal = $song->getArrangementByName('normal');
        $this->assertNotNull($normal);
        $this->assertSame(
            ['Verse 1', 'Chorus'],
            array_map(fn ($group) => $group->getName(), $song->getGroupsForArrangement($normal)),
        );

        $short = $song->getArrangementByName('short');
        $this->assertNotNull($short);
        $this->assertSame(
            ['Chorus'],
            array_map(fn ($group) => $group->getName(), $song->getGroupsForArrangement($short)),
        );
    }

    #[Test]
    public function testGenerateWithTranslation(): void
    {
        $song = ProFileGenerator::generate(
            'Translation Song',
            [
                [
                    'name' => 'Verse 1',
                    'color' => [0.1, 0.2, 0.3, 1.0],
                    'slides' => [
                        ['text' => 'Original', 'translation' => 'Translated'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['Verse 1']],
            ],
        );

        $slide = $song->getSlides()[0];
        $this->assertTrue($slide->hasTranslation());
        $this->assertSame('Translated', $slide->getTranslation()?->getPlainText());
    }

    #[Test]
    public function testGenerateWithCcliMetadata(): void
    {
        $song = ProFileGenerator::generate(
            'CCLI Song',
            [
                [
                    'name' => 'Verse 1',
                    'color' => [0.1, 0.2, 0.3, 1.0],
                    'slides' => [
                        ['text' => 'Line'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['Verse 1']],
            ],
            [
                'author' => 'Author Name',
                'song_title' => 'Song Title',
                'publisher' => 'Publisher Name',
                'copyright_year' => 2024,
                'song_number' => 12345,
                'display' => true,
                'artist_credits' => 'Artist Credits',
                'album' => 'Album Name',
            ],
        );

        $this->assertSame('Author Name', $song->getCcliAuthor());
        $this->assertSame('Song Title', $song->getCcliSongTitle());
        $this->assertSame('Publisher Name', $song->getCcliPublisher());
        $this->assertSame(2024, $song->getCcliCopyrightYear());
        $this->assertSame(12345, $song->getCcliSongNumber());
        $this->assertTrue($song->getCcliDisplay());
        $this->assertSame('Artist Credits', $song->getCcliArtistCredits());
        $this->assertSame('Album Name', $song->getCcliAlbum());
    }

    #[Test]
    public function testRoundTripFromTestPro(): void
    {
        $original = ProFileReader::read(__DIR__ . '/../doc/reference_samples/Test.pro');

        $groups = [];
        foreach ($original->getGroups() as $group) {
            $color = $group->getColor();
            $slides = [];
            foreach ($original->getSlidesForGroup($group) as $slide) {
                $slides[] = [
                    'text' => $slide->getPlainText(),
                    'translation' => $slide->hasTranslation() ? $slide->getTranslation()?->getPlainText() : null,
                ];
            }

            $groups[] = [
                'name' => $group->getName(),
                'color' => [
                    $color['r'] ?? 0.0,
                    $color['g'] ?? 0.0,
                    $color['b'] ?? 0.0,
                    $color['a'] ?? 1.0,
                ],
                'slides' => $slides,
            ];
        }

        $arrangements = [];
        foreach ($original->getArrangements() as $arrangement) {
            $arrangements[] = [
                'name' => $arrangement->getName(),
                'groupNames' => array_map(
                    fn ($group) => $group->getName(),
                    $original->getGroupsForArrangement($arrangement),
                ),
            ];
        }

        $ccli = [
            'author' => $original->getCcliAuthor(),
            'song_title' => $original->getCcliSongTitle(),
            'publisher' => $original->getCcliPublisher(),
            'copyright_year' => $original->getCcliCopyrightYear(),
            'song_number' => $original->getCcliSongNumber(),
            'display' => $original->getCcliDisplay(),
            'artist_credits' => $original->getCcliArtistCredits(),
            'album' => $original->getCcliAlbum(),
        ];

        $generated = ProFileGenerator::generate($original->getName(), $groups, $arrangements, $ccli);
        $filePath = $this->tmpDir . '/test-roundtrip.pro';
        ProFileWriter::write($generated, $filePath);
        $roundTrip = ProFileReader::read($filePath);

        $this->assertSame($original->getName(), $roundTrip->getName());

        $this->assertSame(
            array_map(fn ($group) => $group->getName(), $original->getGroups()),
            array_map(fn ($group) => $group->getName(), $roundTrip->getGroups()),
        );

        foreach ($original->getGroups() as $group) {
            $actualGroup = $roundTrip->getGroupByName($group->getName());
            $this->assertNotNull($actualGroup);

            $expectedSlides = $original->getSlidesForGroup($group);
            $actualSlides = $roundTrip->getSlidesForGroup($actualGroup);
            $this->assertCount(count($expectedSlides), $actualSlides);

            foreach ($expectedSlides as $index => $expectedSlide) {
                $actualSlide = $actualSlides[$index];
                $this->assertSame($expectedSlide->getPlainText(), $actualSlide->getPlainText());
                $this->assertSame($expectedSlide->hasTranslation(), $actualSlide->hasTranslation());
                if ($expectedSlide->hasTranslation()) {
                    $this->assertSame(
                        $expectedSlide->getTranslation()?->getPlainText(),
                        $actualSlide->getTranslation()?->getPlainText(),
                    );
                }
            }
        }

        $this->assertSame(
            array_map(fn ($arrangement) => $arrangement->getName(), $original->getArrangements()),
            array_map(fn ($arrangement) => $arrangement->getName(), $roundTrip->getArrangements()),
        );

        foreach ($original->getArrangements() as $arrangement) {
            $roundTripArrangement = $roundTrip->getArrangementByName($arrangement->getName());
            $this->assertNotNull($roundTripArrangement);

            $expectedNames = array_map(
                fn ($group) => $group->getName(),
                $original->getGroupsForArrangement($arrangement),
            );
            $actualNames = array_map(
                fn ($group) => $group->getName(),
                $roundTrip->getGroupsForArrangement($roundTripArrangement),
            );

            $this->assertSame($expectedNames, $actualNames);
        }
    }

    #[Test]
    public function testGenerateAndWriteCreatesFile(): void
    {
        $filePath = $this->tmpDir . '/generated.pro';

        ProFileGenerator::generateAndWrite(
            $filePath,
            'Write Song',
            [
                [
                    'name' => 'Verse 1',
                    'color' => [0.1, 0.2, 0.3, 1.0],
                    'slides' => [
                        ['text' => 'Line 1'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['Verse 1']],
            ],
        );

        $this->assertFileExists($filePath);

        $song = ProFileReader::read($filePath);
        $this->assertSame('Write Song', $song->getName());
    }

    #[Test]
    public function testGenerateWithMacro(): void
    {
        $song = ProFileGenerator::generate(
            'Macro Song',
            [
                [
                    'name' => 'Verse 1',
                    'color' => [0.1, 0.2, 0.3, 1.0],
                    'slides' => [
                        [
                            'text' => 'Line 1',
                            'macro' => [
                                'name' => 'Macro Name',
                                'uuid' => '11111111-2222-3333-4444-555555555555',
                                'collectionName' => '--MAIN--',
                                'collectionUuid' => '8D02FC57-83F8-4042-9B90-81C229728426',
                            ],
                        ],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['Verse 1']],
            ],
        );

        $slide = $song->getSlides()[0];
        $this->assertTrue($slide->hasMacro());
        $this->assertSame('Macro Name', $slide->getMacroName());
        $this->assertSame('11111111-2222-3333-4444-555555555555', $slide->getMacroUuid());
    }

    #[Test]
    public function testGenerateMediaSlide(): void
    {
        $song = ProFileGenerator::generate(
            'Media Song',
            [
                [
                    'name' => 'Verse 1',
                    'color' => [0.1, 0.2, 0.3, 1.0],
                    'slides' => [
                        [
                            'media' => 'file:///tmp/test-image.jpg',
                            'format' => 'JPG',
                        ],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['Verse 1']],
            ],
        );

        $slide = $song->getSlides()[0];
        $this->assertTrue($slide->hasMedia());
        $this->assertSame('file:///tmp/test-image.jpg', $slide->getMediaUrl());
        $this->assertSame('JPG', $slide->getMediaFormat());
        $this->assertSame('', $slide->getPlainText());
    }

    #[Test]
    public function testGenerateMediaSlideWithLabelAndMacro(): void
    {
        $song = ProFileGenerator::generate(
            'Media Macro Song',
            [
                [
                    'name' => 'Verse 1',
                    'color' => [0.1, 0.2, 0.3, 1.0],
                    'slides' => [
                        [
                            'media' => 'file:///tmp/test-image-2.jpg',
                            'format' => 'JPG',
                            'label' => 'Image Slide Label',
                            'macro' => [
                                'name' => 'Image Macro',
                                'uuid' => 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
                            ],
                        ],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['Verse 1']],
            ],
        );

        $slide = $song->getSlides()[0];
        $this->assertTrue($slide->hasMedia());
        $this->assertSame('Image Slide Label', $slide->getLabel());
        $this->assertTrue($slide->hasMacro());
        $this->assertSame('Image Macro', $slide->getMacroName());
        $this->assertSame('AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE', $slide->getMacroUuid());
    }

    #[Test]
    public function testGenerateAttributesAreDisabled(): void
    {
        $song = ProFileGenerator::generate(
            'Attributes Test',
            [
                [
                    'name' => 'Verse 1',
                    'color' => [0.1, 0.2, 0.3, 1.0],
                    'slides' => [
                        ['text' => 'Test Text'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['Verse 1']],
            ],
        );

        $slide = $song->getSlides()[0];
        $elements = $slide->getAllElements();
        $this->assertCount(1, $elements);

        $element = $elements[0];
        $graphicsElement = $element->getGraphicsElement();

        // Verify fill is present but disabled
        $this->assertNotNull($graphicsElement->getFill());
        $this->assertFalse($graphicsElement->getFill()->getEnable());

        // Verify stroke is present but disabled
        $this->assertNotNull($graphicsElement->getStroke());
        $this->assertFalse($graphicsElement->getStroke()->getEnable());

        // Verify shadow is present but disabled
        $this->assertNotNull($graphicsElement->getShadow());
        $this->assertFalse($graphicsElement->getShadow()->getEnable());

        // Verify feather is present but disabled
        $this->assertNotNull($graphicsElement->getFeather());
        $this->assertFalse($graphicsElement->getFeather()->getEnable());

        // Verify text scroller is present but disabled (should_scroll = false)
        // Access the raw SlideElement protobuf to get TextScroller
        $slideElements = $slide->getCue()->getActions()[0]->getSlide()->getPresentation()->getBaseSlide()->getElements();
        $this->assertCount(1, $slideElements);
        $textScroller = $slideElements[0]->getTextScroller();
        $this->assertNotNull($textScroller);
        $this->assertFalse($textScroller->getShouldScroll());
    }

    #[Test]
    public function testGenerateSelectsNormalArrangementWhenPresent(): void
    {
        $song = ProFileGenerator::generate(
            'SelectTest',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        ['text' => 'Hello'],
                    ],
                ],
            ],
            [
                ['name' => 'other', 'groupNames' => ['V1']],
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
        );

        $filePath = $this->tmpDir . '/select-test.pro';
        ProFileWriter::write($song, $filePath);
        $readSong = ProFileReader::read($filePath);
        $selectedUuid = $readSong->getSelectedArrangementUuid();

        // Find which arrangement has this UUID
        $selectedArrangement = null;
        foreach ($readSong->getArrangements() as $arrangement) {
            if ($arrangement->getUuid() === $selectedUuid) {
                $selectedArrangement = $arrangement;
                break;
            }
        }

        $this->assertNotNull($selectedArrangement);
        $this->assertSame('normal', $selectedArrangement->getName());
    }

    #[Test]
    public function testGenerateFallsBackToFirstArrangementWhenNoNormal(): void
    {
        $song = ProFileGenerator::generate(
            'FallbackTest',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        ['text' => 'Hello'],
                    ],
                ],
            ],
            [
                ['name' => 'custom', 'groupNames' => ['V1']],
            ],
        );

        $filePath = $this->tmpDir . '/fallback-test.pro';
        ProFileWriter::write($song, $filePath);
        $readSong = ProFileReader::read($filePath);
        $selectedUuid = $readSong->getSelectedArrangementUuid();

        // Find which arrangement has this UUID
        $selectedArrangement = null;
        foreach ($readSong->getArrangements() as $arrangement) {
            if ($arrangement->getUuid() === $selectedUuid) {
                $selectedArrangement = $arrangement;
                break;
            }
        }

        $this->assertNotNull($selectedArrangement);
        $this->assertSame('custom', $selectedArrangement->getName());
    }

    #[Test]
    public function testTranslatedSlideHasCorrectDualBounds(): void
    {
        $song = ProFileGenerator::generate(
            'TranslateTest',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        ['text' => 'Amazing Grace', 'translation' => 'Erstaunliche Gnade'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
        );

        $filePath = $this->tmpDir . '/translate-test.pro';
        ProFileWriter::write($song, $filePath);
        $readSong = ProFileReader::read($filePath);
        $slides = $readSong->getSlides();
        $elements = $slides[0]->getAllElements();

        $this->assertCount(2, $elements);
        $this->assertSame('Orginal', $elements[0]->getName());
        $this->assertSame('Deutsch', $elements[1]->getName());

        $bounds0 = $elements[0]->getGraphicsElement()->getBounds();
        $bounds1 = $elements[1]->getGraphicsElement()->getBounds();

        // Check heights differ and match expected values
        $this->assertEqualsWithDelta(182.946, $bounds0->getSize()->getHeight(), 0.01);
        $this->assertEqualsWithDelta(113.889, $bounds1->getSize()->getHeight(), 0.01);

        // Check Y positions
        $this->assertEqualsWithDelta(99.543, $bounds0->getOrigin()->getY(), 0.01);
        $this->assertEqualsWithDelta(303.166, $bounds1->getOrigin()->getY(), 0.01);
    }

    #[Test]
    public function testNonTranslatedSlideHasSingleFullBounds(): void
    {
        $song = ProFileGenerator::generate(
            'NoTranslateTest',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        ['text' => 'Amazing Grace'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
        );

        $filePath = $this->tmpDir . '/no-translate-test.pro';
        ProFileWriter::write($song, $filePath);
        $readSong = ProFileReader::read($filePath);
        $slides = $readSong->getSlides();
        $elements = $slides[0]->getAllElements();

        $this->assertCount(1, $elements);
        $this->assertSame('Orginal', $elements[0]->getName());

        $bounds = $elements[0]->getGraphicsElement()->getBounds();

        // Check full-size bounds
        $this->assertEqualsWithDelta(880, $bounds->getSize()->getHeight(), 0.01);
        $this->assertEqualsWithDelta(1620, $bounds->getSize()->getWidth(), 0.01);
        $this->assertEqualsWithDelta(100, $bounds->getOrigin()->getY(), 0.01);
        $this->assertEqualsWithDelta(150, $bounds->getOrigin()->getX(), 0.01);
    }

    #[Test]
    public function testGeneratePresentationFields(): void
    {
        $song = ProFileGenerator::generate(
            'Fields Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        ['text' => 'Test'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
        );

        $p = $song->getPresentation();
        $this->assertNotNull($p->getBackground());
        $this->assertNotNull($p->getChordChart());
        $this->assertNotNull($p->getCcli());
        $this->assertNotNull($p->getTimeline());
        $this->assertSame(300.0, $p->getTimeline()->getDuration());
    }

    #[Test]
    public function testGeneratePresentationFieldsWithEmptyCcli(): void
    {
        $song = ProFileGenerator::generate(
            'Empty CCLI Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        ['text' => 'Test'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
            [],
        );

        $p = $song->getPresentation();
        $this->assertNotNull($p->getCcli(), 'CCLI should be set even when empty array is passed');
    }

    #[Test]
    public function testGenerateSlideSizeAndChordChart(): void
    {
        $song = ProFileGenerator::generate(
            'Slide Size Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        ['text' => 'Test Slide'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
        );

        // Navigate to the first cue's slide action
        $cue = $song->getPresentation()->getCues()[0];
        $action = $cue->getActions()[0];  // slide action
        $baseSlide = $action->getSlide()->getPresentation()->getBaseSlide();

        // Verify slide size
        $this->assertNotNull($baseSlide->getSize());
        $this->assertSame(1920.0, $baseSlide->getSize()->getWidth());
        $this->assertSame(1080.0, $baseSlide->getSize()->getHeight());

        // Verify PresentationSlide has chordChart
        $presentationSlide = $action->getSlide()->getPresentation();
        $this->assertNotNull($presentationSlide->getChordChart());
    }

    #[Test]
    public function testBuildLocalRelativePathMappedDirectory(): void
    {
        // Test: Known directory mapping (Downloads) → ROOT_USER_DOWNLOADS with correct relative path
        $song = ProFileGenerator::generate(
            'Mapped Path Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        [
                            'media' => 'file:///Users/testuser/Downloads/test-image.jpg',
                            'format' => 'JPG',
                        ],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
        );

        // Navigate to the first slide's media action
        $cue = $song->getPresentation()->getCues()[0];
        $mediaAction = null;
        foreach ($cue->getActions() as $action) {
            if ($action->getType() === 2) {  // ACTION_TYPE_MEDIA = 2
                $mediaAction = $action;
                break;
            }
        }
        $this->assertNotNull($mediaAction);

        $url = $mediaAction->getMedia()->getElement()->getUrl();

        $this->assertNotNull($url);
        $this->assertNotNull($url->getLocal());
        $this->assertSame(4, $url->getLocal()->getRoot());  // ROOT_USER_DOWNLOADS = 4
        $this->assertSame('test-image.jpg', $url->getLocal()->getPath());
    }

    #[Test]
    public function testBuildLocalRelativePathUnmappedUserDirectory(): void
    {
        // Test: Unmapped user directory (AI) → ROOT_USER_HOME with full relative path
        $song = ProFileGenerator::generate(
            'Unmapped Path Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        [
                            'media' => 'file:///Users/sample-user/projects/propresenter-php/doc/reference_samples/Media/test.png',
                            'format' => 'PNG',
                        ],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
        );

        // Navigate to the first slide's media action
        $cue = $song->getPresentation()->getCues()[0];
        $mediaAction = null;
        foreach ($cue->getActions() as $action) {
            if ($action->getType() === 2) {  // ACTION_TYPE_MEDIA = 2
                $mediaAction = $action;
                break;
            }
        }
        $this->assertNotNull($mediaAction);

        $url = $mediaAction->getMedia()->getElement()->getUrl();

        $this->assertNotNull($url);
        $this->assertNotNull($url->getLocal());
        $this->assertSame(2, $url->getLocal()->getRoot());  // ROOT_USER_HOME = 2
        $this->assertSame('projects/propresenter-php/doc/reference_samples/Media/test.png', $url->getLocal()->getPath());
    }

    #[Test]
    public function testBuildLocalRelativePathNonUserPath(): void
    {
        // Test: Non-user path (tmp) → ROOT_BOOT_VOLUME with full path
        $song = ProFileGenerator::generate(
            'Non-User Path Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        [
                            'media' => 'file:///tmp/test-image.jpg',
                            'format' => 'JPG',
                        ],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
        );

        // Navigate to the first slide's media action
        $cue = $song->getPresentation()->getCues()[0];
        $mediaAction = null;
        foreach ($cue->getActions() as $action) {
            if ($action->getType() === 2) {  // ACTION_TYPE_MEDIA = 2
                $mediaAction = $action;
                break;
            }
        }
        $this->assertNotNull($mediaAction);

        $url = $mediaAction->getMedia()->getElement()->getUrl();

        $this->assertNotNull($url);
        $this->assertNotNull($url->getLocal());
        $this->assertSame(1, $url->getLocal()->getRoot());  // ROOT_BOOT_VOLUME = 1
        $this->assertSame('tmp/test-image.jpg', $url->getLocal()->getPath());
    }

    #[Test]
    public function testMediaActionHasNameFromFilename(): void
    {
        $song = ProFileGenerator::generate(
            'Media Name Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        [
                            'text' => 'x',
                            'media' => 'file:///Users/test/AI/Media/test-image.png',
                            'format' => 'png',
                            'mediaWidth' => 200,
                            'mediaHeight' => 150,
                        ],
                    ],
                ],
            ],
            [
                ['name' => 'n', 'groupNames' => ['V1']],
            ],
        );

        $mediaAction = $song->getPresentation()->getCues()[0]->getActions()[1];
        $this->assertSame('test-image', $mediaAction->getName());
    }

    #[Test]
    public function testMediaActionHasNameFromLabel(): void
    {
        $song = ProFileGenerator::generate(
            'Media Label Name Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        [
                            'text' => 'x',
                            'media' => 'file:///Users/test/AI/Media/test-image.png',
                            'format' => 'png',
                            'label' => 'My Custom Label',
                        ],
                    ],
                ],
            ],
            [
                ['name' => 'n', 'groupNames' => ['V1']],
            ],
        );

        $mediaAction = $song->getPresentation()->getCues()[0]->getActions()[1];
        $this->assertSame('My Custom Label', $mediaAction->getName());
    }

    #[Test]
    public function testMediaActionHasAudioOnMediaType(): void
    {
        $song = ProFileGenerator::generate(
            'Media Audio Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        [
                            'text' => 'x',
                            'media' => 'file:///Users/test/AI/Media/test-image.png',
                            'format' => 'png',
                        ],
                    ],
                ],
            ],
            [
                ['name' => 'n', 'groupNames' => ['V1']],
            ],
        );

        $mediaAction = $song->getPresentation()->getCues()[0]->getActions()[1];
        $media = $mediaAction->getMedia();
        $this->assertNotNull($media->getAudio());

        // Element should still be set (audio oneof doesn't clear element)
        $this->assertNotNull($media->getElement());
    }

    #[Test]
    public function testMediaActionHasImageDrawingAndFileProperties(): void
    {
        $song = ProFileGenerator::generate(
            'Media Image Props Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        [
                            'text' => 'x',
                            'media' => 'file:///Users/test/AI/Media/test-image.png',
                            'format' => 'png',
                            'mediaWidth' => 200,
                            'mediaHeight' => 150,
                        ],
                    ],
                ],
            ],
            [
                ['name' => 'n', 'groupNames' => ['V1']],
            ],
        );

        $mediaAction = $song->getPresentation()->getCues()[0]->getActions()[1];
        $image = $mediaAction->getMedia()->getElement()->getImage();
        $this->assertNotNull($image);

        // Verify drawing properties
        $this->assertNotNull($image->getDrawing());
        $this->assertSame(200.0, $image->getDrawing()->getNaturalSize()->getWidth());
        $this->assertSame(150.0, $image->getDrawing()->getNaturalSize()->getHeight());
        $this->assertNotNull($image->getDrawing()->getCustomImageBounds());
        $this->assertNotNull($image->getDrawing()->getCustomImageBounds()->getOrigin());
        $this->assertNotNull($image->getDrawing()->getCustomImageBounds()->getSize());
        $this->assertNotNull($image->getDrawing()->getCropInsets());
        $this->assertSame(1, $image->getDrawing()->getAlphaType()); // ALPHA_TYPE_STRAIGHT = 1

        // Verify file properties
        $this->assertNotNull($image->getFile());
        $this->assertNotNull($image->getFile()->getLocalUrl());
        $this->assertSame('file:///Users/test/AI/Media/test-image.png', $image->getFile()->getLocalUrl()->getAbsoluteString());
    }

    #[Test]
    public function testGeneratedProFileMatchesProPresenterNativeStructure(): void
    {
        $song = ProFileGenerator::generate(
            'TestBild',
            [[
                'name' => 'Verse 1',
                'color' => [0.0, 0.0, 0.0, 1.0],
                'slides' => [[
                    'label' => 'test-background',
                    'media' => 'file:///Users/sample-user/projects/propresenter-php/doc/reference_samples/Media/test-background.png',
                    'format' => 'png',
                    'mediaWidth' => 200,
                    'mediaHeight' => 150,
                ]],
            ]],
            [['name' => 'normal', 'groupNames' => ['Verse 1']]],
        );

        $p = $song->getPresentation();

        // Fix 1: UUIDs are uppercase
        $this->assertMatchesRegularExpression(
            '/^[A-F0-9]{8}-[A-F0-9]{4}-4[A-F0-9]{3}-[89AB][A-F0-9]{3}-[A-F0-9]{12}$/',
            $p->getUuid()->getString(),
            'Presentation UUID must be uppercase',
        );

        // Fix 2: Separate platform and application versions
        $appInfo = $p->getApplicationInfo();
        $this->assertSame(14, $appInfo->getPlatformVersion()->getMajorVersion());
        $this->assertSame(8, $appInfo->getPlatformVersion()->getMinorVersion());
        $this->assertSame(3, $appInfo->getPlatformVersion()->getPatchVersion());
        $this->assertSame(20, $appInfo->getApplicationVersion()->getMajorVersion());
        $this->assertSame('335544354', $appInfo->getApplicationVersion()->getBuild());

        // Fix 3a: Background present
        $this->assertNotNull($p->getBackground(), 'Background must be set');
        $this->assertSame(1.0, $p->getBackground()->getColor()->getAlpha());

        // Fix 3b: ChordChart on Presentation
        $this->assertNotNull($p->getChordChart(), 'Presentation chordChart must be set');

        // Fix 3c: CCLI always present (even with no data)
        $this->assertNotNull($p->getCcli(), 'CCLI must always be set');

        // Fix 3d: Timeline
        $this->assertNotNull($p->getTimeline(), 'Timeline must be set');
        $this->assertSame(300.0, $p->getTimeline()->getDuration());

        // Fix 4: HotKey on Group and Cue
        $this->assertNotNull($p->getCueGroups()[0]->getGroup()->getHotKey(), 'Group must have hotKey');
        $this->assertNotNull($p->getCues()[0]->getHotKey(), 'Cue must have hotKey');

        // Fix 5a: Slide size 1920x1080
        $slidePresentation = $p->getCues()[0]->getActions()[0]->getSlide()->getPresentation();
        $size = $slidePresentation->getBaseSlide()->getSize();
        $this->assertNotNull($size, 'Slide must have size');
        $this->assertSame(1920.0, $size->getWidth());
        $this->assertSame(1080.0, $size->getHeight());

        // Fix 5b: PresentationSlide chordChart
        $this->assertNotNull($slidePresentation->getChordChart(), 'PresentationSlide must have chordChart');

        // Fix 6: LOCAL PATH ROOT_USER_HOME for /Users/ paths
        $mediaUrl = $p->getCues()[0]->getActions()[1]->getMedia()->getElement()->getUrl();
        $this->assertSame(2, $mediaUrl->getLocal()->getRoot(), 'Must use ROOT_USER_HOME (2) for /Users/ paths');
        $this->assertSame('projects/propresenter-php/doc/reference_samples/Media/test-background.png', $mediaUrl->getLocal()->getPath());

        // Fix 7a: Media action name
        $mediaAction = $p->getCues()[0]->getActions()[1];
        $this->assertSame('test-background', $mediaAction->getName());

        // Fix 7b: MediaType.audio set (oneof discriminator)
        $this->assertNotNull($mediaAction->getMedia()->getAudio(), 'MediaType must have audio set');

        // Fix 7c: ImageTypeProperties with drawing and file
        $img = $mediaAction->getMedia()->getElement()->getImage();
        $this->assertNotNull($img->getDrawing(), 'ImageTypeProperties must have drawing');
        $this->assertSame(200.0, $img->getDrawing()->getNaturalSize()->getWidth());
        $this->assertSame(150.0, $img->getDrawing()->getNaturalSize()->getHeight());
        $this->assertNotNull($img->getDrawing()->getCustomImageBounds());
        $this->assertNotNull($img->getDrawing()->getCropInsets());
        $this->assertNotNull($img->getFile(), 'ImageTypeProperties must have file');
        $this->assertNotNull($img->getFile()->getLocalUrl());
        $this->assertSame(
            'file:///Users/sample-user/projects/propresenter-php/doc/reference_samples/Media/test-background.png',
            $img->getFile()->getLocalUrl()->getAbsoluteString(),
        );
    }

    #[Test]
    public function testBundleRoundTripWithGeneratedSong(): void
    {
        $song = ProFileGenerator::generate(
            'TestBild',
            [[
                'name' => 'Verse 1',
                'color' => [0.0, 0.0, 0.0, 1.0],
                'slides' => [[
                    'label' => 'test-background',
                    'media' => 'file:///Users/sample-user/projects/propresenter-php/doc/reference_samples/Media/test-background.png',
                    'format' => 'png',
                    'mediaWidth' => 200,
                    'mediaHeight' => 150,
                ]],
            ]],
            [['name' => 'normal', 'groupNames' => ['Verse 1']]],
        );

        $fakeImageBytes = "\x89PNG\r\n\x1a\n" . str_repeat("\x00", 100);

        $bundle = new PresentationBundle(
            $song,
            'TestBild.pro',
            ['test-background.png' => $fakeImageBytes],
        );

        $path = $this->tmpDir . '/TestBild.probundle';
        ProBundleWriter::write($bundle, $path);

        $this->assertFileExists($path);

        $read = ProBundleReader::read($path);
        $this->assertSame('TestBild', $read->getName());
        $this->assertSame('TestBild.pro', $read->getProFilename());
        $this->assertSame(1, $read->getMediaFileCount());
        $this->assertTrue($read->hasMediaFile('test-background.png'));

        // UUID preserved through serialization
        $this->assertSame(
            $song->getPresentation()->getUuid()->getString(),
            $read->getSong()->getPresentation()->getUuid()->getString(),
        );
    }
}

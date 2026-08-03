<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;
use ProPresenter\Parser\ProFileReader;
use ProPresenter\Parser\ProFileWriter;

class ProFileGeneratorClockTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/propresenter-clock-test-'.uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (! is_dir($this->tmpDir)) {
            return;
        }

        foreach (scandir($this->tmpDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            @unlink($this->tmpDir.'/'.$entry);
        }

        @rmdir($this->tmpDir);
    }

    private function clockGroups(): array
    {
        return [
            [
                'name' => 'V1',
                'color' => [0, 0, 0, 1],
                'slides' => [
                    [
                        'clock' => [
                            'format' => 'HH:mm',
                            'military24' => true,
                            'bounds' => ['x' => 60, 'y' => 40, 'width' => 600, 'height' => 200],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function normalArrangement(): array
    {
        return [
            ['name' => 'normal', 'groupNames' => ['V1']],
        ];
    }

    #[Test]
    public function test_clock_element_carries_datalink(): void
    {
        $song = ProFileGenerator::generate(
            'Countdown',
            $this->clockGroups(),
            $this->normalArrangement(),
        );

        $cue = $song->getPresentation()->getCues()[0];
        $elements = $cue->getActions()[0]->getSlide()->getPresentation()->getBaseSlide()->getElements();

        $this->assertCount(1, $elements);
        $slideElement = $elements[0];

        $dataLinks = $slideElement->getDataLinks();
        $this->assertCount(1, $dataLinks);

        $clockText = $dataLinks[0]->getClockText();
        $this->assertNotNull($clockText);
        $this->assertSame('HH:mm', $clockText->getClockFormatString());
        $this->assertNotNull($clockText->getFormat());
        $this->assertTrue($clockText->getFormat()->getMilitaryTimeEnabled());

        // Graphics element carries the bounds (upper-left origin) + RTF styling.
        $bounds = $slideElement->getElement()->getBounds();
        $this->assertSame(60.0, $bounds->getOrigin()->getX());
        $this->assertSame(40.0, $bounds->getOrigin()->getY());
        $this->assertSame(600.0, $bounds->getSize()->getWidth());
        $this->assertSame(200.0, $bounds->getSize()->getHeight());
    }

    #[Test]
    public function test_clock_survives_write_read_round_trip(): void
    {
        $song = ProFileGenerator::generate(
            'Countdown',
            $this->clockGroups(),
            $this->normalArrangement(),
        );

        $filePath = $this->tmpDir.'/clock-test.pro';
        ProFileWriter::write($song, $filePath);
        $readSong = ProFileReader::read($filePath);

        $slide = $readSong->getSlides()[0];
        $this->assertTrue($slide->hasClock());
        $this->assertSame('HH:mm', $slide->getClockFormat());
    }

    #[Test]
    public function test_clock_bounds_default_to_upper_left(): void
    {
        $song = ProFileGenerator::generate(
            'Countdown',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [['clock' => ['format' => 'HH:mm']]],
                ],
            ],
            $this->normalArrangement(),
        );

        $cue = $song->getPresentation()->getCues()[0];
        $element = $cue->getActions()[0]->getSlide()->getPresentation()->getBaseSlide()->getElements()[0];
        $bounds = $element->getElement()->getBounds();

        $this->assertSame(60.0, $bounds->getOrigin()->getX());
        $this->assertSame(40.0, $bounds->getOrigin()->getY());
        $this->assertTrue($element->getDataLinks()[0]->getClockText()->getFormat()->getMilitaryTimeEnabled());
    }
}

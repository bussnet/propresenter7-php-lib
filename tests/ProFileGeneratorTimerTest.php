<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;
use ProPresenter\Parser\ProFileReader;
use ProPresenter\Parser\ProFileWriter;
use Rv\Data\Graphics\Text\VerticalAlignment;
use Rv\Data\Timer\Format\Style as TimerFormatStyle;

class ProFileGeneratorTimerTest extends TestCase
{
    private const TIMER_UUID = '0E45D0AF-BCC2-4A31-BCFD-0F5A3358E225';

    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/propresenter-timer-test-'.uniqid();
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

    private function timerGroups(array $timerData): array
    {
        return [
            [
                'name' => 'V1',
                'color' => [0, 0, 0, 1],
                'slides' => [['timer' => $timerData]],
            ],
        ];
    }

    private function normalArrangement(): array
    {
        return [
            ['name' => 'normal', 'groupNames' => ['V1']],
        ];
    }

    private function firstElement(array $timerData): \Rv\Data\Slide\Element
    {
        $song = ProFileGenerator::generate(
            'Countdown',
            $this->timerGroups($timerData),
            $this->normalArrangement(),
        );

        $cue = $song->getPresentation()->getCues()[0];
        $elements = $cue->getActions()[0]->getSlide()->getPresentation()->getBaseSlide()->getElements();

        $this->assertCount(1, $elements);

        return $elements[0];
    }

    #[Test]
    public function test_timer_element_carries_datalink(): void
    {
        $element = $this->firstElement([
            'timerUuid' => self::TIMER_UUID,
            'timerName' => 'Gottesdienst',
            'format' => 'mm:ss',
            'bounds' => ['x' => 60, 'y' => 40, 'width' => 1800, 'height' => 1000],
        ]);

        $dataLinks = $element->getDataLinks();
        $this->assertCount(1, $dataLinks);

        $timerText = $dataLinks[0]->getTimerText();
        $this->assertNotNull($timerText);
        $this->assertSame('mm:ss', $timerText->getTimerFormatString());
        $this->assertSame('Gottesdienst', $timerText->getTimerName());
        $this->assertNotNull($timerText->getTimerUuid());
        $this->assertSame(self::TIMER_UUID, $timerText->getTimerUuid()->getString());

        $bounds = $element->getElement()->getBounds();
        $this->assertSame(60.0, $bounds->getOrigin()->getX());
        $this->assertSame(40.0, $bounds->getOrigin()->getY());
        $this->assertSame(1800.0, $bounds->getSize()->getWidth());
        $this->assertSame(1000.0, $bounds->getSize()->getHeight());
    }

    #[Test]
    public function test_timer_format_string_drives_structured_format(): void
    {
        $timerText = $this->firstElement(['timerName' => 'Countdown', 'format' => 'mm:ss'])
            ->getDataLinks()[0]->getTimerText();

        $format = $timerText->getTimerFormat();
        $this->assertNotNull($format);
        $this->assertSame(TimerFormatStyle::STYLE_REMOVE_SHORT, $format->getHour());
        $this->assertSame(TimerFormatStyle::STYLE_SHORT, $format->getMinute());
        $this->assertSame(TimerFormatStyle::STYLE_SHORT, $format->getSecond());
        $this->assertSame(TimerFormatStyle::STYLE_REMOVE_SHORT, $format->getMillisecond());
        $this->assertTrue($format->getIs24HourTime());
        $this->assertFalse($format->getIsWallClockTime());
    }

    #[Test]
    public function test_timer_format_string_with_hours(): void
    {
        $format = $this->firstElement(['format' => 'HH:mm:ss'])
            ->getDataLinks()[0]->getTimerText()->getTimerFormat();

        $this->assertSame(TimerFormatStyle::STYLE_SHORT, $format->getHour());
        $this->assertSame(TimerFormatStyle::STYLE_SHORT, $format->getMinute());
        $this->assertSame(TimerFormatStyle::STYLE_SHORT, $format->getSecond());
    }

    #[Test]
    public function test_timer_accepts_format_string_alias(): void
    {
        $timerText = $this->firstElement(['formatString' => 'HH:mm'])
            ->getDataLinks()[0]->getTimerText();

        $this->assertSame('HH:mm', $timerText->getTimerFormatString());
    }

    #[Test]
    public function test_timer_defaults_are_applied(): void
    {
        $element = $this->firstElement([]);

        $bounds = $element->getElement()->getBounds();
        $this->assertSame(60.0, $bounds->getOrigin()->getX());
        $this->assertSame(40.0, $bounds->getOrigin()->getY());
        $this->assertSame(1800.0, $bounds->getSize()->getWidth());
        $this->assertSame(1000.0, $bounds->getSize()->getHeight());

        $this->assertSame('Timer', $element->getElement()->getName());
        $this->assertSame('mm:ss', $element->getDataLinks()[0]->getTimerText()->getTimerFormatString());
        $this->assertSame('', $element->getDataLinks()[0]->getTimerText()->getTimerName());
        $this->assertNull($element->getDataLinks()[0]->getTimerText()->getTimerUuid());
    }

    #[Test]
    public function test_timer_style_options_are_rendered_into_rtf(): void
    {
        $element = $this->firstElement([
            'format' => 'mm:ss',
            'text' => '10:00',
            'style' => [
                'fontName' => 'Impact',
                'fontSize' => 200,
                'bold' => true,
                'color' => [255, 200, 0],
                'align' => 'left',
                'verticalAlign' => 'top',
            ],
        ]);

        $rtf = $element->getElement()->getText()->getRtfData();
        $this->assertStringContainsString('Impact;', $rtf);
        $this->assertStringContainsString('\fs400', $rtf);
        $this->assertStringContainsString('\b ', $rtf);
        $this->assertStringContainsString('\red255\green200\blue0', $rtf);
        $this->assertStringContainsString('\ql', $rtf);
        $this->assertStringContainsString('10:00', $rtf);

        $this->assertSame(
            VerticalAlignment::VERTICAL_ALIGNMENT_TOP,
            $element->getElement()->getText()->getVerticalAlignment(),
        );
    }

    #[Test]
    public function test_timer_style_accepts_float_colors(): void
    {
        $element = $this->firstElement([
            'style' => ['color' => [1.0, 0.0, 0.5]],
        ]);

        $rtf = $element->getElement()->getText()->getRtfData();
        $this->assertStringContainsString('\red255\green0\blue128', $rtf);
    }

    #[Test]
    public function test_timer_without_style_keeps_default_rtf_template(): void
    {
        $withoutStyle = $this->firstElement(['text' => '00:00'])->getElement()->getText()->getRtfData();
        $clockRtf = ProFileGenerator::generate(
            'Countdown',
            [[
                'name' => 'V1',
                'color' => [0, 0, 0, 1],
                'slides' => [['clock' => ['format' => 'HH:mm']]],
            ]],
            $this->normalArrangement(),
        )->getPresentation()->getCues()[0]
            ->getActions()[0]->getSlide()->getPresentation()->getBaseSlide()
            ->getElements()[0]->getElement()->getText()->getRtfData();

        $this->assertSame($clockRtf, $withoutStyle);
    }

    #[Test]
    public function test_timer_survives_write_read_round_trip(): void
    {
        $song = ProFileGenerator::generate(
            'Countdown',
            $this->timerGroups([
                'timerUuid' => self::TIMER_UUID,
                'timerName' => 'Gottesdienst',
                'format' => 'mm:ss',
            ]),
            $this->normalArrangement(),
        );

        $filePath = $this->tmpDir.'/timer-test.pro';
        ProFileWriter::write($song, $filePath);
        $readSong = ProFileReader::read($filePath);

        $slide = $readSong->getSlides()[0];
        $this->assertTrue($slide->hasTimer());
        $this->assertFalse($slide->hasClock());
        $this->assertSame('mm:ss', $slide->getTimerFormat());
        $this->assertSame('Gottesdienst', $slide->getTimerName());
        $this->assertSame(self::TIMER_UUID, $slide->getTimerUuid());
    }

    #[Test]
    public function test_slide_without_timer_reports_no_timer(): void
    {
        $song = ProFileGenerator::generate(
            'Plain',
            [[
                'name' => 'V1',
                'color' => [0, 0, 0, 1],
                'slides' => [['text' => 'Hello']],
            ]],
            $this->normalArrangement(),
        );

        $filePath = $this->tmpDir.'/plain-test.pro';
        ProFileWriter::write($song, $filePath);
        $readSong = ProFileReader::read($filePath);

        $slide = $readSong->getSlides()[0];
        $this->assertFalse($slide->hasTimer());
        $this->assertNull($slide->getTimerFormat());
        $this->assertNull($slide->getTimerName());
        $this->assertNull($slide->getTimerUuid());
    }
}

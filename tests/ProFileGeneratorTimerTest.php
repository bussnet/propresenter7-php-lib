<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;
use ProPresenter\Parser\ProFileReader;
use ProPresenter\Parser\ProFileWriter;
use Rv\Data\Graphics\Text\VerticalAlignment;
use Rv\Data\Slide\Element\DataLink\VisibilityLink\Condition\TimerVisibility\TimerVisibilityCriterion;
use Rv\Data\Slide\Element\DataLink\VisibilityLink\VisibilityCriterion;
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
        $this->assertSame('${timer}', $timerText->getTimerFormatString());
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

        // mm:ss ground truth from real ProPresenter files:
        // h=0 (NONE), m=2 (LONG), s=2 (LONG), ms=0 (NONE), 24h=false.
        $format = $timerText->getTimerFormat();
        $this->assertNotNull($format);
        $this->assertSame(TimerFormatStyle::STYE_NONE, $format->getHour());
        $this->assertSame(TimerFormatStyle::STYLE_LONG, $format->getMinute());
        $this->assertSame(TimerFormatStyle::STYLE_LONG, $format->getSecond());
        $this->assertSame(TimerFormatStyle::STYE_NONE, $format->getMillisecond());
        $this->assertFalse($format->getIs24HourTime());
        $this->assertFalse($format->getIsWallClockTime());
        $this->assertFalse($format->getShowMillisecondsUnderMinuteOnly());
    }

    #[Test]
    public function test_timer_format_string_with_hours(): void
    {
        $format = $this->firstElement(['format' => 'hh:mm:ss'])
            ->getDataLinks()[0]->getTimerText()->getTimerFormat();

        $this->assertSame(TimerFormatStyle::STYLE_LONG, $format->getHour());
        $this->assertSame(TimerFormatStyle::STYLE_LONG, $format->getMinute());
        $this->assertSame(TimerFormatStyle::STYLE_LONG, $format->getSecond());
        $this->assertSame(TimerFormatStyle::STYE_NONE, $format->getMillisecond());
    }

    #[Test]
    public function test_timer_format_string_with_milliseconds(): void
    {
        $format = $this->firstElement(['format' => 'mm:ss.S'])
            ->getDataLinks()[0]->getTimerText()->getTimerFormat();

        $this->assertSame(TimerFormatStyle::STYE_NONE, $format->getHour());
        $this->assertSame(TimerFormatStyle::STYLE_LONG, $format->getMinute());
        $this->assertSame(TimerFormatStyle::STYLE_LONG, $format->getSecond());
        $this->assertSame(TimerFormatStyle::STYLE_LONG, $format->getMillisecond());
    }

    #[Test]
    public function test_timer_format_string_is_always_the_template_token(): void
    {
        foreach (['mm:ss', 'hh:mm:ss', 'ss', 'mm:ss.S'] as $formatString) {
            $timerText = $this->firstElement(['format' => $formatString])
                ->getDataLinks()[0]->getTimerText();

            $this->assertSame('${timer}', $timerText->getTimerFormatString(), $formatString);
        }
    }

    #[Test]
    public function test_timer_rtf_body_never_carries_the_format_pattern(): void
    {
        foreach (['mm:ss', 'hh:mm:ss', 'mm:ss.S'] as $formatString) {
            $rtf = $this->firstElement(['format' => $formatString])
                ->getElement()->getText()->getRtfData();

            $this->assertStringNotContainsString($formatString, $rtf, $formatString);
            $this->assertStringNotContainsString('${timer}', $rtf, $formatString);
        }
    }

    #[Test]
    public function test_timer_accepts_format_string_alias(): void
    {
        $format = $this->firstElement(['formatString' => 'hh:mm'])
            ->getDataLinks()[0]->getTimerText()->getTimerFormat();

        $this->assertSame(TimerFormatStyle::STYLE_LONG, $format->getHour());
        $this->assertSame(TimerFormatStyle::STYLE_LONG, $format->getMinute());
        $this->assertSame(TimerFormatStyle::STYE_NONE, $format->getSecond());
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
        $this->assertSame('${timer}', $element->getDataLinks()[0]->getTimerText()->getTimerFormatString());
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
        $this->assertSame('${timer}', $slide->getTimerFormat());
        $this->assertSame(TimerFormatStyle::STYE_NONE, $slide->getTimerFormatMessage()->getHour());
        $this->assertSame(TimerFormatStyle::STYLE_LONG, $slide->getTimerFormatMessage()->getMinute());
        $this->assertSame(TimerFormatStyle::STYLE_LONG, $slide->getTimerFormatMessage()->getSecond());
        $this->assertSame('Gottesdienst', $slide->getTimerName());
        $this->assertSame(self::TIMER_UUID, $slide->getTimerUuid());
    }

    #[Test]
    public function test_timer_without_visible_when_carries_only_the_timer_datalink(): void
    {
        $element = $this->firstElement(['timerUuid' => self::TIMER_UUID, 'format' => 'mm:ss']);

        $this->assertCount(1, $element->getDataLinks());
        $this->assertNull($element->getDataLinks()[0]->getVisibilityLink());
    }

    #[Test]
    public function test_timer_visible_when_has_time_remaining_emits_visibility_link(): void
    {
        $element = $this->firstElement([
            'timerUuid' => self::TIMER_UUID,
            'timerName' => 'Gottesdienst',
            'format' => 'mm:ss',
            'visibleWhen' => 'hasTimeRemaining',
        ]);

        $dataLinks = $element->getDataLinks();
        $this->assertCount(2, $dataLinks);
        $this->assertNotNull($dataLinks[0]->getTimerText());

        $visibilityLink = $dataLinks[1]->getVisibilityLink();
        $this->assertNotNull($visibilityLink);
        $this->assertSame(VisibilityCriterion::VISIBILITY_CRITERION_ALL, $visibilityLink->getVisibilityCriterion());

        $conditions = $visibilityLink->getConditions();
        $this->assertCount(1, $conditions);

        $timerVisibility = $conditions[0]->getTimerVisibility();
        $this->assertNotNull($timerVisibility);
        $this->assertSame(
            TimerVisibilityCriterion::TIMER_VISIBILITY_CRITERION_HAS_TIME_REMAINING,
            $timerVisibility->getVisibilityCriterion(),
        );
        $this->assertSame('Gottesdienst', $timerVisibility->getTimerName());
        $this->assertSame(self::TIMER_UUID, $timerVisibility->getTimerUuid()->getString());
    }

    #[Test]
    public function test_timer_visibility_criteria_are_mapped(): void
    {
        $cases = [
            'hasTimeRemaining' => TimerVisibilityCriterion::TIMER_VISIBILITY_CRITERION_HAS_TIME_REMAINING,
            'hasExpired' => TimerVisibilityCriterion::TIMER_VISIBILITY_CRITERION_HAS_EXPIRED,
            'isRunning' => TimerVisibilityCriterion::TIMER_VISIBILITY_CRITERION_IS_RUNNING,
            'notRunning' => TimerVisibilityCriterion::TIMER_VISIBILITY_CRITERION_NOT_RUNNING,
        ];

        foreach ($cases as $visibleWhen => $expected) {
            $element = $this->firstElement(['visibleWhen' => $visibleWhen]);
            $timerVisibility = $element->getDataLinks()[1]->getVisibilityLink()->getConditions()[0]->getTimerVisibility();

            $this->assertSame($expected, $timerVisibility->getVisibilityCriterion(), $visibleWhen);
        }
    }

    #[Test]
    public function test_unknown_visible_when_is_ignored(): void
    {
        $element = $this->firstElement(['visibleWhen' => 'someNonsense']);

        $this->assertCount(1, $element->getDataLinks());
    }

    #[Test]
    public function test_timer_visibility_survives_write_read_round_trip(): void
    {
        $song = ProFileGenerator::generate(
            'Countdown',
            $this->timerGroups([
                'timerUuid' => self::TIMER_UUID,
                'timerName' => 'Gottesdienst',
                'format' => 'mm:ss',
                'visibleWhen' => 'hasTimeRemaining',
            ]),
            $this->normalArrangement(),
        );

        $filePath = $this->tmpDir.'/timer-visibility-test.pro';
        ProFileWriter::write($song, $filePath);
        $readSong = ProFileReader::read($filePath);

        $slide = $readSong->getSlides()[0];
        $this->assertTrue($slide->hasTimer());
        $this->assertTrue($slide->hasTimerVisibilityCondition());
        $this->assertSame('hasTimeRemaining', $slide->getTimerVisibilityCriterion());
        $this->assertSame(self::TIMER_UUID, $slide->getTimerVisibilityTimerUuid());
    }

    #[Test]
    public function test_slide_without_visibility_condition_reports_none(): void
    {
        $song = ProFileGenerator::generate(
            'Countdown',
            $this->timerGroups(['timerUuid' => self::TIMER_UUID, 'format' => 'mm:ss']),
            $this->normalArrangement(),
        );

        $filePath = $this->tmpDir.'/timer-no-visibility-test.pro';
        ProFileWriter::write($song, $filePath);
        $readSong = ProFileReader::read($filePath);

        $slide = $readSong->getSlides()[0];
        $this->assertFalse($slide->hasTimerVisibilityCondition());
        $this->assertNull($slide->getTimerVisibilityCriterion());
        $this->assertNull($slide->getTimerVisibilityTimerUuid());
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

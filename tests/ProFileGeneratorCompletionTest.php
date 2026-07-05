<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;

class ProFileGeneratorCompletionTest extends TestCase
{
    private function baseGroups(array $slideData): array
    {
        return [
            [
                'name' => 'V1',
                'color' => [0, 0, 0, 1],
                'slides' => [$slideData],
            ],
        ];
    }

    private function baseArrangements(): array
    {
        return [
            ['name' => 'normal', 'groupNames' => ['V1']],
        ];
    }

    #[Test]
    public function test_cue_completion_advances_to_next_after_time(): void
    {
        $song = ProFileGenerator::generate(
            'Auto Advance',
            $this->baseGroups([
                'text' => 'Loop me',
                'completion' => ['time' => 10.0, 'target' => 'next'],
            ]),
            $this->baseArrangements(),
        );

        $cue = $song->getPresentation()->getCues()[0];

        // COMPLETION_ACTION_TYPE_AFTER_TIME = 3
        $this->assertSame(3, $cue->getCompletionActionType());
        $this->assertSame(10.0, $cue->getCompletionTime());
        // COMPLETION_TARGET_TYPE_NEXT = 1
        $this->assertSame(1, $cue->getCompletionTargetType());
    }

    #[Test]
    public function test_cue_completion_loops_to_first(): void
    {
        $song = ProFileGenerator::generate(
            'Loop To First',
            $this->baseGroups([
                'text' => 'Last slide',
                'completion' => ['time' => 10.0, 'target' => 'first'],
            ]),
            $this->baseArrangements(),
        );

        $cue = $song->getPresentation()->getCues()[0];

        $this->assertSame(3, $cue->getCompletionActionType());
        $this->assertSame(10.0, $cue->getCompletionTime());
        // COMPLETION_TARGET_TYPE_FIRST = 4
        $this->assertSame(4, $cue->getCompletionTargetType());
    }

    #[Test]
    public function test_dissolve_transition_applied_to_presentation(): void
    {
        $song = ProFileGenerator::generate(
            'Dissolve Song',
            $this->baseGroups(['text' => 'Fade']),
            $this->baseArrangements(),
            [],
            ['transition' => 'dissolve'],
        );

        $presentation = $song->getPresentation();
        $this->assertTrue($presentation->hasTransition());

        $transition = $presentation->getTransition();
        $this->assertSame(1.0, $transition->getDuration());

        $effect = $transition->getEffect();
        $this->assertNotNull($effect);
        $this->assertTrue($effect->getEnabled());
        $this->assertSame('Dissolve', $effect->getName());
        $this->assertSame('EC52A828-AD85-4602-B70C-1DEE7C904DB6', $effect->getRenderId());
        $this->assertSame('Dissolves', $effect->getCategory());
    }

    #[Test]
    public function test_dissolve_transition_honors_custom_duration(): void
    {
        $song = ProFileGenerator::generate(
            'Dissolve Slow',
            $this->baseGroups(['text' => 'Fade']),
            $this->baseArrangements(),
            [],
            ['transition' => 'dissolve', 'transitionDuration' => 2.5],
        );

        $this->assertSame(2.5, $song->getPresentation()->getTransition()->getDuration());
    }

    #[Test]
    public function test_backward_compat_no_options_leaves_defaults(): void
    {
        $song = ProFileGenerator::generate(
            'Plain Song',
            $this->baseGroups(['text' => 'Nothing special']),
            $this->baseArrangements(),
        );

        $presentation = $song->getPresentation();

        // No transition configured
        $this->assertFalse($presentation->hasTransition());

        // Cue completion left at default (0 / 0 / 0.0)
        $cue = $presentation->getCues()[0];
        $this->assertSame(0, $cue->getCompletionActionType());
        $this->assertSame(0, $cue->getCompletionTargetType());
        $this->assertSame(0.0, $cue->getCompletionTime());
    }
}

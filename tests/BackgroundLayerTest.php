<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;
use Rv\Data\Action;
use Rv\Data\Action\ActionType;
use Rv\Data\Action\LayerType;

class BackgroundLayerTest extends TestCase
{
    #[Test]
    public function generated_text_slide_can_have_background_layer_media(): void
    {
        $song = ProFileGenerator::generate(
            'Background Text Song',
            [[
                'name' => 'Verse 1',
                'color' => [0.1, 0.2, 0.3, 1.0],
                'slides' => [[
                    'text' => 'Text auf Folie',
                    'background' => [
                        'path' => 'file:///tmp/bg.jpg',
                        'format' => 'JPG',
                        'width' => 1920,
                        'height' => 1080,
                    ],
                ]],
            ]],
            [['name' => 'normal', 'groupNames' => ['Verse 1']]],
        );

        $slide = $song->getSlides()[0];
        $mediaActions = self::mediaActions($slide->getCue()->getActions());

        $this->assertCount(1, $slide->getTextElements());
        $this->assertSame('Text auf Folie', $slide->getPlainText());
        $this->assertCount(1, $mediaActions);
        $this->assertSame(LayerType::LAYER_TYPE_BACKGROUND, $mediaActions[0]->getMedia()->getLayerType());
        $this->assertSame('file:///tmp/bg.jpg', $mediaActions[0]->getMedia()->getElement()->getUrl()->getAbsoluteString());
        $this->assertSame('JPG', $mediaActions[0]->getMedia()->getElement()->getMetadata()->getFormat());
        $this->assertSame(1920.0, $mediaActions[0]->getMedia()->getElement()->getImage()->getDrawing()->getNaturalSize()->getWidth());
        $this->assertSame(1080.0, $mediaActions[0]->getMedia()->getElement()->getImage()->getDrawing()->getNaturalSize()->getHeight());
        $this->assertTrue($slide->hasBackgroundMedia());
        $this->assertSame('file:///tmp/bg.jpg', $slide->getBackgroundMediaUrl());
        $this->assertSame('JPG', $slide->getBackgroundMediaFormat());
    }

    #[Test]
    public function generated_image_only_slide_has_no_text_elements(): void
    {
        $song = ProFileGenerator::generate(
            'Image Only Background Song',
            [[
                'name' => 'Image',
                'color' => [0.1, 0.2, 0.3, 1.0],
                'slides' => [[
                    'text' => 'Dieser Text darf nicht erscheinen',
                    'imageOnly' => true,
                    'background' => [
                        'path' => 'file:///tmp/image-only.jpg',
                        'format' => 'JPG',
                        'width' => 1920,
                        'height' => 1080,
                    ],
                ]],
            ]],
            [['name' => 'normal', 'groupNames' => ['Image']]],
        );

        $slide = $song->getSlides()[0];
        $mediaActions = self::mediaActions($slide->getCue()->getActions());

        $this->assertCount(0, $slide->getTextElements());
        $this->assertSame('', $slide->getPlainText());
        $this->assertCount(1, $mediaActions);
        $this->assertSame(LayerType::LAYER_TYPE_BACKGROUND, $mediaActions[0]->getMedia()->getLayerType());
        $this->assertTrue($slide->hasBackgroundMedia());
        $this->assertSame('file:///tmp/image-only.jpg', $slide->getBackgroundMediaUrl());
    }

    #[Test]
    public function generated_slide_can_have_background_and_foreground_media_in_order(): void
    {
        $song = ProFileGenerator::generate(
            'Mixed Media Song',
            [[
                'name' => 'Verse 1',
                'color' => [0.1, 0.2, 0.3, 1.0],
                'slides' => [[
                    'text' => 'Text vor Bild',
                    'background' => [
                        'path' => 'file:///tmp/background.jpg',
                        'format' => 'JPG',
                        'width' => 1920,
                        'height' => 1080,
                    ],
                    'media' => 'file:///tmp/foreground.png',
                    'format' => 'PNG',
                    'mediaWidth' => 640,
                    'mediaHeight' => 360,
                ]],
            ]],
            [['name' => 'normal', 'groupNames' => ['Verse 1']]],
        );

        $slide = $song->getSlides()[0];
        $mediaActions = self::mediaActions($slide->getCue()->getActions());

        $this->assertCount(2, $mediaActions);
        $this->assertSame(LayerType::LAYER_TYPE_BACKGROUND, $mediaActions[0]->getMedia()->getLayerType());
        $this->assertSame(LayerType::LAYER_TYPE_FOREGROUND, $mediaActions[1]->getMedia()->getLayerType());
        $this->assertSame('file:///tmp/background.jpg', $mediaActions[0]->getMedia()->getElement()->getUrl()->getAbsoluteString());
        $this->assertSame('file:///tmp/foreground.png', $mediaActions[1]->getMedia()->getElement()->getUrl()->getAbsoluteString());
        $this->assertTrue($slide->hasBackgroundMedia());
        $this->assertTrue($slide->hasMedia());
        $this->assertSame('file:///tmp/background.jpg', $slide->getBackgroundMediaUrl());
        $this->assertSame('file:///tmp/foreground.png', $slide->getMediaUrl());
    }

    /**
     * @param  iterable<Action>  $actions
     * @return Action[]
     */
    private static function mediaActions(iterable $actions): array
    {
        $mediaActions = [];
        foreach ($actions as $action) {
            if ($action->getType() === ActionType::ACTION_TYPE_MEDIA) {
                $mediaActions[] = $action;
            }
        }

        return $mediaActions;
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;
use ProPresenter\Parser\ProFileReader;
use ProPresenter\Parser\ProFileWriter;
use ProPresenter\Parser\Slide;
use Rv\Data\Action\ActionType;
use Rv\Data\Action\LayerType;
use Rv\Data\Media\ScaleBehavior;
use Rv\Data\URL\LocalRelativePath\Root;

class ProFileGeneratorImageElementTest extends TestCase
{
    private string $tempDir = '';

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/pp-image-element-'.bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        if ($this->tempDir !== '' && is_dir($this->tempDir)) {
            foreach (glob($this->tempDir.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($this->tempDir);
        }
    }

    #[Test]
    public function image_element_is_the_first_slide_element(): void
    {
        $slide = self::firstSlide([
            'text' => 'Text auf dem Bild',
            'image' => ['path' => 'INFO_1.jpg', 'format' => 'JPG'],
        ]);

        $elements = self::slideElements($slide);

        $this->assertCount(2, $elements);

        $fill = $elements[0]->getElement()->getFill();
        $this->assertTrue($fill->getEnable());
        $this->assertNotNull($fill->getMedia());
        $this->assertTrue($fill->getMedia()->hasImage());
        $this->assertSame(0, $elements[0]->getInfo());
    }

    #[Test]
    public function text_element_comes_after_the_image_element(): void
    {
        $slide = self::firstSlide([
            'text' => 'Text auf dem Bild',
            'image' => ['path' => 'INFO_1.jpg'],
        ]);

        $elements = self::slideElements($slide);

        $this->assertNull($elements[0]->getElement()->getText());
        $this->assertNotNull($elements[1]->getElement()->getText());
        $this->assertSame('Orginal', $elements[1]->getElement()->getName());
        $this->assertSame('Text auf dem Bild', $slide->getPlainText());
    }

    #[Test]
    public function image_element_is_below_clock_and_timer_elements(): void
    {
        $slide = self::firstSlide([
            'image' => ['path' => 'COUNTDOWN.jpg'],
            'imageOnly' => true,
            'clock' => ['format' => 'HH:mm'],
            'timer' => ['format' => 'mm:ss', 'timerName' => 'Gottesdienst'],
        ]);

        $elements = self::slideElements($slide);

        $this->assertCount(3, $elements);
        $this->assertTrue($slide->hasImageElement());
        $this->assertNotNull($elements[0]->getElement()->getFill()->getMedia());
        $this->assertSame('Clock', $elements[1]->getElement()->getName());
        $this->assertSame('Timer', $elements[2]->getElement()->getName());
    }

    #[Test]
    public function image_element_defaults_to_full_canvas_fill(): void
    {
        $slide = self::firstSlide(['image' => ['path' => 'INFO_1.jpg']]);

        $element = self::slideElements($slide)[0]->getElement();
        $bounds = $element->getBounds();
        $drawing = $element->getFill()->getMedia()->getImage()->getDrawing();

        $this->assertSame(0.0, $bounds->getOrigin()->getX());
        $this->assertSame(0.0, $bounds->getOrigin()->getY());
        $this->assertSame(1920.0, $bounds->getSize()->getWidth());
        $this->assertSame(1080.0, $bounds->getSize()->getHeight());
        $this->assertSame(1.0, $element->getOpacity());
        $this->assertSame(ScaleBehavior::SCALE_BEHAVIOR_FILL, $drawing->getScaleBehavior());
        $this->assertSame(1920.0, $drawing->getNaturalSize()->getWidth());
        $this->assertSame(1080.0, $drawing->getNaturalSize()->getHeight());
        $this->assertSame('JPG', $slide->getImageElementFormat());
    }

    #[Test]
    public function image_element_honours_custom_bounds_scale_behavior_opacity_and_name(): void
    {
        $slide = self::firstSlide([
            'image' => [
                'path' => 'LOGO.png',
                'format' => 'PNG',
                'width' => 640,
                'height' => 360,
                'bounds' => ['x' => 100, 'y' => 50, 'width' => 800, 'height' => 450],
                'scaleBehavior' => 'fit',
                'opacity' => 0.5,
                'name' => 'Logo',
            ],
        ]);

        $element = self::slideElements($slide)[0]->getElement();
        $bounds = $element->getBounds();
        $drawing = $element->getFill()->getMedia()->getImage()->getDrawing();

        $this->assertSame('Logo', $element->getName());
        $this->assertSame(100.0, $bounds->getOrigin()->getX());
        $this->assertSame(50.0, $bounds->getOrigin()->getY());
        $this->assertSame(800.0, $bounds->getSize()->getWidth());
        $this->assertSame(450.0, $bounds->getSize()->getHeight());
        $this->assertSame(0.5, $element->getOpacity());
        $this->assertSame(ScaleBehavior::SCALE_BEHAVIOR_FIT, $drawing->getScaleBehavior());
        $this->assertSame(640.0, $drawing->getNaturalSize()->getWidth());
        $this->assertSame(360.0, $drawing->getNaturalSize()->getHeight());
        $this->assertSame('PNG', $slide->getImageElementFormat());
    }

    #[Test]
    public function stretch_scale_behavior_is_supported(): void
    {
        $slide = self::firstSlide(['image' => ['path' => 'BG.jpg', 'scaleBehavior' => 'stretch']]);

        $drawing = self::slideElements($slide)[0]->getElement()->getFill()->getMedia()->getImage()->getDrawing();

        $this->assertSame(ScaleBehavior::SCALE_BEHAVIOR_STRETCH, $drawing->getScaleBehavior());
    }

    #[Test]
    public function image_element_is_referenced_bundle_relative_by_bare_filename(): void
    {
        $slide = self::firstSlide(['image' => ['path' => '/absolute/path/to/INFO_1.jpg']]);

        $media = self::slideElements($slide)[0]->getElement()->getFill()->getMedia();

        $this->assertSame('INFO_1.jpg', $media->getUrl()->getAbsoluteString());
        $this->assertSame('INFO_1.jpg', $media->getUrl()->getLocal()->getPath());
        $this->assertSame(Root::ROOT_CURRENT_RESOURCE, $media->getUrl()->getLocal()->getRoot());
        $this->assertSame('INFO_1.jpg', $media->getImage()->getFile()->getLocalUrl()->getAbsoluteString());
        $this->assertSame('INFO_1.jpg', $slide->getImageElementUrl());
    }

    #[Test]
    public function image_only_slide_has_no_text_elements(): void
    {
        $slide = self::firstSlide([
            'text' => 'Dieser Text darf nicht erscheinen',
            'imageOnly' => true,
            'image' => ['path' => 'SERMON_1.jpg'],
        ]);

        $elements = self::slideElements($slide);

        $this->assertCount(1, $elements);
        $this->assertCount(0, $slide->getTextElements());
        $this->assertSame('', $slide->getPlainText());
        $this->assertTrue($slide->hasImageElement());
        $this->assertSame('SERMON_1.jpg', $slide->getImageElementUrl());
    }

    #[Test]
    public function slide_without_image_key_reports_no_image_element(): void
    {
        $slide = self::firstSlide(['text' => 'Nur Text']);

        $this->assertFalse($slide->hasImageElement());
        $this->assertNull($slide->getImageElementUrl());
        $this->assertNull($slide->getImageElementFormat());
        $this->assertCount(1, self::slideElements($slide));
    }

    #[Test]
    public function image_element_produces_no_media_action(): void
    {
        $slide = self::firstSlide(['text' => 'Text', 'image' => ['path' => 'INFO_1.jpg']]);

        $mediaActions = 0;
        foreach ($slide->getCue()->getActions() as $action) {
            if ($action->getType() === ActionType::ACTION_TYPE_MEDIA) {
                $mediaActions++;
            }
        }

        $this->assertSame(0, $mediaActions);
        $this->assertFalse($slide->hasBackgroundMedia());
        $this->assertFalse($slide->hasMedia());
    }

    #[Test]
    public function background_action_still_works_alongside_the_image_element(): void
    {
        $slide = self::firstSlide([
            'text' => 'Text',
            'image' => ['path' => 'INFO_1.jpg'],
            'background' => [
                'path' => 'BACKGROUND.jpg',
                'format' => 'JPG',
                'bundleRelative' => true,
            ],
        ]);

        $backgroundActions = [];
        foreach ($slide->getCue()->getActions() as $action) {
            if ($action->getType() === ActionType::ACTION_TYPE_MEDIA) {
                $backgroundActions[] = $action;
            }
        }

        $this->assertCount(1, $backgroundActions);
        $this->assertSame(LayerType::LAYER_TYPE_BACKGROUND, $backgroundActions[0]->getMedia()->getLayerType());
        $this->assertTrue($slide->hasBackgroundMedia());
        $this->assertSame('BACKGROUND.jpg', $slide->getBackgroundMediaUrl());

        // Both mechanisms coexist and stay independent.
        $this->assertTrue($slide->hasImageElement());
        $this->assertSame('INFO_1.jpg', $slide->getImageElementUrl());
        $this->assertSame('Text', $slide->getPlainText());
    }

    #[Test]
    public function background_only_slide_reports_no_image_element(): void
    {
        $slide = self::firstSlide([
            'text' => 'Text',
            'background' => ['path' => 'BACKGROUND.jpg', 'bundleRelative' => true],
        ]);

        $this->assertTrue($slide->hasBackgroundMedia());
        $this->assertFalse($slide->hasImageElement());
        $this->assertNull($slide->getImageElementUrl());
    }

    #[Test]
    public function image_element_survives_a_write_read_round_trip(): void
    {
        $song = ProFileGenerator::generate(
            'Image Element Round Trip',
            [[
                'name' => 'Info',
                'color' => [0.1, 0.2, 0.3, 1.0],
                'slides' => [
                    [
                        'text' => 'Text auf dem Bild',
                        'image' => ['path' => 'INFO_1.jpg', 'format' => 'JPG'],
                    ],
                    [
                        'imageOnly' => true,
                        'image' => ['path' => 'INFO_2.png', 'format' => 'PNG'],
                    ],
                    [
                        'text' => 'Nur Text',
                    ],
                ],
            ]],
            [['name' => 'normal', 'groupNames' => ['Info']]],
        );

        $path = $this->tempDir.'/image-element.pro';
        ProFileWriter::write($song, $path);

        $slides = ProFileReader::read($path)->getSlides();

        $this->assertTrue($slides[0]->hasImageElement());
        $this->assertSame('INFO_1.jpg', $slides[0]->getImageElementUrl());
        $this->assertSame('JPG', $slides[0]->getImageElementFormat());
        $this->assertSame('Text auf dem Bild', $slides[0]->getPlainText());
        $this->assertSame(2, count(self::slideElements($slides[0])));

        $this->assertTrue($slides[1]->hasImageElement());
        $this->assertSame('INFO_2.png', $slides[1]->getImageElementUrl());
        $this->assertSame('PNG', $slides[1]->getImageElementFormat());
        $this->assertCount(0, $slides[1]->getTextElements());

        $this->assertFalse($slides[2]->hasImageElement());
        $this->assertSame('Nur Text', $slides[2]->getPlainText());
    }

    private static function firstSlide(array $slideData): Slide
    {
        $song = ProFileGenerator::generate(
            'Image Element Song',
            [[
                'name' => 'Info',
                'color' => [0.1, 0.2, 0.3, 1.0],
                'slides' => [$slideData],
            ]],
            [['name' => 'normal', 'groupNames' => ['Info']]],
        );

        return $song->getSlides()[0];
    }

    /**
     * @return \Rv\Data\Slide\Element[]
     */
    private static function slideElements(Slide $slide): array
    {
        foreach ($slide->getCue()->getActions() as $action) {
            $baseSlide = $action->getSlide()?->getPresentation()?->getBaseSlide();
            if ($baseSlide !== null) {
                return iterator_to_array($baseSlide->getElements());
            }
        }

        return [];
    }
}

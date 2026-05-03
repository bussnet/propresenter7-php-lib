<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Action;
use Rv\Data\Action\ActionType;
use Rv\Data\Action\LayerType;
use Rv\Data\Action\MacroType;
use Rv\Data\Action\MediaType;
use Rv\Data\Action\MediaType\Audio;
use Rv\Data\Action\SlideType;
use Rv\Data\AlphaType;
use Rv\Data\ApplicationInfo;
use Rv\Data\ApplicationInfo\Application;
use Rv\Data\ApplicationInfo\Platform;
use Rv\Data\Color;
use Rv\Data\CollectionElementType;
use Rv\Data\Cue;
use Rv\Data\FileProperties;
use Rv\Data\Graphics\EdgeInsets;
use Rv\Data\Graphics\Element as GraphicsElement;
use Rv\Data\Graphics\Feather;
use Rv\Data\Graphics\Feather\Style as FeatherStyle;
use Rv\Data\Graphics\Fill;
use Rv\Data\Graphics\Path;
use Rv\Data\Graphics\Path\BezierPoint;
use Rv\Data\Graphics\Path\Shape;
use Rv\Data\Graphics\Path\Shape\Type as ShapeType;
use Rv\Data\Graphics\Point;
use Rv\Data\Graphics\Rect;
use Rv\Data\Graphics\Shadow;
use Rv\Data\Graphics\Shadow\Style as ShadowStyle;
use Rv\Data\Graphics\Size;
use Rv\Data\Graphics\Stroke;
use Rv\Data\Graphics\Stroke\Style as StrokeStyle;
use Rv\Data\Graphics\Text;
use Rv\Data\Graphics\Text\VerticalAlignment;
use Rv\Data\Group;
use Rv\Data\HotKey;
use Rv\Data\Media;
use Rv\Data\Media\DrawingProperties;
use Rv\Data\Media\ImageTypeProperties;
use Rv\Data\Media\Metadata;
use Rv\Data\Presentation;
use Rv\Data\Presentation\Arrangement;
use Rv\Data\Presentation\CCLI;
use Rv\Data\Presentation\CueGroup;
use Rv\Data\PresentationSlide;
use Rv\Data\Slide;
use Rv\Data\Slide\Element as SlideElement;
use Rv\Data\Slide\Element\TextScroller;
use Rv\Data\Background;
use Rv\Data\URL;
use Rv\Data\URL\LocalRelativePath;
use Rv\Data\URL\Platform as UrlPlatform;
use Rv\Data\UUID;
use Rv\Data\Version;
use Rv\Data\Presentation\Timeline;

final class ProFileGenerator
{
    public static function generate(
        string $name,
        array $groups,
        array $arrangements,
        array $ccli = [],
    ): Song {
        $presentation = new Presentation();
        $presentation->setApplicationInfo(self::buildApplicationInfo());
        $presentation->setUuid(self::newUuid());
        $presentation->setName($name);

        $cueGroups = [];
        $cues = [];
        $groupUuidsByName = [];

        foreach ($groups as $groupData) {
            $groupUuid = self::newUuidString();
            $groupUuidsByName[$groupData['name']] = $groupUuid;

            $group = new Group();
            $group->setUuid(self::uuidFromString($groupUuid));
            $group->setName($groupData['name']);
            $group->setColor(self::colorFromArray($groupData['color']));
            $group->setHotKey(new HotKey());

            $cueIdentifiers = [];
            foreach ($groupData['slides'] as $slideData) {
                $cueUuid = self::newUuidString();
                $cueIdentifiers[] = self::uuidFromString($cueUuid);
                $cues[] = self::buildCue($cueUuid, $slideData);
            }

            $cueGroup = new CueGroup();
            $cueGroup->setGroup($group);
            $cueGroup->setCueIdentifiers($cueIdentifiers);
            $cueGroups[] = $cueGroup;
        }

        $presentation->setCues($cues);
        $presentation->setCueGroups($cueGroups);

        $arrangementProtos = [];
        foreach ($arrangements as $arrangementData) {
            $arrangement = new Arrangement();
            $arrangement->setUuid(self::newUuid());
            $arrangement->setName($arrangementData['name']);

            $groupIdentifiers = [];
            foreach ($arrangementData['groupNames'] as $groupName) {
                if (!isset($groupUuidsByName[$groupName])) {
                    continue;
                }

                $groupIdentifiers[] = self::uuidFromString($groupUuidsByName[$groupName]);
            }

            $arrangement->setGroupIdentifiers($groupIdentifiers);
            $arrangementProtos[] = $arrangement;
        }

        $presentation->setArrangements($arrangementProtos);

        $selectedArrangement = null;
        foreach ($arrangementProtos as $arr) {
            if (strtolower($arr->getName()) === 'normal') {
                $selectedArrangement = $arr;
                break;
            }
        }
        $selectedArrangement = $selectedArrangement ?? ($arrangementProtos[0] ?? null);
        if ($selectedArrangement) {
            $presentation->setSelectedArrangement($selectedArrangement->getUuid());
        }

        $presentation->setBackground(self::buildPresentationBackground());
        $presentation->setChordChart(self::buildChordChartUrl());
        $presentation->setTimeline(self::buildTimeline());

        self::applyCcliMetadata($presentation, $ccli);

        return new Song($presentation);
    }

    public static function generateAndWrite(
        string $filePath,
        string $name,
        array $groups,
        array $arrangements,
        array $ccli = [],
    ): Song {
        $song = self::generate($name, $groups, $arrangements, $ccli);
        ProFileWriter::write($song, $filePath);

        return $song;
    }

    private static function buildApplicationInfo(): ApplicationInfo
    {
        $platformVersion = new Version();
        $platformVersion->setMajorVersion(14);
        $platformVersion->setMinorVersion(8);
        $platformVersion->setPatchVersion(3);

        $applicationVersion = new Version();
        $applicationVersion->setMajorVersion(20);
        $applicationVersion->setBuild('335544354');

        $applicationInfo = new ApplicationInfo();
        $applicationInfo->setPlatform(Platform::PLATFORM_MACOS);
        $applicationInfo->setApplication(Application::APPLICATION_PROPRESENTER);
        $applicationInfo->setPlatformVersion($platformVersion);
        $applicationInfo->setApplicationVersion($applicationVersion);

        return $applicationInfo;
    }

    private static function buildCue(string $cueUuid, array $slideData): Cue
    {
        $elements = [];
        if (isset($slideData['text'])) {
            $hasTranslation = isset($slideData['translation']) && $slideData['translation'] !== null;

            if ($hasTranslation) {
                $elements[] = self::buildSlideElement('Orginal', (string) $slideData['text'], self::buildOriginalBounds());
                $elements[] = self::buildSlideElement('Deutsch', (string) $slideData['translation'], self::buildTranslationBounds());
            } else {
                $elements[] = self::buildSlideElement('Orginal', (string) $slideData['text']);
            }
        }

        $slide = new Slide();
        $slide->setUuid(self::newUuid());
        $slide->setElements($elements);

        // Set slide size to 1920x1080
        $slideSize = new Size();
        $slideSize->setWidth(1920);
        $slideSize->setHeight(1080);
        $slide->setSize($slideSize);

        $presentationSlide = new PresentationSlide();
        $presentationSlide->setBaseSlide($slide);
        $presentationSlide->setChordChart(self::buildChordChartUrl());

        $slideType = new SlideType();
        $slideType->setPresentation($presentationSlide);

        $actions = [self::buildSlideAction($slideType)];

        if (isset($slideData['media'])) {
            // Derive name from label OR filename without extension
            $mediaName = $slideData['label'] ?? null;
            if ($mediaName === null) {
                $basename = basename((string) $slideData['media']);
                // Strip query string and fragment
                $basename = strtok($basename, '?#') ?: $basename;
                // Remove extension
                $dotPos = strrpos($basename, '.');
                $mediaName = $dotPos !== false ? substr($basename, 0, $dotPos) : $basename;
            }

            $actions[] = self::buildMediaAction(
                (string) $slideData['media'],
                (string) ($slideData['format'] ?? 'JPG'),
                $mediaName,
                (int) ($slideData['mediaWidth'] ?? 0),
                (int) ($slideData['mediaHeight'] ?? 0),
                (bool) ($slideData['bundleRelative'] ?? false),
            );
        }

        if (isset($slideData['macro']) && is_array($slideData['macro'])) {
            $actions[] = self::buildMacroAction($slideData['macro']);
        }

        $cue = new Cue();
        $cue->setUuid(self::uuidFromString($cueUuid));
        $cue->setActions($actions);
        $cue->setIsEnabled(true);
        $cue->setHotKey(new HotKey());
        if (isset($slideData['label'])) {
            $cue->setName((string) $slideData['label']);
        }

        return $cue;
    }

    private static function buildSlideAction(SlideType $slideType): Action
    {
        $action = new Action();
        $action->setUuid(self::newUuid());
        $action->setType(ActionType::ACTION_TYPE_PRESENTATION_SLIDE);
        $action->setSlide($slideType);
        $action->setIsEnabled(true);

        return $action;
    }

    private static function buildMacroAction(array $macroData): Action
    {
        $parentCollection = new CollectionElementType();
        $parentCollection->setParameterName((string) ($macroData['collectionName'] ?? '--MAIN--'));
        $parentCollection->setParameterUuid(self::uuidFromString((string) ($macroData['collectionUuid'] ?? '')));

        $identification = new CollectionElementType();
        $identification->setParameterName((string) ($macroData['name'] ?? ''));
        $identification->setParameterUuid(self::uuidFromString((string) ($macroData['uuid'] ?? '')));
        $identification->setParentCollection($parentCollection);

        $macroType = new MacroType();
        $macroType->setIdentification($identification);

        $action = new Action();
        $action->setUuid(self::newUuid());
        $action->setType(ActionType::ACTION_TYPE_MACRO);
        $action->setMacro($macroType);
        $action->setIsEnabled(true);

        return $action;
    }

    private static function buildMediaAction(string $absoluteUrl, string $format, ?string $name = null, int $imageWidth = 0, int $imageHeight = 0, bool $bundleRelative = false): Action
    {
        if ($bundleRelative) {
            $filename = basename($absoluteUrl);
            $url = self::buildBundleRelativeUrl($filename);
            $fileLocalUrl = self::buildBundleRelativeUrl($filename);
        } else {
            $url = new URL();
            $url->setAbsoluteString($absoluteUrl);
            $url->setLocal(self::buildLocalRelativePath($absoluteUrl));
            $url->setPlatform(UrlPlatform::PLATFORM_MACOS);

            $fileLocalUrl = new URL();
            $fileLocalUrl->setAbsoluteString($absoluteUrl);
            $fileLocalUrl->setLocal(self::buildLocalRelativePath($absoluteUrl));
            $fileLocalUrl->setPlatform(UrlPlatform::PLATFORM_MACOS);
        }

        $metadata = new Metadata();
        $metadata->setFormat($format);

        $naturalSize = new Size();
        $naturalSize->setWidth($imageWidth);
        $naturalSize->setHeight($imageHeight);

        $customBoundsOrigin = new Point();
        $customBoundsSize = new Size();
        $customImageBounds = new Rect();
        $customImageBounds->setOrigin($customBoundsOrigin);
        $customImageBounds->setSize($customBoundsSize);

        $cropInsets = new EdgeInsets();

        $drawing = new DrawingProperties();
        $drawing->setNaturalSize($naturalSize);
        $drawing->setCustomImageBounds($customImageBounds);
        $drawing->setCropInsets($cropInsets);
        $drawing->setAlphaType(AlphaType::ALPHA_TYPE_STRAIGHT);

        $fileProperties = new FileProperties();
        $fileProperties->setLocalUrl($fileLocalUrl);

        $imageTypeProperties = new ImageTypeProperties();
        $imageTypeProperties->setDrawing($drawing);
        $imageTypeProperties->setFile($fileProperties);

        $mediaElement = new Media();
        $mediaElement->setUuid(self::newUuid());
        $mediaElement->setUrl($url);
        $mediaElement->setMetadata($metadata);
        $mediaElement->setImage($imageTypeProperties);

        $mediaType = new MediaType();
        $mediaType->setLayerType(LayerType::LAYER_TYPE_FOREGROUND);
        $mediaType->setElement($mediaElement);
        $mediaType->setAudio(new Audio());

        $action = new Action();
        $action->setUuid(self::newUuid());
        $action->setType(ActionType::ACTION_TYPE_MEDIA);
        $action->setMedia($mediaType);
        $action->setIsEnabled(true);

        if ($name !== null) {
            $action->setName($name);
        }

        return $action;
    }

    private static function buildBundleRelativeUrl(string $filename): URL
    {
        $local = new LocalRelativePath();
        $local->setRoot(LocalRelativePath\Root::ROOT_CURRENT_RESOURCE);
        $local->setPath($filename);

        $url = new URL();
        $url->setAbsoluteString($filename);
        $url->setLocal($local);
        $url->setPlatform(UrlPlatform::PLATFORM_MACOS);

        return $url;
    }

    private static function buildSlideElement(string $name, string $text, ?Rect $bounds = null): SlideElement
    {
        $graphicsElement = new GraphicsElement();
        $graphicsElement->setUuid(self::newUuid());
        $graphicsElement->setName($name);
        $graphicsElement->setBounds($bounds ?? self::buildBounds());
        $graphicsElement->setOpacity(1.0);
        $graphicsElement->setPath(self::buildPath());
        $graphicsElement->setFill(self::buildFill());
        $graphicsElement->setStroke(self::buildStroke());
        $graphicsElement->setShadow(self::buildShadow());
        $graphicsElement->setFeather(self::buildFeather());

        $graphicsText = new Text();
        $graphicsText->setRtfData(self::buildRtf($text));
        $graphicsText->setVerticalAlignment(VerticalAlignment::VERTICAL_ALIGNMENT_MIDDLE);
        $graphicsElement->setText($graphicsText);

        $slideElement = new SlideElement();
        $slideElement->setElement($graphicsElement);
        $slideElement->setInfo(3);
        $slideElement->setTextScroller(self::buildTextScroller());

        return $slideElement;
    }

    private static function buildBounds(): Rect
    {
        $origin = new Point();
        $origin->setX(150);
        $origin->setY(100);

        $size = new Size();
        $size->setWidth(1620);
        $size->setHeight(880);

        $rect = new Rect();
        $rect->setOrigin($origin);
        $rect->setSize($size);

        return $rect;
    }

    private static function buildOriginalBounds(): Rect
    {
        $origin = new Point();
        $origin->setX(150);
        $origin->setY(99.543);

        $size = new Size();
        $size->setWidth(1620);
        $size->setHeight(182.946);

        $rect = new Rect();
        $rect->setOrigin($origin);
        $rect->setSize($size);

        return $rect;
    }

    private static function buildTranslationBounds(): Rect
    {
        $origin = new Point();
        $origin->setX(150);
        $origin->setY(303.166);

        $size = new Size();
        $size->setWidth(1620);
        $size->setHeight(113.889);

        $rect = new Rect();
        $rect->setOrigin($origin);
        $rect->setSize($size);

        return $rect;
    }

    private static function buildPath(): Path
    {
        $path = new Path();
        $path->setClosed(true);
        $path->setPoints([
            self::buildBezierPoint(0.0, 0.0),
            self::buildBezierPoint(1.0, 0.0),
            self::buildBezierPoint(1.0, 1.0),
            self::buildBezierPoint(0.0, 1.0),
        ]);

        $shape = new Shape();
        $shape->setType(ShapeType::TYPE_RECTANGLE);
        $path->setShape($shape);

        return $path;
    }

    private static function buildBezierPoint(float $x, float $y): BezierPoint
    {
        $point = new Point();
        $point->setX($x);
        $point->setY($y);

        $bezierPoint = new BezierPoint();
        $bezierPoint->setPoint($point);
        $bezierPoint->setQ0($point);
        $bezierPoint->setQ1($point);
        $bezierPoint->setCurved(false);

        return $bezierPoint;
    }

    private static function buildFill(): Fill
    {
        $fill = new Fill();
        $fill->setEnable(false);
        $fill->setColor(self::buildColor(0.13, 0.59, 0.95, 1.0));

        return $fill;
    }

    private static function buildStroke(): Stroke
    {
        $stroke = new Stroke();
        $stroke->setStyle(StrokeStyle::STYLE_SOLID_LINE);
        $stroke->setEnable(false);
        $stroke->setWidth(3.0);
        $stroke->setColor(self::buildColor(1.0, 1.0, 1.0, 1.0));

        return $stroke;
    }

    private static function buildShadow(): Shadow
    {
        $shadow = new Shadow();
        $shadow->setStyle(ShadowStyle::STYLE_DROP);
        $shadow->setEnable(false);
        $shadow->setAngle(315.0);
        $shadow->setOffset(5.0);
        $shadow->setRadius(5.0);
        $shadow->setColor(self::buildColor(0.0, 0.0, 0.0, 1.0));
        $shadow->setOpacity(0.75);

        return $shadow;
    }

    private static function buildFeather(): Feather
    {
        $feather = new Feather();
        $feather->setStyle(FeatherStyle::STYLE_INSIDE);
        $feather->setEnable(false);
        $feather->setRadius(0.05);

        return $feather;
    }

    private static function buildTextScroller(): TextScroller
    {
        $textScroller = new TextScroller();
        $textScroller->setShouldScroll(false);
        $textScroller->setScrollRate(0.5);
        $textScroller->setShouldRepeat(true);
        $textScroller->setRepeatDistance(0.0617);

        return $textScroller;
    }

    private static function colorFromArray(array $rgba): Color
    {
        return self::buildColor(
            (float) ($rgba[0] ?? 0.0),
            (float) ($rgba[1] ?? 0.0),
            (float) ($rgba[2] ?? 0.0),
            (float) ($rgba[3] ?? 1.0),
        );
    }

    private static function buildColor(float $r, float $g, float $b, float $a): Color
    {
        $color = new Color();
        $color->setRed($r);
        $color->setGreen($g);
        $color->setBlue($b);
        $color->setAlpha($a);

        return $color;
    }

    private static function buildPresentationBackground(): Background
    {
        $color = new Color();
        $color->setAlpha(1.0);
        $background = new Background();
        $background->setColor($color);
        return $background;
    }

    private static function buildChordChartUrl(): URL
    {
        $url = new URL();
        $url->setPlatform(UrlPlatform::PLATFORM_MACOS);
        return $url;
    }

    private static function buildTimeline(): Timeline
    {
        $timeline = new Timeline();
        $timeline->setDuration(300.0);
        return $timeline;
    }

    private static function applyCcliMetadata(Presentation $presentation, array $ccli): void
    {
        $metadata = new CCLI();
        if (isset($ccli['author'])) {
            $metadata->setAuthor((string) $ccli['author']);
        }
        if (isset($ccli['song_title'])) {
            $metadata->setSongTitle((string) $ccli['song_title']);
        }
        if (isset($ccli['publisher'])) {
            $metadata->setPublisher((string) $ccli['publisher']);
        }
        if (isset($ccli['copyright_year'])) {
            $metadata->setCopyrightYear((int) $ccli['copyright_year']);
        }
        if (isset($ccli['song_number'])) {
            $metadata->setSongNumber((int) $ccli['song_number']);
        }
        if (isset($ccli['display'])) {
            $metadata->setDisplay((bool) $ccli['display']);
        }
        if (isset($ccli['artist_credits'])) {
            $metadata->setArtistCredits((string) $ccli['artist_credits']);
        }
        if (isset($ccli['album'])) {
            $metadata->setAlbum((string) $ccli['album']);
        }

        $presentation->setCcli($metadata);
    }

    private static function buildRtf(string $text): string
    {
        $encodedText = self::encodePlainTextForRtf($text);

        return str_replace('ENCODED_TEXT_HERE', $encodedText, <<<'RTF'
{\rtf1\ansi\ansicpg1252\cocoartf2761
\cocoatextscaling0\cocoaplatform0{\fonttbl\f0\fnil\fcharset0 HelveticaNeue;}
{\colortbl;\red255\green255\blue255;\red255\green255\blue255;}
{\*\expandedcolortbl;;\csgray\c100000;}
\deftab1680
\pard\pardeftab1680\pardirnatural\qc\partightenfactor0

\f0\fs84 \cf2 \CocoaLigature0 ENCODED_TEXT_HERE}
RTF);
    }

    private static function encodePlainTextForRtf(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = strtr($text, [
            'ü' => "\\'fc",
            'ö' => "\\'f6",
            'ä' => "\\'e4",
            'ß' => "\\'df",
            'Ü' => "\\'dc",
            'Ö' => "\\'d6",
            'Ä' => "\\'c4",
        ]);

        return str_replace("\n", "\\\n", $text);
    }

    private static function newUuid(): UUID
    {
        return self::uuidFromString(self::newUuidString());
    }

    private static function uuidFromString(string $uuid): UUID
    {
        $message = new UUID();
        $message->setString($uuid);

        return $message;
    }

    private static function buildLocalRelativePath(string $absoluteUrl): LocalRelativePath
    {
        $path = $absoluteUrl;
        if (str_starts_with($path, 'file:///')) {
            $path = substr($path, 7);
        }

        $rootMappings = [
            '/Downloads/' => LocalRelativePath\Root::ROOT_USER_DOWNLOADS,
            '/Documents/' => LocalRelativePath\Root::ROOT_USER_DOCUMENTS,
            '/Music/' => LocalRelativePath\Root::ROOT_USER_MUSIC,
            '/Pictures/' => LocalRelativePath\Root::ROOT_USER_PICTURES,
            '/Movies/' => LocalRelativePath\Root::ROOT_USER_VIDEOS,
            '/Desktop/' => LocalRelativePath\Root::ROOT_USER_DESKTOP,
        ];

        $root = LocalRelativePath\Root::ROOT_BOOT_VOLUME;
        $relativePath = ltrim($path, '/');

        if (preg_match('#^/Users/[^/]+(/\w+/)(.+)$#', $path, $matches)) {
            $dirSegment = $matches[1];
            if (isset($rootMappings[$dirSegment])) {
                $root = $rootMappings[$dirSegment];
                $relativePath = $matches[2];
            } else {
                // Unmapped user directory → ROOT_USER_HOME with user-relative path
                $root = LocalRelativePath\Root::ROOT_USER_HOME;
                if (preg_match('#^/Users/[^/]+/(.+)$#', $path, $userMatch)) {
                    $relativePath = $userMatch[1];
                }
            }
        }

        $local = new LocalRelativePath();
        $local->setRoot($root);
        $local->setPath($relativePath);

        return $local;
    }

    private static function newUuidString(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return strtoupper(sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        ));
    }
}

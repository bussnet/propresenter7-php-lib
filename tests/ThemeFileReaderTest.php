<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ThemeAsset;
use ProPresenter\Parser\ThemeBundle;
use ProPresenter\Parser\ThemeFileReader;
use ProPresenter\Parser\ThemeFileWriter;
use ProPresenter\Parser\ThemeSlide;
use Rv\Data\Template\Slide as TemplateSlide;

class ThemeFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/pp-themes/sample';

    #[Test]
    public function readThrowsOnMissingFolder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ThemeFileReader::read(__DIR__ . '/../doc/reference_samples/pp-themes/does-not-exist');
    }

    #[Test]
    public function readReturnsBundleWithExpectedCount(): void
    {
        $bundle = ThemeFileReader::read(self::REFERENCE_PATH);
        $this->assertInstanceOf(ThemeBundle::class, $bundle);
        $this->assertCount(11, $bundle->getSlides());
        $this->assertSame(11, $bundle->count());
        $this->assertCount(3, $bundle->getAssets());
        $this->assertSame(3, $bundle->getAssetCount());
    }

    #[Test]
    public function slideExposesNameAndBaseSlide(): void
    {
        $slide = ThemeFileReader::read(self::REFERENCE_PATH)->getSlides()[0];
        $this->assertInstanceOf(ThemeSlide::class, $slide);
        $this->assertSame('KeyVisual', $slide->getName());
        $this->assertNotNull($slide->getBaseSlide());
    }

    #[Test]
    public function assetsExposeBytesAndMimeType(): void
    {
        $asset = ThemeFileReader::read(self::REFERENCE_PATH)->getAssetByName('BACKGROUND.jpg');
        $this->assertInstanceOf(ThemeAsset::class, $asset);
        $this->assertSame('BACKGROUND.jpg', $asset->getName());
        $this->assertNotSame('', $asset->getBytes());
        $this->assertGreaterThan(0, $asset->getSize());
        $this->assertSame('image/jpeg', $asset->getMimeType());
    }

    #[Test]
    public function writerProducesStableThemeDocumentRoundTrip(): void
    {
        $tmp = $this->makeTempDir('theme_');
        $second = $this->makeTempDir('theme_');
        try {
            ThemeFileWriter::write(ThemeFileReader::read(self::REFERENCE_PATH), $tmp);
            ThemeFileWriter::write(ThemeFileReader::read($tmp), $second);
            $this->assertSame(file_get_contents($tmp . '/Theme'), file_get_contents($second . '/Theme'));
        } finally {
            $this->removeDirectory($tmp);
            $this->removeDirectory($second);
        }
    }

    #[Test]
    public function writerRoundTripsEntireFolder(): void
    {
        $source = ThemeFileReader::read(self::REFERENCE_PATH);
        $tmp = $this->makeTempDir('theme_');
        try {
            ThemeFileWriter::write($source, $tmp);
            $roundTrip = ThemeFileReader::read($tmp);
            $this->assertSame($source->count(), $roundTrip->count());
            $this->assertSame($source->getAssetCount(), $roundTrip->getAssetCount());
            $first = $source->getAssets()[0];
            $this->assertSame($first->getBytes(), $roundTrip->getAssetByName($first->getName())?->getBytes());
        } finally {
            $this->removeDirectory($tmp);
        }
    }

    #[Test]
    public function addRemoveAndStaleAssetCleanupRoundTrip(): void
    {
        $bundle = ThemeFileReader::read(self::REFERENCE_PATH);
        $slide = new ThemeSlide(new TemplateSlide());
        $slide->setName('Test Theme Slide');
        $bundle->addSlide($slide);
        $bundle->addAsset('TEST.png', 'png-bytes');
        $this->assertSame(12, $bundle->count());
        $this->assertSame(4, $bundle->getAssetCount());

        $this->assertTrue($bundle->removeSlide('Test Theme Slide'));
        $this->assertTrue($bundle->removeAsset('TEST.png'));
        $this->assertSame(11, $bundle->count());
        $this->assertSame(3, $bundle->getAssetCount());

        $tmp = $this->makeTempDir('theme_');
        try {
            mkdir($tmp . '/Assets');
            file_put_contents($tmp . '/Assets/stale.jpg', 'stale');
            ThemeFileWriter::write($bundle, $tmp);
            $this->assertFileDoesNotExist($tmp . '/Assets/stale.jpg');
        } finally {
            $this->removeDirectory($tmp);
        }
    }

    private function makeTempDir(string $prefix): string
    {
        $path = sys_get_temp_dir() . '/' . $prefix . uniqid('', true);
        mkdir($path);

        return $path;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $entries = scandir($path);
        if ($entries === false) {
            return;
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $child = $path . '/' . $entry;
            if (is_dir($child)) {
                $this->removeDirectory($child);
            } else {
                @unlink($child);
            }
        }
        @rmdir($path);
    }
}

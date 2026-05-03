<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\PresentationBundle;
use ProPresenter\Parser\ProBundleReader;
use ProPresenter\Parser\ProBundleWriter;
use ProPresenter\Parser\ProFileGenerator;
use RuntimeException;
use ZipArchive;

class ProBundleTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/propresenter-bundle-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->tmpDir)) {
            return;
        }

        $this->removeDirectoryRecursively($this->tmpDir);
    }

    #[Test]
    public function readerThrowsWhenFileNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bundle file not found');

        ProBundleReader::read('/nonexistent/path.probundle');
    }

    #[Test]
    public function readerThrowsWhenFileIsEmpty(): void
    {
        $emptyFile = $this->tmpDir . '/empty.probundle';
        file_put_contents($emptyFile, '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bundle file is empty');

        ProBundleReader::read($emptyFile);
    }

    #[Test]
    public function writerThrowsWhenTargetDirectoryMissing(): void
    {
        $bundle = new PresentationBundle(
            ProFileGenerator::generate('Dummy', [
                ['name' => 'V1', 'color' => [0, 0, 0, 1], 'slides' => [['text' => 'x']]],
            ], [['name' => 'n', 'groupNames' => ['V1']]]),
            'Dummy.pro',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Target directory does not exist');

        ProBundleWriter::write($bundle, '/nonexistent/dir/out.probundle');
    }

    #[Test]
    public function writeAndReadBundleWithRealImage(): void
    {
        $imagePath = $this->tmpDir . '/test-background.png';
        $this->createTestPngImage($imagePath, 200, 150);
        $imageBytes = file_get_contents($imagePath);
        $this->assertNotFalse($imageBytes);

        $song = ProFileGenerator::generate(
            'Bundle Test Song',
            [
                [
                    'name' => 'Verse 1',
                    'color' => [0.2, 0.4, 0.8, 1.0],
                    'slides' => [
                        ['text' => 'Amazing Grace, how sweet the sound'],
                        ['text' => 'That saved a wretch like me'],
                    ],
                ],
                [
                    'name' => 'Chorus',
                    'color' => [0.8, 0.2, 0.2, 1.0],
                    'slides' => [
                        ['text' => 'I once was lost, but now am found'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['Verse 1', 'Chorus']],
            ],
        );

        $bundle = new PresentationBundle(
            $song,
            'Bundle Test Song.pro',
            ['test-background.png' => $imageBytes],
        );

        $bundlePath = $this->tmpDir . '/BundleTestSong.probundle';
        ProBundleWriter::write($bundle, $bundlePath);

        $this->assertFileExists($bundlePath);
        $this->assertGreaterThan(0, filesize($bundlePath));

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($bundlePath) === true);
        $this->assertNotFalse($zip->locateName('Bundle Test Song.pro'));
        $this->assertNotFalse($zip->locateName('test-background.png'));
        $this->assertSame(2, $zip->numFiles);
        $zip->close();

        $readBundle = ProBundleReader::read($bundlePath);

        $this->assertSame('Bundle Test Song', $readBundle->getName());
        $this->assertSame('Bundle Test Song.pro', $readBundle->getProFilename());
        $this->assertSame(1, $readBundle->getMediaFileCount());
        $this->assertTrue($readBundle->hasMediaFile('test-background.png'));
        $this->assertSame($imageBytes, $readBundle->getMediaFile('test-background.png'));

        $readSong = $readBundle->getSong();
        $this->assertSame('Bundle Test Song', $readSong->getName());
        $this->assertCount(2, $readSong->getGroups());
        $this->assertCount(3, $readSong->getSlides());
    }

    #[Test]
    public function writeAndReadBundleWithMultipleMediaFiles(): void
    {
        $image1Path = $this->tmpDir . '/slide1.png';
        $image2Path = $this->tmpDir . '/slide2.png';
        $this->createTestPngImage($image1Path, 100, 100);
        $this->createTestPngImage($image2Path, 320, 240);

        $image1Bytes = file_get_contents($image1Path);
        $image2Bytes = file_get_contents($image2Path);
        $this->assertNotFalse($image1Bytes);
        $this->assertNotFalse($image2Bytes);

        $song = ProFileGenerator::generate(
            'Multi Media Song',
            [
                [
                    'name' => 'Verse 1',
                    'color' => [0.1, 0.2, 0.3, 1.0],
                    'slides' => [
                        ['text' => 'Slide with media'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['Verse 1']],
            ],
        );

        $bundle = new PresentationBundle(
            $song,
            'Multi Media Song.pro',
            [
                'slide1.png' => $image1Bytes,
                'slide2.png' => $image2Bytes,
            ],
        );

        $bundlePath = $this->tmpDir . '/MultiMedia.probundle';
        ProBundleWriter::write($bundle, $bundlePath);

        $readBundle = ProBundleReader::read($bundlePath);

        $this->assertSame(2, $readBundle->getMediaFileCount());
        $this->assertTrue($readBundle->hasMediaFile('slide1.png'));
        $this->assertTrue($readBundle->hasMediaFile('slide2.png'));
        $this->assertSame($image1Bytes, $readBundle->getMediaFile('slide1.png'));
        $this->assertSame($image2Bytes, $readBundle->getMediaFile('slide2.png'));
    }

    #[Test]
    public function writeAndReadBundleWithoutMediaFiles(): void
    {
        $song = ProFileGenerator::generate(
            'No Media Song',
            [
                [
                    'name' => 'Verse 1',
                    'color' => [0.1, 0.2, 0.3, 1.0],
                    'slides' => [
                        ['text' => 'Just lyrics, no media'],
                    ],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['Verse 1']],
            ],
        );

        $bundle = new PresentationBundle($song, 'No Media Song.pro');

        $bundlePath = $this->tmpDir . '/NoMedia.probundle';
        ProBundleWriter::write($bundle, $bundlePath);

        $readBundle = ProBundleReader::read($bundlePath);

        $this->assertSame('No Media Song', $readBundle->getName());
        $this->assertSame(0, $readBundle->getMediaFileCount());
        $this->assertFalse($readBundle->hasMediaFile('anything'));
        $this->assertNull($readBundle->getMediaFile('anything'));
    }

    #[Test]
    public function readerHandlesProPresenterExportedBundle(): void
    {
        $ppExportPath = dirname(__DIR__) . '/doc/reference_samples/RestBildExportFromPP.probundle';
        if (!is_file($ppExportPath)) {
            $this->markTestSkipped('PP-exported reference file not available');
        }

        $bundle = ProBundleReader::read($ppExportPath);

        $this->assertSame('TestBild', $bundle->getName());
        $this->assertSame('TestBild.pro', $bundle->getProFilename());
        $this->assertSame(1, $bundle->getMediaFileCount());

        $slide = $bundle->getSong()->getSlides()[0];
        $this->assertTrue($slide->hasMedia());
        $this->assertStringStartsWith('file:///', $slide->getMediaUrl());
        $this->assertSame('png', $slide->getMediaFormat());
    }

    #[Test]
    public function writeProducesStandardZipWithFlatMediaPaths(): void
    {
        $imagePath = $this->tmpDir . '/bg.png';
        $this->createTestPngImage($imagePath, 100, 100);
        $imageBytes = file_get_contents($imagePath);
        $this->assertNotFalse($imageBytes);

        $song = ProFileGenerator::generate(
            'ZipFormatTest',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [
                        [
                            'media' => 'bg.png',
                            'format' => 'png',
                            'bundleRelative' => true,
                        ],
                    ],
                ],
            ],
            [['name' => 'normal', 'groupNames' => ['V1']]],
        );

        $bundle = new PresentationBundle(
            $song,
            'ZipFormatTest.pro',
            ['bg.png' => $imageBytes],
        );

        $bundlePath = $this->tmpDir . '/ZipFormatTest.probundle';
        ProBundleWriter::write($bundle, $bundlePath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($bundlePath) === true);
        $this->assertSame(2, $zip->numFiles);

        $mediaIdx = $zip->locateName('bg.png');
        $this->assertNotFalse($mediaIdx, 'Media entry should use flat filename');

        $proIdx = $zip->locateName('ZipFormatTest.pro');
        $this->assertNotFalse($proIdx);
        $this->assertGreaterThan($mediaIdx, $proIdx, 'Media entries should come before .pro entry');

        $zip->close();

        $readBundle = ProBundleReader::read($bundlePath);
        $this->assertSame('ZipFormatTest', $readBundle->getName());
        $this->assertTrue($readBundle->hasMediaFile('bg.png'));
        $this->assertSame($imageBytes, $readBundle->getMediaFile('bg.png'));
    }

    #[Test]
    public function bundleWrapperExposesAllProperties(): void
    {
        $song = ProFileGenerator::generate(
            'Wrapper Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [['text' => 'Hello']],
                ],
            ],
            [['name' => 'normal', 'groupNames' => ['V1']]],
        );

        $bundle = new PresentationBundle(
            $song,
            'Wrapper Test.pro',
            ['bg.jpg' => 'fake-jpeg-bytes'],
        );

        $this->assertSame('Wrapper Test', $bundle->getName());
        $this->assertSame('Wrapper Test.pro', $bundle->getProFilename());
        $this->assertSame($song, $bundle->getSong());
        $this->assertSame($song->getPresentation(), $bundle->getPresentation());
        $this->assertSame(1, $bundle->getMediaFileCount());
        $this->assertTrue($bundle->hasMediaFile('bg.jpg'));
        $this->assertSame('fake-jpeg-bytes', $bundle->getMediaFile('bg.jpg'));
        $this->assertSame(['bg.jpg' => 'fake-jpeg-bytes'], $bundle->getMediaFiles());
    }

    private function createTestPngImage(string $path, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        $this->assertNotFalse($image);

        $blue = imagecolorallocate($image, 30, 60, 180);
        $this->assertNotFalse($blue);
        imagefill($image, 0, 0, $blue);

        $white = imagecolorallocate($image, 255, 255, 255);
        $this->assertNotFalse($white);
        imagestring($image, 5, 10, 10, 'ProPresenter', $white);

        imagepng($image, $path);
    }

    private function removeDirectoryRecursively(string $path): void
    {
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $path . '/' . $entry;
            if (is_dir($entryPath)) {
                $this->removeDirectoryRecursively($entryPath);
                continue;
            }

            @unlink($entryPath);
        }

        @rmdir($path);
    }

}

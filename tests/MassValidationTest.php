<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileReader;
use RuntimeException;

class MassValidationTest extends TestCase
{
    private const EMPTY_FILE = '_empty.pro';

    private static function refPath(): string
    {
        return dirname(__DIR__) . '/doc/reference_samples/';
    }

    /**
     * Collect all .pro files from doc/reference_samples/all-songs/ plus doc/reference_samples/Test.pro.
     *
     * @return string[]
     */
    private static function collectAllProFiles(): array
    {
        $dir = self::refPath() . 'all-songs/';
        $files = glob($dir . '*.pro');
        self::assertNotFalse($files);

        $testPro = self::refPath() . 'Test.pro';
        self::assertFileExists($testPro);
        $files[] = $testPro;

        sort($files);

        return $files;
    }

    #[Test]
    public function emptyFileThrowsRuntimeException(): void
    {
        $emptyFile = self::refPath() . 'all-songs/' . self::EMPTY_FILE;
        $this->assertFileExists($emptyFile);
        $this->assertSame(0, filesize($emptyFile));

        $this->expectException(RuntimeException::class);
        ProFileReader::read($emptyFile);
    }

    #[Test]
    public function allNonEmptyProFilesParseSuccessfully(): void
    {
        $allFiles = self::collectAllProFiles();

        $totalFiles = 0;
        $totalGroups = 0;
        $totalSlides = 0;
        $totalTranslations = 0;
        $totalArrangements = 0;
        $skipped = 0;

        foreach ($allFiles as $file) {
            $basename = basename($file);

            // Skip the known empty file
            if ($basename === self::EMPTY_FILE) {
                $skipped++;
                continue;
            }

            $song = ProFileReader::read($file);
            $totalFiles++;

            // Song name must be non-empty
            $this->assertNotSame('', $song->getName(), sprintf(
                'Song name should not be empty for %s',
                $basename,
            ));

            // Groups must be an array
            $groups = $song->getGroups();
            $this->assertIsArray($groups, sprintf(
                'getGroups() should return array for %s',
                $basename,
            ));

            // Slides must be an array
            $slides = $song->getSlides();
            $this->assertIsArray($slides, sprintf(
                'getSlides() should return array for %s',
                $basename,
            ));

            // Validate each group — name may be empty in non-song files
            foreach ($groups as $group) {
                $this->assertIsString($group->getName(), sprintf(
                    'Group getName() should return a string in %s',
                    $basename,
                ));
                $totalGroups++;
            }

            // Validate each slide — text elements may exist with empty text (placeholder shapes)
            foreach ($slides as $slide) {
                $plainText = $slide->getPlainText();

                // getPlainText() must return a string (may be empty for non-content slides)
                $this->assertIsString($plainText, sprintf(
                    'getPlainText() should return a string for slide in %s',
                    $basename,
                ));

                if ($slide->hasTranslation()) {
                    $translation = $slide->getTranslation();
                    $this->assertNotNull($translation, sprintf(
                        'Slide reporting hasTranslation() should return non-null getTranslation() in %s',
                        $basename,
                    ));
                    $totalTranslations++;
                }

                $totalSlides++;
            }

            $totalArrangements += count($song->getArrangements());
        }

        $this->assertSame(1, $skipped, 'Expected exactly 1 empty file to be skipped');

        // Synthetic fixture corpus: 9 non-empty all-songs + 1 Test.pro = 10 parsed.
        $this->assertGreaterThanOrEqual(10, $totalFiles, 'Expected at least 10 non-empty files parsed');

        fwrite(STDERR, sprintf(
            "\n[MassValidation] %d files parsed, %d skipped | %d groups, %d slides, %d translations, %d arrangements\n",
            $totalFiles,
            $skipped,
            $totalGroups,
            $totalSlides,
            $totalTranslations,
            $totalArrangements,
        ));
    }

    #[Test]
    public function songsWithoutArrangementsAreValid(): void
    {
        $allFiles = self::collectAllProFiles();
        $noArrangementCount = 0;

        foreach ($allFiles as $file) {
            if (basename($file) === self::EMPTY_FILE) {
                continue;
            }

            $song = ProFileReader::read($file);

            if (count($song->getArrangements()) === 0) {
                $noArrangementCount++;
                // Still valid: name and groups must work
                $this->assertNotSame('', $song->getName(), sprintf(
                    'Song without arrangements should still have a name: %s',
                    basename($file),
                ));
                $this->assertIsArray($song->getGroups());
                $this->assertIsArray($song->getSlides());
            }
        }

        // Some files are expected to have no arrangements
        $this->assertGreaterThan(0, $noArrangementCount, 'Expected at least some files without arrangements');

        fwrite(STDERR, sprintf(
            "\n[MassValidation] %d files have zero arrangements\n",
            $noArrangementCount,
        ));
    }

    #[Test]
    public function nonSongFilesParseWithGroups(): void
    {
        $refDir = self::refPath() . 'all-songs/';

        // Non-song fixtures: moderation slot, announcements, thema placeholder.
        $patterns = [
            'MODERATION'    => $refDir . '-- MODERATION --.pro',
            'ANNOUNCEMENTS' => $refDir . '-- ANNOUNCEMENTS --.pro',
        ];

        $themaMatches = glob($refDir . 'THEMA*');
        $this->assertNotFalse($themaMatches);
        $this->assertNotEmpty($themaMatches, 'THEMA files should exist');
        $patterns['THEMA'] = $themaMatches[0];

        foreach ($patterns as $label => $file) {
            $this->assertFileExists($file, sprintf('%s file should exist', $label));
            $song = ProFileReader::read($file);

            $this->assertNotSame('', $song->getName(), sprintf(
                '%s should have a name',
                $label,
            ));

            // Non-song files have groups but may have no text in slides
            $this->assertIsArray($song->getGroups(), sprintf(
                '%s should return groups array',
                $label,
            ));
        }
    }

    #[Test]
    public function transFilesHaveTranslations(): void
    {
        $refDir = self::refPath() . 'all-songs/';
        $transFiles = glob($refDir . '*[TRANS]*.pro');
        $this->assertNotFalse($transFiles);
        $this->assertNotEmpty($transFiles, 'Expected [TRANS] files to exist');

        $filesWithTranslations = 0;

        foreach ($transFiles as $file) {
            $song = ProFileReader::read($file);
            $slides = $song->getSlides();
            $hasAnyTranslation = false;

            foreach ($slides as $slide) {
                if ($slide->hasTranslation()) {
                    $hasAnyTranslation = true;
                    $translation = $slide->getTranslation();
                    $this->assertNotNull($translation, sprintf(
                        'Translation should be non-null when hasTranslation() is true in %s',
                        basename($file),
                    ));
                }
            }

            if ($hasAnyTranslation) {
                $filesWithTranslations++;
            }
        }

        $this->assertGreaterThan(0, $filesWithTranslations, 'Expected at least some [TRANS] files to have translations');

        fwrite(STDERR, sprintf(
            "\n[MassValidation] %d of %d [TRANS] files have slides with translations\n",
            $filesWithTranslations,
            count($transFiles),
        ));
    }

    #[Test]
    public function everyGroupHasConsistentSlideUuids(): void
    {
        $allFiles = self::collectAllProFiles();
        $totalGroupsChecked = 0;

        foreach ($allFiles as $file) {
            if (basename($file) === self::EMPTY_FILE) {
                continue;
            }

            $song = ProFileReader::read($file);

            foreach ($song->getGroups() as $group) {
                $slideUuids = $group->getSlideUuids();
                $this->assertIsArray($slideUuids, sprintf(
                    'Group "%s" in %s should return slide UUIDs array',
                    $group->getName(),
                    basename($file),
                ));

                // Each UUID referenced by the group should resolve to a slide
                $resolvedSlides = $song->getSlidesForGroup($group);
                $this->assertIsArray($resolvedSlides);
                $this->assertCount(count($slideUuids), $resolvedSlides, sprintf(
                    'Group "%s" in %s: all %d slide UUIDs should resolve',
                    $group->getName(),
                    basename($file),
                    count($slideUuids),
                ));

                $totalGroupsChecked++;
            }
        }

        fwrite(STDERR, sprintf(
            "\n[MassValidation] %d groups verified with consistent slide UUID resolution\n",
            $totalGroupsChecked,
        ));
    }
}

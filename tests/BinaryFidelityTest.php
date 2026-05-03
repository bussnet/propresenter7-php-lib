<?php

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\TestCase;
use Rv\Data\Presentation;

class BinaryFidelityTest extends TestCase
{
    private const ALL_SONGS_DIR = __DIR__ . '/../doc/reference_samples/all-songs';
    private const TEST_PRO_PATH = __DIR__ . '/../doc/reference_samples/Test.pro';
    private const EMPTY_SKIP_FILE = __DIR__ . '/../doc/reference_samples/all-songs/_empty.pro';
    private const EXPECTED_NON_EMPTY_ALL_SONGS = 9;

    public function testDecodeEncodeRoundTripAcrossReferenceFiles(): void
    {
        $referenceFiles = glob(self::ALL_SONGS_DIR . '/*.pro');
        $this->assertIsArray($referenceFiles, 'Unable to list .pro files in all-songs directory.');
        sort($referenceFiles, SORT_STRING);

        $paths = [];
        foreach ($referenceFiles as $path) {
            if ($path === self::EMPTY_SKIP_FILE) {
                continue;
            }

            $bytes = @file_get_contents($path);
            $this->assertIsString($bytes, 'Unable to read file: ' . $path);

            if ($bytes === '') {
                $this->fail('Unexpected empty file not in skip list: ' . $path);
            }

            $paths[] = $path;
        }

        $paths[] = self::TEST_PRO_PATH;

        $this->assertCount(
            self::EXPECTED_NON_EMPTY_ALL_SONGS + 1,
            $paths,
            sprintf('Expected %d non-empty all-songs files plus Test.pro.', self::EXPECTED_NON_EMPTY_ALL_SONGS),
        );

        $failures = [];
        $byteIdenticalCount = 0;

        foreach ($paths as $path) {
            $original = @file_get_contents($path);
            $this->assertIsString($original, 'Unable to read file: ' . $path);

            $presentation = new Presentation();

            try {
                $presentation->mergeFromString($original);
            } catch (\Throwable $throwable) {
                $failures[] = [
                    'path' => $path,
                    'reason' => 'decode_error',
                    'message' => $throwable->getMessage(),
                ];

                continue;
            }

            $reencoded = $presentation->serializeToString();

            if ($original === $reencoded) {
                $byteIdenticalCount++;
            }

            $roundTrip = new Presentation();
            try {
                $roundTrip->mergeFromString($reencoded);
            } catch (\Throwable $throwable) {
                $failures[] = [
                    'path' => $path,
                    'reason' => 'reencode_decode_error',
                    'message' => $throwable->getMessage(),
                ];

                continue;
            }

            $originalJson = json_decode($presentation->serializeToJsonString(), true);
            $roundTripJson = json_decode($roundTrip->serializeToJsonString(), true);

            if ($originalJson !== $roundTripJson) {
                $failures[] = [
                    'path' => $path,
                    'reason' => 'semantic_mismatch',
                ];
            }
        }

        $total = count($paths);
        $mismatchCount = count($failures);
        $testProIdentical = !in_array(self::TEST_PRO_PATH, array_column($failures, 'path'), true);
        $message = "Round-trip results: {$byteIdenticalCount}/{$total} byte-identical, {$mismatchCount} semantic/decode failures. Test.pro byte-identical: "
            . ($testProIdentical ? 'yes' : 'no') . '.';

        if ($mismatchCount > 0) {
            $message .= "\nDetails:\n" . json_encode($failures, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        $this->assertSame([], $failures, $message);
    }

}

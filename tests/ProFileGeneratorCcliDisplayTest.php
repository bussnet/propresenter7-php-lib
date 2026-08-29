<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\ProFileGenerator;
use ProPresenter\Parser\ProFileReader;
use ProPresenter\Parser\ProFileWriter;
use ProPresenter\Parser\Song;

/**
 * `$ccli['display']` toggles the ProPresenter copyright/CCLI footer.
 *
 * The generator must honour an EXPLICIT boolean, `false` included: gating the
 * value on truthiness would make `display => false` indistinguishable from
 * "not provided". (Consumers that drop the key with `array_filter()` before
 * calling the generator have their own bug — this test pins the generator's
 * side of the contract.)
 */
final class ProFileGeneratorCcliDisplayTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/pro-ccli-display-'.bin2hex(random_bytes(4));
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir.'/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->tmpDir)) {
            rmdir($this->tmpDir);
        }
    }

    private function generate(array $ccli): Song
    {
        return ProFileGenerator::generate(
            'CCLI Display Test',
            [
                [
                    'name' => 'V1',
                    'color' => [0, 0, 0, 1],
                    'slides' => [['text' => 'Line']],
                ],
            ],
            [
                ['name' => 'normal', 'groupNames' => ['V1']],
            ],
            $ccli,
        );
    }

    #[Test]
    public function display_true_is_honoured(): void
    {
        $this->assertTrue($this->generate(['display' => true])->getCcliDisplay());
    }

    #[Test]
    public function display_false_is_honoured(): void
    {
        $this->assertFalse($this->generate(['display' => false])->getCcliDisplay());
    }

    #[Test]
    public function display_defaults_to_false_when_absent(): void
    {
        $this->assertFalse($this->generate([])->getCcliDisplay());
    }

    #[Test]
    public function display_false_survives_a_write_read_round_trip(): void
    {
        $song = $this->generate([
            'author' => 'Author Name',
            'song_title' => 'Song Title',
            'publisher' => 'Publisher Name',
            'copyright_year' => 2024,
            'song_number' => 12345,
            'display' => false,
        ]);

        $path = $this->tmpDir.'/display-false.pro';
        ProFileWriter::write($song, $path);
        $roundTrip = ProFileReader::read($path);

        $this->assertFalse($roundTrip->getCcliDisplay());
        // The rest of the metadata must be unaffected by display => false.
        $this->assertSame('Author Name', $roundTrip->getCcliAuthor());
        $this->assertSame(12345, $roundTrip->getCcliSongNumber());
    }

    #[Test]
    public function display_true_survives_a_write_read_round_trip(): void
    {
        $song = $this->generate([
            'song_title' => 'Song Title',
            'display' => true,
        ]);

        $path = $this->tmpDir.'/display-true.pro';
        ProFileWriter::write($song, $path);

        $this->assertTrue(ProFileReader::read($path)->getCcliDisplay());
    }

    #[Test]
    public function the_display_flag_can_be_toggled_after_generation(): void
    {
        $song = $this->generate(['display' => false]);
        $song->setCcliDisplay(true);
        $this->assertTrue($song->getCcliDisplay());

        $song->setCcliDisplay(false);
        $this->assertFalse($song->getCcliDisplay());
    }

    #[Test]
    public function a_truthy_non_boolean_display_value_is_cast_to_true(): void
    {
        $this->assertTrue($this->generate(['display' => 1])->getCcliDisplay());
        $this->assertFalse($this->generate(['display' => 0])->getCcliDisplay());
    }
}

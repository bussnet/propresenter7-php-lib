<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\Song;
use Rv\Data\Presentation;

class SongMetadataTest extends TestCase
{
    #[Test]
    public function ccliAuthorRoundTripWorks(): void
    {
        $song = new Song(new Presentation());

        $song->setCcliAuthor('Author Name');

        $this->assertSame('Author Name', $song->getCcliAuthor());
    }

    #[Test]
    public function ccliSongNumberRoundTripWorks(): void
    {
        $song = new Song(new Presentation());

        $song->setCcliSongNumber(123456);

        $this->assertSame(123456, $song->getCcliSongNumber());
    }

    #[Test]
    public function categoryRoundTripWorks(): void
    {
        $song = new Song(new Presentation());

        $song->setCategory('Worship');

        $this->assertSame('Worship', $song->getCategory());
    }

    #[Test]
    public function notesRoundTripWorks(): void
    {
        $song = new Song(new Presentation());

        $song->setNotes('Use acoustic intro');

        $this->assertSame('Use acoustic intro', $song->getNotes());
    }

    #[Test]
    public function selectedArrangementUuidRoundTripWorks(): void
    {
        $song = new Song(new Presentation());
        $uuid = '12345678-1234-4234-8234-123456789abc';

        $song->setSelectedArrangementUuid($uuid);

        $this->assertSame($uuid, $song->getSelectedArrangementUuid());
    }

    #[Test]
    public function ccliGettersReturnDefaultsWithoutCcliData(): void
    {
        $song = new Song(new Presentation());

        $this->assertSame('', $song->getCcliAuthor());
        $this->assertSame('', $song->getCcliSongTitle());
        $this->assertSame('', $song->getCcliPublisher());
        $this->assertSame(0, $song->getCcliCopyrightYear());
        $this->assertSame(0, $song->getCcliSongNumber());
        $this->assertFalse($song->getCcliDisplay());
        $this->assertSame('', $song->getCcliArtistCredits());
        $this->assertSame('', $song->getCcliAlbum());
    }
}

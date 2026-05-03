<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ProPresenter\Parser\PlaylistEntry;
use Rv\Data\PlaylistItem;
use Rv\Data\PlaylistItem\Header;
use Rv\Data\PlaylistItem\Presentation;
use Rv\Data\PlaylistItem\Placeholder;
use Rv\Data\Cue;
use Rv\Data\UUID;
use Rv\Data\URL;
use Rv\Data\Color;

class PlaylistEntryTest extends TestCase
{
    // ─── Helpers ───

    private function makePresentationItem(
        string $uuid = 'test-uuid',
        string $name = 'Test Song',
        ?string $documentPath = null,
        ?string $arrangementUuid = null,
        string $arrangementName = '',
    ): PlaylistItem {
        $item = new PlaylistItem();

        $itemUuid = new UUID();
        $itemUuid->setString($uuid);
        $item->setUuid($itemUuid);
        $item->setName($name);

        $pres = new Presentation();

        if ($documentPath !== null) {
            $url = new URL();
            $url->setAbsoluteString($documentPath);
            $pres->setDocumentPath($url);
        }

        if ($arrangementUuid !== null) {
            $arrUuid = new UUID();
            $arrUuid->setString($arrangementUuid);
            $pres->setArrangement($arrUuid);
        }

        if ($arrangementName !== '') {
            $pres->setArrangementName($arrangementName);
        }

        $item->setPresentation($pres);

        return $item;
    }

    private function makeHeaderItem(
        string $uuid = 'header-uuid',
        string $name = 'Section Header',
        ?array $color = null,
    ): PlaylistItem {
        $item = new PlaylistItem();

        $itemUuid = new UUID();
        $itemUuid->setString($uuid);
        $item->setUuid($itemUuid);
        $item->setName($name);

        $header = new Header();

        if ($color !== null) {
            $c = new Color();
            $c->setRed($color[0]);
            $c->setGreen($color[1]);
            $c->setBlue($color[2]);
            $c->setAlpha($color[3]);
            $header->setColor($c);
        }

        $item->setHeader($header);

        return $item;
    }

    private function makeCueItem(
        string $uuid = 'cue-uuid',
        string $name = 'Cue Item',
    ): PlaylistItem {
        $item = new PlaylistItem();

        $itemUuid = new UUID();
        $itemUuid->setString($uuid);
        $item->setUuid($itemUuid);
        $item->setName($name);

        $cue = new Cue();
        $item->setCue($cue);

        return $item;
    }

    private function makePlaceholderItem(
        string $uuid = 'placeholder-uuid',
        string $name = 'Placeholder Item',
    ): PlaylistItem {
        $item = new PlaylistItem();

        $itemUuid = new UUID();
        $itemUuid->setString($uuid);
        $item->setUuid($itemUuid);
        $item->setName($name);

        $placeholder = new Placeholder();
        $item->setPlaceholder($placeholder);

        return $item;
    }

    // ─── getUuid() ───

    #[Test]
    public function getUuidReturnsUuidString(): void
    {
        $item = $this->makePresentationItem(uuid: 'ABC-123-DEF');
        $entry = new PlaylistEntry($item);

        $this->assertSame('ABC-123-DEF', $entry->getUuid());
    }

    // ─── getName() ───

    #[Test]
    public function getNameReturnsItemName(): void
    {
        $item = $this->makePresentationItem(name: 'Amazing Grace');
        $entry = new PlaylistEntry($item);

        $this->assertSame('Amazing Grace', $entry->getName());
    }

    // ─── getType() ───

    #[Test]
    public function getTypeReturnsPresentationForPresentationItem(): void
    {
        $item = $this->makePresentationItem();
        $entry = new PlaylistEntry($item);

        $this->assertSame('presentation', $entry->getType());
    }

    #[Test]
    public function getTypeReturnsHeaderForHeaderItem(): void
    {
        $item = $this->makeHeaderItem();
        $entry = new PlaylistEntry($item);

        $this->assertSame('header', $entry->getType());
    }

    #[Test]
    public function getTypeReturnsCueForCueItem(): void
    {
        $item = $this->makeCueItem();
        $entry = new PlaylistEntry($item);

        $this->assertSame('cue', $entry->getType());
    }

    #[Test]
    public function getTypeReturnsPlaceholderForPlaceholderItem(): void
    {
        $item = $this->makePlaceholderItem();
        $entry = new PlaylistEntry($item);

        $this->assertSame('placeholder', $entry->getType());
    }

    // ─── Type checks ───

    #[Test]
    public function isPresentationReturnsTrueForPresentationItem(): void
    {
        $entry = new PlaylistEntry($this->makePresentationItem());

        $this->assertTrue($entry->isPresentation());
        $this->assertFalse($entry->isHeader());
        $this->assertFalse($entry->isCue());
        $this->assertFalse($entry->isPlaceholder());
    }

    #[Test]
    public function isHeaderReturnsTrueForHeaderItem(): void
    {
        $entry = new PlaylistEntry($this->makeHeaderItem());

        $this->assertTrue($entry->isHeader());
        $this->assertFalse($entry->isPresentation());
        $this->assertFalse($entry->isCue());
        $this->assertFalse($entry->isPlaceholder());
    }

    #[Test]
    public function isCueReturnsTrueForCueItem(): void
    {
        $entry = new PlaylistEntry($this->makeCueItem());

        $this->assertTrue($entry->isCue());
        $this->assertFalse($entry->isPresentation());
        $this->assertFalse($entry->isHeader());
        $this->assertFalse($entry->isPlaceholder());
    }

    #[Test]
    public function isPlaceholderReturnsTrueForPlaceholderItem(): void
    {
        $entry = new PlaylistEntry($this->makePlaceholderItem());

        $this->assertTrue($entry->isPlaceholder());
        $this->assertFalse($entry->isPresentation());
        $this->assertFalse($entry->isHeader());
        $this->assertFalse($entry->isCue());
    }

    // ─── Header: getHeaderColor() ───

    #[Test]
    public function getHeaderColorReturnsRgbaArrayForHeaderItem(): void
    {
        $item = $this->makeHeaderItem(color: [0.13, 0.59, 0.95, 1.0]);
        $entry = new PlaylistEntry($item);

        $color = $entry->getHeaderColor();
        $this->assertIsArray($color);
        $this->assertCount(4, $color);
        $this->assertEqualsWithDelta(0.13, $color[0], 0.01);
        $this->assertEqualsWithDelta(0.59, $color[1], 0.01);
        $this->assertEqualsWithDelta(0.95, $color[2], 0.01);
        $this->assertEqualsWithDelta(1.0, $color[3], 0.01);
    }

    #[Test]
    public function getHeaderColorReturnsNullForNonHeaderItem(): void
    {
        $entry = new PlaylistEntry($this->makePresentationItem());

        $this->assertNull($entry->getHeaderColor());
    }

    #[Test]
    public function getHeaderColorReturnsNullWhenHeaderHasNoColor(): void
    {
        $item = $this->makeHeaderItem(color: null);
        $entry = new PlaylistEntry($item);

        $this->assertNull($entry->getHeaderColor());
    }

    // ─── Presentation: document path ───

    #[Test]
    public function getDocumentPathReturnsFullUrl(): void
    {
        $item = $this->makePresentationItem(
            documentPath: 'file:///Users/me/Documents/ProPresenter/Libraries/Default/Song.pro',
        );
        $entry = new PlaylistEntry($item);

        $this->assertSame(
            'file:///Users/me/Documents/ProPresenter/Libraries/Default/Song.pro',
            $entry->getDocumentPath(),
        );
    }

    #[Test]
    public function getDocumentPathReturnsNullForNonPresentationItem(): void
    {
        $entry = new PlaylistEntry($this->makeHeaderItem());

        $this->assertNull($entry->getDocumentPath());
    }

    #[Test]
    public function getDocumentFilenameExtractsFilenameFromUrl(): void
    {
        $item = $this->makePresentationItem(
            documentPath: 'file:///Users/me/Documents/ProPresenter/Libraries/Default/Amazing%20Grace.pro',
        );
        $entry = new PlaylistEntry($item);

        $this->assertSame('Amazing Grace.pro', $entry->getDocumentFilename());
    }

    #[Test]
    public function getDocumentFilenameReturnsNullForNonPresentationItem(): void
    {
        $entry = new PlaylistEntry($this->makeCueItem());

        $this->assertNull($entry->getDocumentFilename());
    }

    // ─── Presentation: arrangement ───

    #[Test]
    public function getArrangementUuidReturnsUuidString(): void
    {
        $item = $this->makePresentationItem(arrangementUuid: 'ARR-UUID-123');
        $entry = new PlaylistEntry($item);

        $this->assertSame('ARR-UUID-123', $entry->getArrangementUuid());
    }

    #[Test]
    public function getArrangementNameReturnsFieldFiveValue(): void
    {
        $item = $this->makePresentationItem(arrangementName: 'normal');
        $entry = new PlaylistEntry($item);

        $this->assertSame('normal', $entry->getArrangementName());
    }

    #[Test]
    public function hasArrangementReturnsTrueWhenArrangementSet(): void
    {
        $item = $this->makePresentationItem(arrangementUuid: 'ARR-UUID-123');
        $entry = new PlaylistEntry($item);

        $this->assertTrue($entry->hasArrangement());
    }

    #[Test]
    public function hasArrangementReturnsFalseWhenNoArrangement(): void
    {
        $item = $this->makePresentationItem(arrangementUuid: null);
        $entry = new PlaylistEntry($item);

        $this->assertFalse($entry->hasArrangement());
    }

    #[Test]
    public function getArrangementNameReturnsNullForNonPresentationItem(): void
    {
        $entry = new PlaylistEntry($this->makeHeaderItem());

        $this->assertNull($entry->getArrangementName());
    }

    // ─── getPlaylistItem() ───

    #[Test]
    public function getPlaylistItemReturnsOriginalProto(): void
    {
        $item = $this->makePresentationItem();
        $entry = new PlaylistEntry($item);

        $this->assertSame($item, $entry->getPlaylistItem());
    }
}

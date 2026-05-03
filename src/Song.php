<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use Rv\Data\Presentation;
use Rv\Data\Presentation\CCLI;
use Rv\Data\UUID;

class Song
{
    private array $groups = [];
    private array $slides = [];
    private array $arrangements = [];
    private array $groupsByUuid = [];
    private array $slidesByUuid = [];
    private array $groupsByName = [];
    private array $arrangementsByName = [];

    public function __construct(
        private readonly Presentation $presentation,
    ) {
        foreach ($this->presentation->getCueGroups() as $cueGroup) {
            $group = new Group($cueGroup);
            $this->groups[] = $group;

            $groupUuid = strtoupper($group->getUuid());
            if ($groupUuid !== '') {
                $this->groupsByUuid[$groupUuid] = $group;
            }

            $this->groupsByName[$group->getName()] = $group;
        }

        foreach ($this->presentation->getCues() as $cue) {
            $slide = new Slide($cue);
            $this->slides[] = $slide;

            $slideUuid = strtoupper($slide->getUuid());
            if ($slideUuid !== '') {
                $this->slidesByUuid[$slideUuid] = $slide;
            }
        }

        foreach ($this->presentation->getArrangements() as $arrangementProto) {
            $arrangement = new Arrangement($arrangementProto);
            $this->arrangements[] = $arrangement;
            $this->arrangementsByName[$arrangement->getName()] = $arrangement;
        }
    }

    public function getUuid(): string
    {
        return $this->presentation->getUuid()?->getString() ?? '';
    }

    public function getName(): string
    {
        return $this->presentation->getName();
    }

    public function setName(string $name): void
    {
        $this->presentation->setName($name);
    }

    public function getCcliAuthor(): string
    {
        return $this->presentation->getCcli()?->getAuthor() ?? '';
    }

    public function setCcliAuthor(string $author): void
    {
        $this->ensureCcli()->setAuthor($author);
    }

    public function getCcliSongTitle(): string
    {
        return $this->presentation->getCcli()?->getSongTitle() ?? '';
    }

    public function setCcliSongTitle(string $title): void
    {
        $this->ensureCcli()->setSongTitle($title);
    }

    public function getCcliPublisher(): string
    {
        return $this->presentation->getCcli()?->getPublisher() ?? '';
    }

    public function setCcliPublisher(string $publisher): void
    {
        $this->ensureCcli()->setPublisher($publisher);
    }

    public function getCcliCopyrightYear(): int
    {
        return $this->presentation->getCcli()?->getCopyrightYear() ?? 0;
    }

    public function setCcliCopyrightYear(int $year): void
    {
        $this->ensureCcli()->setCopyrightYear($year);
    }

    public function getCcliSongNumber(): int
    {
        return $this->presentation->getCcli()?->getSongNumber() ?? 0;
    }

    public function setCcliSongNumber(int $number): void
    {
        $this->ensureCcli()->setSongNumber($number);
    }

    public function getCcliDisplay(): bool
    {
        return $this->presentation->getCcli()?->getDisplay() ?? false;
    }

    public function setCcliDisplay(bool $display): void
    {
        $this->ensureCcli()->setDisplay($display);
    }

    public function getCcliArtistCredits(): string
    {
        return $this->presentation->getCcli()?->getArtistCredits() ?? '';
    }

    public function setCcliArtistCredits(string $credits): void
    {
        $this->ensureCcli()->setArtistCredits($credits);
    }

    public function getCcliAlbum(): string
    {
        return $this->presentation->getCcli()?->getAlbum() ?? '';
    }

    public function setCcliAlbum(string $album): void
    {
        $this->ensureCcli()->setAlbum($album);
    }

    public function getCategory(): string
    {
        return $this->presentation->getCategory();
    }

    public function setCategory(string $category): void
    {
        $this->presentation->setCategory($category);
    }

    public function getNotes(): string
    {
        return $this->presentation->getNotes();
    }

    public function setNotes(string $notes): void
    {
        $this->presentation->setNotes($notes);
    }

    public function getSelectedArrangementUuid(): string
    {
        return $this->presentation->getSelectedArrangement()?->getString() ?? '';
    }

    public function setSelectedArrangementUuid(string $uuid): void
    {
        $arrangementUuid = new UUID();
        $arrangementUuid->setString($uuid);
        $this->presentation->setSelectedArrangement($arrangementUuid);
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getGroupByName(string $name): ?Group
    {
        return $this->groupsByName[$name] ?? null;
    }

    public function getSlides(): array
    {
        return $this->slides;
    }

    public function getSlideByUuid(string $uuid): ?Slide
    {
        return $this->slidesByUuid[strtoupper($uuid)] ?? null;
    }

    public function getArrangements(): array
    {
        return $this->arrangements;
    }

    public function getArrangementByName(string $name): ?Arrangement
    {
        return $this->arrangementsByName[$name] ?? null;
    }

    public function getSlidesForGroup(Group $group): array
    {
        $slides = [];

        foreach ($group->getSlideUuids() as $slideUuid) {
            $slide = $this->slidesByUuid[strtoupper($slideUuid)] ?? null;
            if ($slide !== null) {
                $slides[] = $slide;
            }
        }

        return $slides;
    }

    public function getGroupsForArrangement(Arrangement $arrangement): array
    {
        $groups = [];

        foreach ($arrangement->getGroupUuids() as $groupUuid) {
            $group = $this->groupsByUuid[strtoupper($groupUuid)] ?? null;
            if ($group !== null) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    public function getPresentation(): Presentation
    {
        return $this->presentation;
    }

    private function ensureCcli(): CCLI
    {
        $ccli = $this->presentation->getCcli();
        if ($ccli === null) {
            $ccli = new CCLI();
            $this->presentation->setCcli($ccli);
        }

        return $ccli;
    }
}

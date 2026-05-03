<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use Rv\Data\ApplicationInfo;
use Rv\Data\ApplicationInfo\Application;
use Rv\Data\ApplicationInfo\Platform;
use Rv\Data\Color;
use Rv\Data\MusicKeyScale;
use Rv\Data\MusicKeyScale\MusicKey;
use Rv\Data\Playlist;
use Rv\Data\Playlist\PlaylistArray;
use Rv\Data\Playlist\PlaylistItems;
use Rv\Data\PlaylistDocument;
use Rv\Data\PlaylistDocument\Type as PlaylistDocumentType;
use Rv\Data\PlaylistItem;
use Rv\Data\PlaylistItem\Header;
use Rv\Data\PlaylistItem\Placeholder;
use Rv\Data\PlaylistItem\Presentation;
use Rv\Data\URL;
use Rv\Data\UUID;
use Rv\Data\Version;

final class ProPlaylistGenerator
{
    public static function generate(
        string $name,
        array $items,
        array $embeddedFiles = [],
    ): PlaylistArchive {
        $document = new PlaylistDocument();
        $document->setApplicationInfo(self::buildApplicationInfo());
        $document->setType(PlaylistDocumentType::TYPE_PRESENTATION);

        $rootPlaylist = new Playlist();
        $rootPlaylist->setUuid(self::newUuid());
        $rootPlaylist->setName('PLAYLIST');
        $rootPlaylist->setType(Playlist\Type::TYPE_PLAYLIST);

        $playlist = new Playlist();
        $playlist->setUuid(self::newUuid());
        $playlist->setName($name);
        $playlist->setType(Playlist\Type::TYPE_PLAYLIST);

        $playlistItems = new PlaylistItems();
        $itemMessages = [];
        foreach ($items as $itemData) {
            $itemMessages[] = self::buildPlaylistItem($itemData);
        }
        $playlistItems->setItems($itemMessages);
        $playlist->setItems($playlistItems);

        $playlistArray = new PlaylistArray();
        $playlistArray->setPlaylists([$playlist]);
        $rootPlaylist->setPlaylists($playlistArray);

        $document->setRootNode($rootPlaylist);

        return new PlaylistArchive($document, $embeddedFiles);
    }

    public static function generateAndWrite(
        string $filePath,
        string $name,
        array $items,
        array $embeddedFiles = [],
    ): PlaylistArchive {
        $archive = self::generate($name, $items, $embeddedFiles);
        ProPlaylistWriter::write($archive, $filePath);

        return $archive;
    }

    private static function buildPlaylistItem(array $data): PlaylistItem
    {
        $item = new PlaylistItem();
        $item->setUuid(self::newUuid());
        $item->setName((string) ($data['name'] ?? ''));

        $type = (string) ($data['type'] ?? '');
        switch ($type) {
            case 'header':
                $header = new Header();
                $header->setColor(self::colorFromArray($data['color'] ?? []));
                $item->setHeader($header);
                break;

            case 'presentation':
                $presentation = new Presentation();
                $presentation->setDocumentPath(self::urlFromString((string) ($data['path'] ?? '')));
                if (isset($data['arrangement_uuid'])) {
                    $presentation->setArrangement(self::uuidFromString((string) $data['arrangement_uuid']));
                }
                if (isset($data['arrangement_name'])) {
                    $presentation->setArrangementName((string) $data['arrangement_name']);
                }

                $musicKey = new MusicKeyScale();
                $musicKey->setMusicKey(MusicKey::MUSIC_KEY_C);
                $presentation->setUserMusicKey($musicKey);

                $item->setPresentation($presentation);
                break;

            case 'placeholder':
                $item->setPlaceholder(new Placeholder());
                break;

            default:
                throw new InvalidArgumentException(sprintf('Unsupported playlist item type: %s', $type));
        }

        return $item;
    }

    private static function urlFromString(string $path): URL
    {
        $url = new URL();
        $url->setAbsoluteString($path);

        return $url;
    }

    private static function buildApplicationInfo(): ApplicationInfo
    {
        $version = new Version();
        $version->setBuild('335544354');

        $applicationInfo = new ApplicationInfo();
        $applicationInfo->setPlatform(Platform::PLATFORM_MACOS);
        $applicationInfo->setApplication(Application::APPLICATION_PROPRESENTER);
        $applicationInfo->setPlatformVersion($version);
        $applicationInfo->setApplicationVersion($version);

        return $applicationInfo;
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

    private static function colorFromArray(array $rgba): Color
    {
        $color = new Color();
        $color->setRed((float) ($rgba[0] ?? 0.0));
        $color->setGreen((float) ($rgba[1] ?? 0.0));
        $color->setBlue((float) ($rgba[2] ?? 0.0));
        $color->setAlpha((float) ($rgba[3] ?? 1.0));

        return $color;
    }

    private static function newUuidString(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}

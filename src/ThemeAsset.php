<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

class ThemeAsset
{
    public function __construct(
        private readonly string $name,
        private readonly string $bytes,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBytes(): string
    {
        return $this->bytes;
    }

    public function getSize(): int
    {
        return strlen($this->bytes);
    }

    public function getMimeType(): string
    {
        return match (strtolower(pathinfo($this->name, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            default => 'application/octet-stream',
        };
    }
}

<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

use InvalidArgumentException;
use RuntimeException;

final class Zip64Fixer
{
    private const EOCD_SIGNATURE = "\x50\x4b\x05\x06";
    private const ZIP64_EOCD_SIGNATURE = "\x50\x4b\x06\x06";
    private const ZIP64_LOCATOR_SIGNATURE = "\x50\x4b\x06\x07";

    public static function fix(string $zipData): string
    {
        if ($zipData === '') {
            throw new InvalidArgumentException('ZIP data must not be empty.');
        }

        $length = strlen($zipData);
        if ($length < 22) {
            throw new RuntimeException('ZIP data is too small to contain EOCD.');
        }

        $eocdPosition = self::findLastSignature($zipData, self::EOCD_SIGNATURE);
        if ($eocdPosition < 0) {
            throw new RuntimeException('EOCD signature not found in ZIP data.');
        }

        if ($eocdPosition + 22 > $length) {
            throw new RuntimeException('EOCD record is truncated.');
        }

        $locatorPosition = self::findLastSignatureBefore($zipData, self::ZIP64_LOCATOR_SIGNATURE, $eocdPosition);
        if ($locatorPosition < 0) {
            $cdOffset = self::readUInt32LE($zipData, $eocdPosition + 16);
            if ($cdOffset === 0xFFFFFFFF) {
                throw new RuntimeException('ZIP64 EOCD locator not found.');
            }

            return $zipData;
        }

        if ($locatorPosition + 20 > $length) {
            throw new RuntimeException('ZIP64 EOCD locator is truncated.');
        }

        $zip64EocdPosition = self::readUInt64LE($zipData, $locatorPosition + 8);
        if ($zip64EocdPosition < 0 || $zip64EocdPosition + 56 > $length) {
            throw new RuntimeException('ZIP64 EOCD position is out of bounds.');
        }

        if (substr($zipData, $zip64EocdPosition, 4) !== self::ZIP64_EOCD_SIGNATURE) {
            throw new RuntimeException('ZIP64 EOCD signature not found at locator position.');
        }

        $zip64CdOffset = self::readUInt64LE($zipData, $zip64EocdPosition + 48);
        $correctCdSize = $zip64EocdPosition - $zip64CdOffset;
        if ($correctCdSize < 0) {
            throw new RuntimeException('Computed central directory size is invalid.');
        }

        $zipData = substr_replace($zipData, self::writeUInt64LE($correctCdSize), $zip64EocdPosition + 40, 8);

        $regularCdSize = $correctCdSize > 0xFFFFFFFF ? 0xFFFFFFFF : (int) $correctCdSize;
        return substr_replace($zipData, pack('V', $regularCdSize), $eocdPosition + 12, 4);
    }

    private static function findLastSignature(string $data, string $signature): int
    {
        $position = strrpos($data, $signature);
        if ($position === false) {
            return -1;
        }

        return $position;
    }

    private static function findLastSignatureBefore(string $data, string $signature, int $before): int
    {
        if ($before <= 0) {
            return -1;
        }

        $slice = substr($data, 0, $before);
        $position = strrpos($slice, $signature);
        if ($position === false) {
            return -1;
        }

        return $position;
    }

    private static function readUInt32LE(string $data, int $offset): int
    {
        $chunk = substr($data, $offset, 4);
        if (strlen($chunk) !== 4) {
            throw new RuntimeException('Unable to read 32-bit little-endian integer.');
        }

        $value = unpack('V', $chunk);
        if ($value === false) {
            throw new RuntimeException('Unable to unpack 32-bit little-endian integer.');
        }

        return (int) $value[1];
    }

    private static function readUInt64LE(string $data, int $offset): int
    {
        $chunk = substr($data, $offset, 8);
        if (strlen($chunk) !== 8) {
            throw new RuntimeException('Unable to read 64-bit little-endian integer.');
        }

        $parts = unpack('Vlow/Vhigh', $chunk);
        if ($parts === false) {
            throw new RuntimeException('Unable to unpack 64-bit little-endian integer.');
        }

        return (int) ($parts['high'] * 4294967296 + $parts['low']);
    }

    private static function writeUInt64LE(int $value): string
    {
        if ($value < 0) {
            throw new RuntimeException('Unable to encode negative 64-bit value.');
        }

        $low = $value & 0xFFFFFFFF;
        $high = intdiv($value, 4294967296);

        return pack('V2', $low, $high);
    }
}

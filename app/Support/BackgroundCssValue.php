<?php

declare(strict_types=1);

namespace App\Support;

final class BackgroundCssValue
{
    private const string POSITION_TOKEN_PATTERN = '/\A(?:0|-?(?:\d+|\d*\.\d+)(?:%|px|rem|em|vh|vw)|left|right|top|bottom|center)\z/i';
    private const string SIZE_TOKEN_PATTERN = '(?:0|(?:\d+|\d*\.\d+)(?:%|px|rem|em|vh|vw)|auto)';

    public static function isPositionToken(mixed $value): bool
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match(self::POSITION_TOKEN_PATTERN, trim((string) $value)) === 1;
    }

    public static function isSize(mixed $value): bool
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $value = trim((string) $value);

        if (in_array(strtolower($value), ['cover', 'contain'], true)) {
            return true;
        }

        return preg_match('/\A' . self::SIZE_TOKEN_PATTERN . '(?:\s+' . self::SIZE_TOKEN_PATTERN . ')?\z/i', $value) === 1;
    }

    public static function normalizePositionToken(mixed $value, string $fallback = '50%'): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return $fallback;
        }

        $value = trim((string) $value);

        return self::isPositionToken($value) ? $value : $fallback;
    }

    public static function normalizeSize(mixed $value, string $fallback = 'cover'): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return $fallback;
        }

        $value = trim((string) $value);

        return self::isSize($value) ? $value : $fallback;
    }
}

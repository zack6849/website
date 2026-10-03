<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Single owner of the POTA "known unresolvable reference" cache keys, so
 * ParksOnTheAirService never touches raw Cache facade keys directly (mirrors
 * Logbook\LogbookCache / Backgrounds\BackgroundSelectionCache).
 */
class PotaUnresolvedReferenceCache
{
    private const string KEY_PREFIX = 'pota:unresolved:';
    private const int TTL_DAYS = 30;

    public function has(string $reference): bool
    {
        return Cache::has(self::key($reference));
    }

    public function remember(string $reference): void
    {
        Cache::put(self::key($reference), true, now()->addDays(self::TTL_DAYS));
    }

    private static function key(string $reference): string
    {
        return self::KEY_PREFIX . $reference;
    }
}

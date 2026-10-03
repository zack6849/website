<?php

declare(strict_types=1);

namespace App\Services\Logbook;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Single owner of the logbook cache keys so callers never touch raw
 * Cache facade keys directly (mirrors Backgrounds\BackgroundSelectionCache).
 */
class LogbookCache
{
    private const string ENTRIES_KEY = 'logbook';
    private const string LAST_IMPORTED_AT_KEY = 'logbook:last_imported_at';

    /**
     * Cache fetched logbook records for slightly under a day, so a same-time
     * daily run never finds yesterday's cache entry still valid due to
     * run-time drift.
     */
    public function rememberEntries(callable $resolver): array
    {
        return Cache::remember(self::ENTRIES_KEY, now()->addHours(23), $resolver);
    }

    public function forgetEntries(): void
    {
        Cache::forget(self::ENTRIES_KEY);
    }

    public function recordImportCompleted(): void
    {
        Cache::forever(self::LAST_IMPORTED_AT_KEY, now()->timestamp);
    }

    public function lastImportedAt(): ?Carbon
    {
        $timestamp = Cache::get(self::LAST_IMPORTED_AT_KEY);

        if (! is_numeric($timestamp)) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $timestamp, 'UTC');
    }
}

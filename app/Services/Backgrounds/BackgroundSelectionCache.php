<?php

declare(strict_types=1);

namespace App\Services\Backgrounds;

use Illuminate\Support\Facades\Cache;

class BackgroundSelectionCache
{
    private const string CACHE_KEY = 'home-background';
    private const int CACHE_TTL_SECONDS = 3600;
    private const string CACHE_MISS = '__background_selection_cache_miss__';
    private const string NULL_SELECTION = '__background_selection_cache_null__';

    public function remember(callable $resolver): mixed
    {
        $cached = Cache::get($this->key(), self::CACHE_MISS);

        if ($cached !== self::CACHE_MISS) {
            return $cached === self::NULL_SELECTION ? null : $cached;
        }

        $value = $resolver();

        Cache::put(
            $this->key(),
            $value === null ? self::NULL_SELECTION : $value,
            now()->addSeconds($this->ttlSeconds()),
        );

        return $value;
    }

    public function forget(): void
    {
        Cache::forget($this->key());
    }

    private function key(): string
    {
        return self::CACHE_KEY;
    }

    private function ttlSeconds(): int
    {
        return (int) config('backgrounds.cache.ttl_seconds', self::CACHE_TTL_SECONDS);
    }
}

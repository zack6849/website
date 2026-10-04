<?php

declare(strict_types=1);

namespace App\Services\Backgrounds;

use App\Models\Background;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BackgroundSelector
{
    private const string FALLBACK_KEY = 'pier_night';
    private const array FALLBACK_BACKGROUND = [
        'title' => 'St. Petersburg Pier',
        'image' => BackgroundNormalizer::DEFAULT_IMAGE,
        'description' => 'Night shot of the St. Petersburg Pier.',
        'position' => [
            'x' => '50%',
            'y' => '34%',
        ],
        'overlay' => BackgroundNormalizer::DEFAULT_OVERLAY,
        'size' => BackgroundNormalizer::DEFAULT_SIZE,
    ];

    public function __construct(
        private readonly BackgroundSelectionCache $cache,
        private readonly BackgroundScheduleMatcher $scheduleMatcher,
    ) {
    }

    /**
     * @return array{item: array, key: string}
     */
    public function select(): array
    {
        return $this->selectFromDatabase() ?? [
            'item' => self::FALLBACK_BACKGROUND,
            'key' => self::FALLBACK_KEY,
        ];
    }

    /**
     * Resolve the active background from the database when the table exists and
     * has at least one enabled row. Precedence:
     *   1. a pinned background (manual override)
     *   2. a themed/scheduled background matching today
     *   3. a weighted random background among all enabled rows
     *
     * The whole lookup (including the enabled-rows query and the pinned check)
     * is wrapped in the selection cache so a cache hit costs zero DB queries;
     * the Background model busts the cache on every save/delete, so a newly
     * pinned/enabled/disabled row is reflected immediately.
     *
     * @return array{item: array, key: string}|null
     */
    private function selectFromDatabase(): ?array
    {
        $result = $this->cache->remember(function (): ?array {
            if (! Schema::hasTable('backgrounds')) {
                return null;
            }

            $enabled = Background::query()->enabled()->get();

            if ($enabled->isEmpty()) {
                return null;
            }

            $pinned = $enabled->where('pinned', true)->sortByDesc('updated_at')->first();
            $selected = $pinned instanceof Background ? $pinned : $this->pickForToday($enabled);

            return $this->modelSelection($selected);
        });

        return is_array($result) ? $result : null;
    }

    /**
     * @return array{item: array, key: string}
     */
    private function modelSelection(Background $background): array
    {
        return [
            'item' => $background->toDefinition(),
            'key' => (string) $background->getKey(),
        ];
    }

    /**
     * @param  Collection<int, Background>  $enabled
     */
    private function pickForToday(Collection $enabled): Background
    {
        $today = Carbon::today();

        $scheduled = $enabled->filter(
            fn (Background $background): bool => $this->scheduleMatcher->matches($background->schedule, $today),
        );

        $pool = $scheduled->isNotEmpty() ? $scheduled : $enabled;

        return $this->weightedRandom($pool);
    }

    /**
     * @param  Collection<int, Background>  $pool
     */
    private function weightedRandom(Collection $pool): Background
    {
        $totalWeight = (int) $pool->sum(fn (Background $background): int => max(1, (int) $background->weight));

        if ($totalWeight <= 0) {
            return $pool->first();
        }

        $roll = random_int(1, $totalWeight);
        $cursor = 0;

        foreach ($pool as $background) {
            $cursor += max(1, (int) $background->weight);

            if ($roll <= $cursor) {
                return $background;
            }
        }

        return $pool->last();
    }

}

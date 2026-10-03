<?php

declare(strict_types=1);

namespace App\Services\Logbook;

use App\Http\Resources\LogbookEntryResource;
use App\Models\LogbookEntry;
use GeoJson\Feature\Feature;
use GeoJson\Geometry\Point;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LogbookGeoJsonService
{
    public const int DEFAULT_LIMIT = 200;
    public const string DEFAULT_SORT = 'newest';
    private const int MAX_LIMIT = 500;
    private const array SORTS = [
        'newest',
        'oldest',
        'distance_desc',
        'distance_asc',
    ];

    public function __construct(
        private readonly LogbookCache $logbookCache,
    ) {
    }

    public function getGeoJSON(
        string $band = '20M',
        string $mode = 'SSB',
        string $search = '',
        int $limit = self::DEFAULT_LIMIT,
        string $sort = self::DEFAULT_SORT,
    ): array {
        $limit = $this->normalizeLimit($limit);
        $sort = $this->normalizeSort($sort);

        $query = $this->buildQuery($band, $mode, $search);
        $total = (clone $query)->count();

        $this->applySort($query, $sort);
        $query->limit($limit);

        $features = $query->get()->map(function (LogbookEntry $entry): Feature {
            $location = new Point([floatval($entry->to_longitude), floatval($entry->to_latitude)]);
            $resource = new LogbookEntryResource($entry);

            return new Feature($location, $resource->resolve());
        })->all();

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
            'meta' => [
                'total' => $total,
                'returned' => count($features),
                'limit' => $limit,
                'sort' => $sort,
                'last_imported_at' => $this->logbookCache->lastImportedAt()?->utc()->toIso8601String(),
            ],
        ];
    }

    private function buildQuery(string $band, string $mode, string $search): Builder
    {
        $query = LogbookEntry::query()
            ->with(['callee', 'park'])
            ->where('hidden_from_public', false)
            ->whereNotNull('to_latitude')
            ->whereNotNull('to_longitude');

        if (! $this->isAllFilter($band)) {
            $query->where('band', strtoupper($band));
        }

        if (! $this->isAllFilter($mode)) {
            $query->where('mode', strtoupper($mode));
        }

        if ($search !== '') {
            $this->applySearchFilter($query, $search);
        }

        return $query;
    }

    private function applySearchFilter(Builder $query, string $search): void
    {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search) . '%';

        $query->where(function (Builder $query) use ($like) {
            $query->where('band', 'like', $like)
                ->orWhere('mode', 'like', $like)
                ->orWhere('frequency', 'like', $like)
                ->orWhere('to_grid', 'like', $like)
                ->orWhere('to_city', 'like', $like)
                ->orWhere('to_state', 'like', $like)
                ->orWhere('to_county', 'like', $like)
                ->orWhere('distance', 'like', $like)
                ->orWhere('comments', 'like', $like)
                ->orWhere('category', 'like', $like)
                ->orWhereHas('callee', function (Builder $query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('country', 'like', $like);
                })
                ->orWhereHas('park', function (Builder $query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('reference', 'like', $like)
                        ->orWhere('location', 'like', $like);
                });
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query
                ->orderBy('created_at')
                ->orderBy('id'),
            'distance_desc' => $query
                ->orderByRaw('distance is null')
                ->orderByDesc('distance')
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            'distance_asc' => $query
                ->orderByRaw('distance is null')
                ->orderBy('distance')
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            default => $query
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        };
    }

    private function normalizeLimit(int $limit): int
    {
        return max(1, min($limit, self::MAX_LIMIT));
    }

    private function normalizeSort(string $sort): string
    {
        $sort = strtolower($sort);

        if (! in_array($sort, self::SORTS, true)) {
            return self::DEFAULT_SORT;
        }

        return $sort;
    }

    private function isAllFilter(string $value): bool
    {
        $value = strtolower(trim($value));

        return $value === '' || $value === 'all' || $value === '*';
    }

    public function getWorkedModes(): Collection
    {
        return LogbookEntry::query()->select('mode')
            ->distinct()
            ->orderBy('mode')
            ->get()
            ->pluck('mode');
    }

    public function getWorkedBands(): Collection
    {
        return LogbookEntry::query()->select('band')
            ->distinct()
            ->orderBy('band')
            ->get()
            ->pluck('band');
    }
}

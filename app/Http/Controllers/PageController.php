<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\HomeBackgroundService;
use Illuminate\Contracts\Support\Renderable;

class PageController extends Controller
{
    public function __construct(
        private readonly HomeBackgroundService $homeBackgroundService,
    ) {
    }

    public function home(): Renderable
    {
        $normalizeTechImages = function (array $tech) {
            if (isset($tech['image'])) {
                $tech['image'] = asset($tech['image']);
            }

            return $tech;
        };

        $techCategories = collect(config('techstack.categories'))
            ->map(fn (array $items) => collect($items)->map($normalizeTechImages)->all())
            ->all();

        return view('pages.home', [
            'techCategories' => $techCategories,
            'exploringTech' => collect(config('techstack.exploring', []))->map($normalizeTechImages)->all(),
            'projects' => config('projects'),
            'background' => $this->homeBackgroundService->calculateCurrentBackgroundInfo(),
        ]);
    }

    public function photos(): Renderable
    {
        return view('pages.photography');
    }

    public function radio(): Renderable
    {
        $radioOrigin = [
            'lat' => (float) config('radio.origin.latitude'),
            'lng' => (float) config('radio.origin.longitude'),
        ];

        return view('pages.radio-contact-map', [
            'mapConfig' => array_merge($radioOrigin, ['zoom' => 2.5]),
            'qth' => array_merge($radioOrigin, ['label' => 'Approx. QTH']),
        ]);
    }
}

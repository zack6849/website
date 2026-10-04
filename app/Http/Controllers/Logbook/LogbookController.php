<?php
declare(strict_types=1);

namespace App\Http\Controllers\Logbook;

use App\Http\Controllers\Controller;
use App\Services\Logbook\LogbookGeoJsonService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LogbookController extends Controller
{
    public function __construct(
        private readonly LogbookGeoJsonService $geoJsonService,
    ) {
    }

    public function getGeoJSON(Request $request, $band = '20M', $mode = 'SSB'): array
    {
        return $this->geoJsonService->getGeoJSON(
            band: (string) $request->query('band', $band),
            mode: (string) $request->query('mode', $mode),
            search: trim((string) $request->query('search', '')),
            limit: (int) $request->query('limit', LogbookGeoJsonService::DEFAULT_LIMIT),
            sort: (string) $request->query('sort', LogbookGeoJsonService::DEFAULT_SORT),
        );
    }

    public function getWorkedModes(): Collection
    {
        return $this->geoJsonService->getWorkedModes();
    }

    public function getWorkedBands(): Collection
    {
        return $this->geoJsonService->getWorkedBands();
    }
}

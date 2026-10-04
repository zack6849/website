<?php

namespace App\Jobs\Logbook;

use App\Models\Callsign;
use App\Models\LogbookEntry;
use App\Models\LogbookEntryVisibilityOverride;
use App\Models\POTAPark;
use App\Services\Logbook\LogbookCache;
use App\Services\LogbookEntryIdentity;
use App\Services\ParksOnTheAirService;
use App\Services\QRZLogbookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class QRZLogbookImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private QRZLogbookService $logbookService;
    private ParksOnTheAirService $potaService;
    private LogbookEntryIdentity $entryIdentity;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        QRZLogbookService             $logbookProvider,
        ParksOnTheAirService          $parksOnTheAirServiceProvider,
        LogbookEntryIdentity          $entryIdentity,
        LogbookCache                  $logbookCache,
    )
    {
        $this->logbookService = $logbookProvider;
        $this->potaService = $parksOnTheAirServiceProvider;
        $this->entryIdentity = $entryIdentity;
        $records = $logbookCache->rememberEntries(function() {
          return $this->logbookService->getLogbookEntries();
        });
        //make sure we don't clobber the db if we don't get any records back
        if(!empty($records)){
            //resolve/cache every distinct POTA park referenced in this batch *before* opening
            //the transaction, so the blocking HTTP calls to api.pota.app for uncached parks
            //don't happen while DB locks are held (saveRecord()'s getParkInfo() calls below
            //hit the now-warm pota_parks table instead of the network)
            $this->warmParkCache($records);
            DB::transaction(function () use ($records, $logbookCache) {
                LogbookEntry::query()->whereNotNull('created_at')->delete();
                foreach ($records as $record) {
                    $this->saveRecord($record);
                }

                $logbookCache->recordImportCompleted();
            });
        }

    }

    private function warmParkCache(array $records): void
    {
        $references = [];
        foreach ($records as $record) {
            $reference = $this->extractParkReference(Arr::get($record, 'COMMENT', ''));
            if ($reference !== null) {
                $references[] = $reference;
            }
        }
        $uniqueReferences = array_values(array_unique($references));
        if (empty($uniqueReferences)) {
            return;
        }
        //one query to find what's already cached, instead of one SELECT per reference
        foreach ($this->potaService->filterUncachedReferences($uniqueReferences) as $reference) {
            $this->potaService->getParkInfo($reference);
        }
    }

    private function saveRecord(array $record) : void
    {
        $theirCall = Callsign::query()->firstOrCreate([
            'name' => $record['CALL']
        ], [
            'country' => $record['COUNTRY']
        ]);

        $myCall = Callsign::firstOrCreate([
            'name' => $record['STATION_CALLSIGN']
        ], [
            'country' => $record['MY_COUNTRY']
        ]);

        $myLat = $this->transformCoordinates(Arr::get($record, 'MY_LAT'));
        $myLon = $this->transformCoordinates(Arr::get($record, 'MY_LON'));
        if ($this->isUnknownLocationPlaceholder($myLat, $myLon)) {
            $myLat = null;
            $myLon = null;
        }
        $park = $this->getRelatedPark($record);
        $grid = $this->getGridSquare($record, $park);
        list($theirLat, $theirLon) = $this->getLatLong($record, $park);
        $qrzLogId = $this->qrzLogId($record);
        $entryKey = $this->entryIdentity->forRecord($record);
        $entry = LogbookEntry::make([
            'qrz_logid' => $qrzLogId,
            'entry_key' => $entryKey,
            'from_callsign' => $myCall->id,
            'to_callsign' => $theirCall->id,
            'to_city' => $this->optionalString(Arr::get($record, 'QTH')),
            'to_state' => $this->optionalString(Arr::get($record, 'STATE')),
            'to_county' => $this->optionalString(Arr::get($record, 'CNTY')),
            'frequency' => Arr::get($record, 'FREQ'),
            'band' => strtoupper(Arr::get($record, 'BAND')),
            'mode' => strtoupper(Arr::get($record, 'MODE')),
            'rst_sent' => Arr::get($record, 'RST_SENT'),
            'rst_received' => Arr::get($record, 'RST_RCVD'),
            'from_grid' => Arr::get($record, 'MY_GRIDSQUARE'),
            'from_coordinates' => $this->combineCoordinates($myLat, $myLon),
            'from_latitude' => $myLat,
            'from_longitude' => $myLon,
            'to_grid' => $grid,
            'to_coordinates' => $this->combineCoordinates($theirLat, $theirLon),
            'to_latitude' => $theirLat,
            'to_longitude' => $theirLon,
            'distance' => Arr::get($record, 'DISTANCE'),
            'comments' => Arr::get($record, 'COMMENT')
        ]);
        $timestamp = Carbon::createFromFormat('YmdHi', $record['QSO_DATE'] . $record['TIME_ON']);
        $entry->created_at = $timestamp;
        if($park !== null){
            $entry->park_id = $park->id;
            $entry->category = "POTA";
        }
        $this->applyVisibilityOverride($entry);
        $entry->save();
    }

    private function applyVisibilityOverride(LogbookEntry $entry): void
    {
        $override = null;

        if ($entry->qrz_logid !== null) {
            $override = LogbookEntryVisibilityOverride::query()
                ->where('qrz_logid', $entry->qrz_logid)
                ->first();
        }

        $override ??= LogbookEntryVisibilityOverride::query()
            ->where('entry_key', $entry->entry_key)
            ->first();

        if ($override !== null) {
            $entry->hidden_from_public = $override->hidden_from_public;
        }
    }

    private function qrzLogId(array $record): ?string
    {
        $logId = Arr::get($record, 'APP_QRZLOG_LOGID')
            ?? Arr::get($record, 'app_qrzlog_logid');

        if ($logId === null || trim((string) $logId) === '') {
            return null;
        }

        return trim((string) $logId);
    }

    private function optionalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Parses an ADIF "N40 00.000"-style coordinate into a decimal-degrees string.
     *
     * Returns null only when the value can't be parsed. A genuinely zero
     * latitude or longitude is a real position (equator, prime meridian) -
     * detecting QRZ's "N000 00.000" unknown-location placeholder requires
     * looking at the lat/lon *pair* together, which callers do via
     * isUnknownLocationPlaceholder() once both axes are known.
     */
    public function transformCoordinates($coordinates): ?string
    {
        $matches = [];
        preg_match('/([NSEW])(\d+)\s(\d+)\.?(\d+)/', (string) $coordinates, $matches);
        array_shift($matches);
        $direction = array_shift($matches);
        $mainLocator = array_shift($matches);

        if ($direction === null || $mainLocator === null) {
            return null;
        }

        $prefix = in_array($direction, ['W', 'S'], true) ? '-' : '';

        return number_format((float) ("$prefix$mainLocator." . implode('', $matches)), 5, '.', '');
    }

    /**
     * QRZ fills unknown locations with "N000 00.000"/"E000 00.000" rather than
     * omitting the field, which transformCoordinates() parses as a legitimate
     * (0, 0). A real amateur radio contact doesn't sit at exactly (0, 0), so
     * treat that specific pair - not either axis alone - as "unknown".
     */
    private function isUnknownLocationPlaceholder(?string $lat, ?string $lon): bool
    {
        return $lat !== null && $lon !== null && (float) $lat === 0.0 && (float) $lon === 0.0;
    }

    private function getGridSquare(array $adifEntry, ?POTAPark $park) : string
    {
        $grid = Arr::get($adifEntry, 'GRIDSQUARE', '');
        if($park !== null){
            if (!empty(trim($park->grid4))) {
                $grid = $park->grid4;
            }
            if (!empty(trim($park->grid6))) {
                $grid = $park->grid6;
            }
        }
        return $grid;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function getLatLong(array $record, ?POTAPark $park) : array
    {
        $lat = $this->transformCoordinates(Arr::get($record, 'LAT'));
        $lon = $this->transformCoordinates(Arr::get($record, 'LON'));
        if ($park !== null) {
            $lat = $park->latitude;
            $lon = $park->longitude;
        }

        if ($this->isUnknownLocationPlaceholder($lat, $lon)) {
            return [null, null];
        }

        return [$lat, $lon];
    }

    private function combineCoordinates(?string $lat, ?string $lon): ?string
    {
        return $lat !== null && $lon !== null ? "$lat,$lon" : null;
    }

    public function getRelatedPark(array $adifEntry){
        $parks = $this->getParks($adifEntry);
        if(!empty($parks)){
            return array_shift($parks);
        }
        return null;
    }

    /**
     * @param array $adifEntry
     * @return POTAPark[] a list of parks in the comments field
     */
    public function getParks(array $adifEntry) : array
    {
        $comment = Arr::get($adifEntry, 'COMMENT', '');
        return $this->findValidParks($comment);
    }

    private function findValidParks(string $comment) : array
    {
        $realParks = [];
        $reference = $this->extractParkReference($comment);
        if ($reference !== null) {
            $park = $this->potaService->getParkInfo($reference);
            if ($park !== false) {
                $realParks[] = $park;
            }
        }
        return $realParks;
    }

    private function extractParkReference(string $comment): ?string
    {
        if (preg_match('/([a-zA-Z]+-\d+)/', $comment, $matches)) {
            return $matches[1];
        }
        return null;
    }
}

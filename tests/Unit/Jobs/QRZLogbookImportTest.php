<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\Logbook\QRZLogbookImport;
use App\Models\Callsign;
use App\Models\LogbookEntry;
use App\Models\LogbookEntryVisibilityOverride;
use App\Services\LogbookEntryIdentity;
use App\Services\ParksOnTheAirService;
use App\Services\QRZLogbookService;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QRZLogbookImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        //the 'array' cache driver persists for the whole PHPUnit process, not per-test;
        //without this, a 'logbook' key warmed by an earlier test would make
        //QRZLogbookService::getLogbookEntries() never get called here
        Cache::forget('logbook');
        Cache::forget('logbook:last_imported_at');
    }

    #[Test]
    public function rollsBackAllChangesWhenARecordFailsToImport(): void
    {
        $callsign = Callsign::factory()->create(['name' => 'N0CALL', 'country' => 'USA']);
        $existing = LogbookEntry::factory()->create([
            'from_callsign' => $callsign->id,
            'to_callsign' => $callsign->id,
            'frequency' => 14.250,
            'band' => '20m',
            'mode' => 'SSB',
            'from_grid' => 'FN20',
            'from_coordinates' => '40.00000,-75.00000',
            'from_latitude' => '40.00000',
            'from_longitude' => '-75.00000',
            'to_latitude' => '41.00000',
            'to_longitude' => '-72.00000',
        ]);

        $goodRecord = $this->adifRecord(['COMMENT' => 'Nice contact']);
        // this record references a POTA park, which will trigger the mocked failure below
        $badRecord = $this->adifRecord(['COMMENT' => 'Activated K-1234', 'CALL' => 'K2ABC']);

        $this->mock(QRZLogbookService::class, function (MockInterface $mock) use ($goodRecord, $badRecord) {
            $mock->shouldReceive('getLogbookEntries')->once()->andReturn([$goodRecord, $badRecord]);
        });
        $this->mock(ParksOnTheAirService::class, function (MockInterface $mock) {
            $mock->shouldReceive('filterUncachedReferences')
                ->with(['K-1234'])
                ->andReturn(['K-1234']);
            $mock->shouldReceive('getParkInfo')
                ->with('K-1234')
                ->andThrow(new \RuntimeException('POTA API unavailable'));
        });

        try {
            dispatch_sync(resolve(QRZLogbookImport::class));
            $this->fail('Expected the import to throw when a record fails.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('POTA API unavailable', $e->getMessage());
        }

        // the pre-existing entry must survive, and neither of the two imported records
        // (including the one that didn't fail) should have been persisted
        $this->assertDatabaseCount('logbook_entries', 1);
        $this->assertDatabaseHas('logbook_entries', ['id' => $existing->id]);
    }

    #[Test]
    public function hiddenVisibilityOverridesSurviveLogbookReimport(): void
    {
        $record = $this->adifRecord([
            'APP_QRZLOG_LOGID' => '123456789',
            'CALL' => 'K1BADGRID',
            'COUNTRY' => 'USA',
            'GRIDSQUARE' => 'NN00nm',
        ]);
        $entryKey = resolve(LogbookEntryIdentity::class)->forRecord($record);

        LogbookEntryVisibilityOverride::create([
            'qrz_logid' => '123456789',
            'entry_key' => $entryKey,
            'hidden_from_public' => true,
        ]);

        $this->mock(QRZLogbookService::class, function (MockInterface $mock) use ($record) {
            $mock->shouldReceive('getLogbookEntries')->once()->andReturn([$record]);
        });

        dispatch_sync(resolve(QRZLogbookImport::class));

        $this->assertDatabaseHas('logbook_entries', [
            'qrz_logid' => '123456789',
            'entry_key' => $entryKey,
            'hidden_from_public' => true,
            'to_grid' => 'NN00nm',
        ]);
        $this->assertIsInt(Cache::get('logbook:last_imported_at'));
    }

    #[Test]
    public function qrzLocationFieldsAreStoredOnImportedEntries(): void
    {
        $record = $this->adifRecord([
            'APP_QRZLOG_LOGID' => '987654321',
            'CALL' => 'KL7ABC',
            'QTH' => 'Anchorage',
            'STATE' => 'AK',
            'CNTY' => 'Anchorage',
        ]);

        $this->mock(QRZLogbookService::class, function (MockInterface $mock) use ($record) {
            $mock->shouldReceive('getLogbookEntries')->once()->andReturn([$record]);
        });

        dispatch_sync(resolve(QRZLogbookImport::class));

        $this->assertDatabaseHas('logbook_entries', [
            'qrz_logid' => '987654321',
            'to_city' => 'Anchorage',
            'to_state' => 'AK',
            'to_county' => 'Anchorage',
        ]);
    }

    #[Test]
    public function qrzsUnknownLocationPlaceholderIsStoredAsNullRatherThanNullIsland(): void
    {
        // QRZ fills in "N000 00.000" rather than omitting LAT/LON when a
        // station's location isn't known; that must not become a real (0, 0)
        // marker on the map.
        $record = $this->adifRecord([
            'APP_QRZLOG_LOGID' => '555555555',
            'LAT' => 'N000 00.000',
            'LON' => 'E000 00.000',
        ]);

        $this->mock(QRZLogbookService::class, function (MockInterface $mock) use ($record) {
            $mock->shouldReceive('getLogbookEntries')->once()->andReturn([$record]);
        });

        dispatch_sync(resolve(QRZLogbookImport::class));

        $this->assertDatabaseHas('logbook_entries', [
            'qrz_logid' => '555555555',
            'to_latitude' => null,
            'to_longitude' => null,
            'to_coordinates' => null,
        ]);
    }

    #[Test]
    public function aGenuineEquatorOrPrimeMeridianContactIsNotTreatedAsUnknown(): void
    {
        // Only the *pair* (0, 0) is QRZ's unknown-location placeholder. A real
        // contact can legitimately sit on the equator (lat 0) or the prime
        // meridian (lon 0) while the other axis is a real, non-zero value.
        $record = $this->adifRecord([
            'APP_QRZLOG_LOGID' => '666666666',
            'LAT' => 'N000 00.000',
            'LON' => 'W078 30.000',
        ]);

        $this->mock(QRZLogbookService::class, function (MockInterface $mock) use ($record) {
            $mock->shouldReceive('getLogbookEntries')->once()->andReturn([$record]);
        });

        dispatch_sync(resolve(QRZLogbookImport::class));

        $this->assertDatabaseHas('logbook_entries', [
            'qrz_logid' => '666666666',
            'to_latitude' => '0.00000',
            'to_longitude' => '-78.30000',
            'to_coordinates' => '0.00000,-78.30000',
        ]);
    }

    private function adifRecord(array $overrides = []): array
    {
        return array_merge([
            'CALL' => 'W1AW',
            'STATION_CALLSIGN' => 'N0CALL',
            'COUNTRY' => 'USA',
            'MY_COUNTRY' => 'USA',
            'MY_LAT' => 'N40 00.000',
            'MY_LON' => 'W75 00.000',
            'LAT' => 'N41 00.000',
            'LON' => 'W72 00.000',
            'FREQ' => '14.250',
            'BAND' => '20m',
            'MODE' => 'SSB',
            'RST_SENT' => '59',
            'RST_RCVD' => '59',
            'MY_GRIDSQUARE' => 'FN20',
            'GRIDSQUARE' => 'FN31',
            'DISTANCE' => '100',
            'COMMENT' => '',
            'QSO_DATE' => '20240101',
            'TIME_ON' => '1200',
        ], $overrides);
    }
}

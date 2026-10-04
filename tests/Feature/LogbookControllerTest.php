<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Callsign;
use App\Models\LogbookEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogbookControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('logbook:last_imported_at');
    }

    #[Test]
    public function radioPageReceivesTheConfiguredApproximateQth(): void
    {
        config([
            'radio.origin.latitude' => 12.3456,
            'radio.origin.longitude' => -65.4321,
        ]);

        $response = $this->get('/radio');

        $response->assertOk();
        $response->assertViewHas('mapConfig', [
            'lat' => 12.3456,
            'lng' => -65.4321,
            'zoom' => 2.5,
        ]);
        $response->assertViewHas('qth', [
            'lat' => 12.3456,
            'lng' => -65.4321,
            'label' => 'Approx. QTH',
        ]);
    }

    #[Test]
    public function returnsRecentContactsWithoutExposingTheStationCallsign(): void
    {
        $station = Callsign::create(['name' => 'N0PRIVATE', 'country' => 'United States']);

        $this->createLogbookEntry([
            'station' => $station,
            'callee' => Callsign::create(['name' => 'K1OLD', 'country' => 'United States']),
            'created_at' => Carbon::parse('2026-07-18 10:00:00'),
        ]);
        $this->createLogbookEntry([
            'station' => $station,
            'callee' => Callsign::create(['name' => 'K1NEW', 'country' => 'United States']),
            'created_at' => Carbon::parse('2026-07-19 10:00:00'),
        ]);

        $response = $this->getJson('/api/radio/qsos/band/All/mode/All');

        $response->assertOk();
        $this->assertSame('FeatureCollection', $response->json('type'));
        $this->assertSame([
            'total' => 2,
            'returned' => 2,
            'limit' => 200,
            'sort' => 'newest',
            'last_imported_at' => null,
        ], $response->json('meta'));
        $this->assertSame('K1NEW', $response->json('features.0.properties.to_callsign'));
        $this->assertSame('K1OLD', $response->json('features.1.properties.to_callsign'));
        $this->assertStringNotContainsString('N0PRIVATE', $response->getContent());

        $properties = $response->json('features.0.properties');
        $this->assertArrayNotHasKey('from_callsign', $properties);
        $this->assertArrayNotHasKey('from_grid', $properties);
        $this->assertArrayNotHasKey('from_coordinates', $properties);
        $this->assertArrayNotHasKey('from_latitude', $properties);
        $this->assertArrayNotHasKey('from_longitude', $properties);
        $this->assertArrayNotHasKey('qrz_logid', $properties);
        $this->assertArrayNotHasKey('entry_key', $properties);
        $this->assertArrayNotHasKey('hidden_from_public', $properties);
    }

    #[Test]
    public function searchesContactedStationFieldsButNotTheStationCallsign(): void
    {
        $station = Callsign::create(['name' => 'N0PRIVATE', 'country' => 'United States']);

        $this->createLogbookEntry([
            'station' => $station,
            'callee' => Callsign::create(['name' => 'K5ARK', 'country' => 'United States']),
            'comments' => 'Nice contact from Arkansas',
            'created_at' => Carbon::parse('2026-07-19 10:00:00'),
        ]);
        $this->createLogbookEntry([
            'station' => $station,
            'callee' => Callsign::create(['name' => 'W1OTHER', 'country' => 'United States']),
            'comments' => 'Quick exchange',
            'created_at' => Carbon::parse('2026-07-18 10:00:00'),
        ]);

        $matched = $this->getJson('/api/radio/qsos/band/All/mode/All?search=Arkansas');

        $matched->assertOk();
        $this->assertCount(1, $matched->json('features'));
        $this->assertSame([
            'total' => 1,
            'returned' => 1,
            'limit' => 200,
            'sort' => 'newest',
            'last_imported_at' => null,
        ], $matched->json('meta'));
        $this->assertSame('K5ARK', $matched->json('features.0.properties.to_callsign'));

        $private = $this->getJson('/api/radio/qsos/band/All/mode/All?search=N0PRIVATE');

        $private->assertOk();
        $this->assertCount(0, $private->json('features'));
        $this->assertSame([
            'total' => 0,
            'returned' => 0,
            'limit' => 200,
            'sort' => 'newest',
            'last_imported_at' => null,
        ], $private->json('meta'));
    }

    #[Test]
    public function reportsTotalMatchesWhenTheResponseIsLimited(): void
    {
        $station = Callsign::create(['name' => 'N0PRIVATE', 'country' => 'United States']);

        $this->createLogbookEntry([
            'station' => $station,
            'callee' => Callsign::create(['name' => 'K1ONE', 'country' => 'United States']),
            'created_at' => Carbon::parse('2026-07-19 10:00:00'),
        ]);
        $this->createLogbookEntry([
            'station' => $station,
            'callee' => Callsign::create(['name' => 'K1TWO', 'country' => 'United States']),
            'created_at' => Carbon::parse('2026-07-18 10:00:00'),
        ]);

        $response = $this->getJson('/api/radio/qsos/band/20M/mode/SSB?limit=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('features'));
        $this->assertSame([
            'total' => 2,
            'returned' => 1,
            'limit' => 1,
            'sort' => 'newest',
            'last_imported_at' => null,
        ], $response->json('meta'));
        $this->assertSame('K1ONE', $response->json('features.0.properties.to_callsign'));
    }

    #[Test]
    public function sortsContactsBeforeApplyingTheResponseLimit(): void
    {
        $station = Callsign::create(['name' => 'N0PRIVATE', 'country' => 'United States']);

        $this->createLogbookEntry([
            'station' => $station,
            'callee' => Callsign::create(['name' => 'K1NEAR', 'country' => 'United States']),
            'distance' => 120,
            'created_at' => Carbon::parse('2026-07-19 10:00:00'),
        ]);
        $this->createLogbookEntry([
            'station' => $station,
            'callee' => Callsign::create(['name' => 'K1DX', 'country' => 'Australia']),
            'distance' => 9500,
            'created_at' => Carbon::parse('2026-07-18 10:00:00'),
        ]);
        $this->createLogbookEntry([
            'station' => $station,
            'callee' => Callsign::create(['name' => 'K1MID', 'country' => 'United States']),
            'distance' => 1400,
            'created_at' => Carbon::parse('2026-07-17 10:00:00'),
        ]);

        $dx = $this->getJson('/api/radio/qsos/band/20M/mode/SSB?sort=distance_desc&limit=2');

        $dx->assertOk();
        $this->assertSame([
            'total' => 3,
            'returned' => 2,
            'limit' => 2,
            'sort' => 'distance_desc',
            'last_imported_at' => null,
        ], $dx->json('meta'));
        $this->assertSame('K1DX', $dx->json('features.0.properties.to_callsign'));
        $this->assertSame('K1MID', $dx->json('features.1.properties.to_callsign'));

        $oldest = $this->getJson('/api/radio/qsos/band/20M/mode/SSB?sort=oldest&limit=1');

        $oldest->assertOk();
        $this->assertSame([
            'total' => 3,
            'returned' => 1,
            'limit' => 1,
            'sort' => 'oldest',
            'last_imported_at' => null,
        ], $oldest->json('meta'));
        $this->assertSame('K1MID', $oldest->json('features.0.properties.to_callsign'));
    }

    #[Test]
    public function hiddenContactsAreExcludedFromThePublicLogbook(): void
    {
        $station = Callsign::create(['name' => 'N0PRIVATE', 'country' => 'United States']);

        $this->createLogbookEntry([
            'station' => $station,
            'callee' => Callsign::create(['name' => 'K1PUBLIC', 'country' => 'United States']),
            'created_at' => Carbon::parse('2026-07-19 10:00:00'),
        ]);
        $this->createLogbookEntry([
            'station' => $station,
            'callee' => Callsign::create(['name' => 'K1HIDDEN', 'country' => 'United States']),
            'hidden_from_public' => true,
            'created_at' => Carbon::parse('2026-07-18 10:00:00'),
        ]);

        $response = $this->getJson('/api/radio/qsos/band/20M/mode/SSB');

        $response->assertOk();
        $this->assertSame([
            'total' => 1,
            'returned' => 1,
            'limit' => 200,
            'sort' => 'newest',
            'last_imported_at' => null,
        ], $response->json('meta'));
        $this->assertSame('K1PUBLIC', $response->json('features.0.properties.to_callsign'));
        $this->assertStringNotContainsString('K1HIDDEN', $response->getContent());
    }

    #[Test]
    public function includesTheCachedQrzImportTimestampInMetadata(): void
    {
        Cache::forever('logbook:last_imported_at', Carbon::parse('2026-07-21 15:30:00 UTC')->timestamp);

        $this->createLogbookEntry([
            'created_at' => Carbon::parse('2026-07-19 10:00:00'),
        ]);

        $response = $this->getJson('/api/radio/qsos/band/20M/mode/SSB');

        $response->assertOk();
        $this->assertSame('2026-07-21T15:30:00+00:00', $response->json('meta.last_imported_at'));
    }

    #[Test]
    public function returnsAndSearchesSpecificContactLocationFields(): void
    {
        $this->createLogbookEntry([
            'callee' => Callsign::create(['name' => 'KL7ABC', 'country' => 'United States']),
            'to_city' => 'Anchorage',
            'to_state' => 'AK',
            'to_county' => 'Anchorage',
            'created_at' => Carbon::parse('2026-07-19 10:00:00'),
        ]);
        $this->createLogbookEntry([
            'callee' => Callsign::create(['name' => 'W1OTHER', 'country' => 'United States']),
            'to_city' => 'Orlando',
            'to_state' => 'FL',
            'created_at' => Carbon::parse('2026-07-18 10:00:00'),
        ]);

        $response = $this->getJson('/api/radio/qsos/band/All/mode/All?search=Anchorage');

        $response->assertOk();
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('KL7ABC', $response->json('features.0.properties.to_callsign'));
        $this->assertSame('Anchorage', $response->json('features.0.properties.to_city'));
        $this->assertSame('AK', $response->json('features.0.properties.to_state'));
        $this->assertSame('Anchorage, AK', $response->json('features.0.properties.display_location'));
    }

    private function createLogbookEntry(array $attributes = []): LogbookEntry
    {
        $station = $attributes['station']
            ?? Callsign::create(['name' => 'N0CALL', 'country' => 'United States']);
        $callee = $attributes['callee']
            ?? Callsign::create(['name' => 'W1AW', 'country' => 'United States']);

        unset($attributes['station'], $attributes['callee']);

        return LogbookEntry::create(array_merge([
            'from_callsign' => $station->id,
            'to_callsign' => $callee->id,
            'frequency' => 14.250,
            'band' => '20M',
            'mode' => 'SSB',
            'rst_sent' => '59',
            'rst_received' => '59',
            'from_grid' => 'FN20',
            'from_coordinates' => '40.00000,-75.00000',
            'from_latitude' => '40.00000',
            'from_longitude' => '-75.00000',
            'to_grid' => 'FN31',
            'to_coordinates' => '41.00000,-72.00000',
            'to_latitude' => '41.00000',
            'to_longitude' => '-72.00000',
            'distance' => 100,
            'comments' => 'Nice contact',
            'category' => 'default',
        ], $attributes));
    }
}

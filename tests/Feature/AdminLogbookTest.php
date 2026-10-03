<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\Logbook\QRZLogbookImport;
use App\Livewire\Admin\LogbookIndex;
use App\Models\Callsign;
use App\Models\LogbookEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminLogbookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('logbook:last_imported_at');
    }

    #[Test]
    public function guestsAreRedirectedFromAdminLogbook(): void
    {
        $this->get(route('admin.logbook.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function nonAdminsAreForbiddenFromAdminLogbook(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(route('admin.logbook.index'))
            ->assertForbidden();
    }

    #[Test]
    public function adminCanHideAndShowAQsoFromThePublicLogbook(): void
    {
        $entry = $this->createLogbookEntry([
            'qrz_logid' => '123456789',
            'created_at' => Carbon::parse('2026-07-19 10:00:00'),
        ]);

        Livewire::actingAs(User::factory()->create(['is_admin' => true]))
            ->test(LogbookIndex::class)
            ->call('hide', $entry->id)
            ->assertSet('status', 'QSO hidden from public logbook.');

        $this->assertTrue($entry->fresh()->hidden_from_public);
        $this->assertNotNull($entry->fresh()->entry_key);
        $this->assertDatabaseHas('logbook_entry_visibility_overrides', [
            'qrz_logid' => '123456789',
            'entry_key' => $entry->fresh()->entry_key,
            'hidden_from_public' => true,
        ]);

        $hiddenResponse = $this->getJson('/api/radio/qsos/band/20M/mode/SSB');
        $hiddenResponse->assertOk();
        $this->assertCount(0, $hiddenResponse->json('features'));

        Livewire::actingAs(User::factory()->create(['is_admin' => true]))
            ->test(LogbookIndex::class)
            ->call('show', $entry->id)
            ->assertSet('status', 'QSO restored to public logbook.');

        $this->assertFalse($entry->fresh()->hidden_from_public);
        $this->assertDatabaseMissing('logbook_entry_visibility_overrides', [
            'qrz_logid' => '123456789',
        ]);

        $shownResponse = $this->getJson('/api/radio/qsos/band/20M/mode/SSB');
        $shownResponse->assertOk();
        $this->assertCount(1, $shownResponse->json('features'));
    }

    #[Test]
    public function adminCanViewTheLogbookList(): void
    {
        $this->createLogbookEntry([
            'qrz_logid' => '987654321',
            'created_at' => Carbon::parse('2026-07-19 10:00:00'),
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.logbook.index'))
            ->assertOk()
            ->assertSee('W1AW')
            ->assertSee('987654321')
            ->assertSee('No QRZ import recorded yet')
            ->assertSee('Force QRZ Re-import')
            ->assertSee('Jul 19, 2026, 10:00 AM')
            ->assertSee('Hide');
    }

    #[Test]
    public function adminLogbookExposesTheLastImportTimestampAsUtcForBrowserFormatting(): void
    {
        Cache::forever('logbook:last_imported_at', Carbon::parse('2026-07-21 03:50:42', 'UTC')->timestamp);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.logbook.index'))
            ->assertOk()
            ->assertSee('Last QRZ import:')
            ->assertSee('data-relative-utc-time="2026-07-21T03:50:42+00:00"', false)
            ->assertSee('Imported at Jul 21, 2026, 3:50 AM UTC');
    }

    #[Test]
    public function adminLogbookRendersStatusMessagesInTheSharedNotificationLayout(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->withSession(['status' => 'QRZ logbook re-import queued.'])
            ->get(route('admin.logbook.index'))
            ->assertOk()
            ->assertSee('notification-stack', false)
            ->assertSee('notification-message notification-message-success', false)
            ->assertSee('data-notification-dismiss-after="10000"', false)
            ->assertSee('data-notification-dismiss', false)
            ->assertSee('Dismiss notification', false)
            ->assertSee('QRZ logbook re-import queued.');
    }

    #[Test]
    public function adminCanQueueAFreshQrzLogbookImport(): void
    {
        Bus::fake();
        Cache::put('logbook', [$this->adifRecord(['CALL' => 'K1STALE'])], now()->addDay());

        Livewire::actingAs(User::factory()->create(['is_admin' => true]))
            ->test(LogbookIndex::class)
            ->call('import')
            ->assertSet('status', 'QRZ logbook re-import queued. A queue worker must be running to process it.');

        Bus::assertDispatched(
            QRZLogbookImport::class,
            fn (QRZLogbookImport $job): bool => $job->connection === 'redis',
        );
        $this->assertNull(Cache::get('logbook'));
    }

    #[Test]
    public function logbookListCanBeFilteredBySearchAndVisibility(): void
    {
        $visible = $this->createLogbookEntry([
            'qrz_logid' => '111111111',
            'created_at' => Carbon::parse('2026-07-19 10:00:00'),
        ]);
        $hidden = $this->createLogbookEntry([
            'qrz_logid' => '222222222',
            'hidden_from_public' => true,
            'created_at' => Carbon::parse('2026-07-19 11:00:00'),
        ]);

        Livewire::actingAs(User::factory()->create(['is_admin' => true]))
            ->test(LogbookIndex::class)
            ->assertSee('111111111')
            ->assertSee('222222222')
            ->set('visibility', 'hidden')
            ->assertDontSee('111111111')
            ->assertSee('222222222')
            ->set('visibility', 'all')
            ->set('search', '111111111')
            ->assertSee('111111111')
            ->assertDontSee('222222222');
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

    private function adifRecord(array $overrides = []): array
    {
        return array_merge([
            'APP_QRZLOG_LOGID' => '123456',
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

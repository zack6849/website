<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\POTAPark;
use App\Models\POTAParkType;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class ParksOnTheAirService extends ServiceProvider
{
    private string $baseUrl = 'https://api.pota.app';

    /**
     * POTA moved away from ARRL/DXCC-style callsign prefixes to its own
     * per-entity reference prefixes some time ago. Loggers (and QRZ's stored
     * comments) that predate the change still emit the old prefix, which the
     * API will never resolve - e.g. "K-3061" 200s with a null body, while the
     * same park under "US-3061" resolves. Translate the known ones instead of
     * hammering the API with references that can never succeed.
     */
    private const array LEGACY_PREFIX_ALIASES = [
        'K' => 'US',
        'VE' => 'CA',
        'HI' => 'DO',
    ];

    /**
     * A handful of entities that used to share the generic "K" prefix were
     * split out into their own POTA entity and renumbered from scratch, not
     * just re-prefixed - so old-number == new-number does NOT hold for these,
     * unlike the rest of "K" (which cleanly becomes "US" with the same
     * number). Checked before LEGACY_PREFIX_ALIASES so these take priority.
     * Source: https://docs.pota.app/docs/changes/2024-03-20-united-states.html
     */
    private const array LEGACY_REFERENCE_ALIASES = [
        // American Samoa (AS)
        'K-0053' => 'AS-0001',
        'K-0130' => 'AS-0002',
        'K-9703' => 'AS-0003',
        'K-9754' => 'AS-0004',
        // Guam (GU)
        'K-0110' => 'GU-0001',
        'K-0762' => 'GU-0002',
        'K-10394' => 'GU-0003',
        'K-10395' => 'GU-0004',
        'K-10459' => 'GU-0005',
        'K-10460' => 'GU-0006',
        'K-10461' => 'GU-0007',
        // Northern Mariana Islands (MP)
        'K-7869' => 'MP-0001',
        'K-9705' => 'MP-0002',
        'K-9706' => 'MP-0003',
        'K-9707' => 'MP-0004',
        'K-9708' => 'MP-0005',
        'K-9709' => 'MP-0006',
        'K-9710' => 'MP-0007',
        'K-9711' => 'MP-0008',
        'K-9712' => 'MP-0009',
        'K-9713' => 'MP-0010',
        'K-9714' => 'MP-0011',
        'K-9715' => 'MP-0012',
        'K-9716' => 'MP-0013',
        'K-9717' => 'MP-0014',
        // US Minor Outlying Islands (UM)
        'K-0111' => 'UM-0001',
        'K-0112' => 'UM-0002',
        'K-0113' => 'UM-0003',
        'K-0114' => 'UM-0004',
        'K-0115' => 'UM-0005',
        'K-0116' => 'UM-0006',
        'K-0122' => 'UM-0007',
        'K-0131' => 'UM-0008',
        'K-0394' => 'UM-0009',
        // Puerto Rico (PR)
        'K-0103' => 'PR-0001',
        'K-0104' => 'PR-0002',
        'K-0106' => 'PR-0003',
        'K-0108' => 'PR-0004',
        'K-0132' => 'PR-0005',
        'K-0134' => 'PR-0006',
        'K-0135' => 'PR-0007',
        'K-0317' => 'PR-0008',
        'K-0323' => 'PR-0009',
        'K-0351' => 'PR-0010',
        'K-0363' => 'PR-0011',
        'K-0444' => 'PR-0012',
        'K-0484' => 'PR-0013',
        'K-0860' => 'PR-0014',
        'K-4668' => 'PR-0015',
        'K-4669' => 'PR-0016',
        'K-4670' => 'PR-0017',
        'K-4671' => 'PR-0018',
        'K-4672' => 'PR-0019',
        'K-4673' => 'PR-0020',
        'K-4674' => 'PR-0021',
        'K-4675' => 'PR-0022',
        'K-4676' => 'PR-0023',
        'K-4677' => 'PR-0024',
        'K-4678' => 'PR-0025',
        'K-4679' => 'PR-0026',
        'K-4680' => 'PR-0027',
        'K-4681' => 'PR-0028',
        'K-4682' => 'PR-0029',
        'K-4683' => 'PR-0030',
        'K-4684' => 'PR-0031',
        'K-4685' => 'PR-0032',
        'K-4686' => 'PR-0033',
        'K-7549' => 'PR-0034',
        'K-7550' => 'PR-0035',
        'K-7551' => 'PR-0036',
        'K-7552' => 'PR-0037',
        'K-7553' => 'PR-0038',
        'K-7554' => 'PR-0039',
        'K-7555' => 'PR-0040',
        'K-7556' => 'PR-0041',
        'K-7557' => 'PR-0042',
        'K-7558' => 'PR-0043',
        'K-7559' => 'PR-0044',
        'K-7560' => 'PR-0045',
        'K-7561' => 'PR-0046',
        'K-7562' => 'PR-0047',
        'K-9685' => 'PR-0048',
        'K-9686' => 'PR-0049',
        'K-9687' => 'PR-0050',
        'K-9688' => 'PR-0051',
        'K-9689' => 'PR-0052',
        'K-9690' => 'PR-0053',
        'K-9691' => 'PR-0054',
        'K-9692' => 'PR-0055',
        'K-9693' => 'PR-0056',
        'K-9694' => 'PR-0057',
        'K-9695' => 'PR-0058',
        'K-9722' => 'PR-0059',
        'K-9723' => 'PR-0060',
        'K-9724' => 'PR-0061',
        'K-9725' => 'PR-0062',
        'K-9726' => 'PR-0063',
        'K-9727' => 'PR-0064',
        'K-9728' => 'PR-0065',
        'K-9729' => 'PR-0066',
        'K-9730' => 'PR-0067',
        'K-9731' => 'PR-0068',
        'K-9732' => 'PR-0069',
        'K-9733' => 'PR-0070',
        'K-9734' => 'PR-0071',
        'K-9735' => 'PR-0072',
        'K-9736' => 'PR-0073',
        'K-9737' => 'PR-0074',
        'K-9738' => 'PR-0075',
        'K-9746' => 'PR-0076',
        'K-9747' => 'PR-0077',
        'K-9748' => 'PR-0078',
        'K-9749' => 'PR-0079',
        'K-9750' => 'PR-0080',
        'K-9751' => 'PR-0081',
        'K-10313' => 'PR-0082',
        'K-10314' => 'PR-0083',
        'K-10315' => 'PR-0084',
        'K-10316' => 'PR-0085',
        'K-10317' => 'PR-0086',
        'K-10384' => 'PR-0087',
        'K-10385' => 'PR-0088',
        'K-10393' => 'PR-0089',
        'K-0133' => 'PR-0090',
        // US Virgin Islands (VI)
        'K-0066' => 'VI-0001',
        'K-0616' => 'VI-0002',
        'K-0617' => 'VI-0003',
        'K-0618' => 'VI-0004',
        'K-0755' => 'VI-0005',
        'K-0807' => 'VI-0006',
        'K-0906' => 'VI-0007',
        'K-0968' => 'VI-0008',
    ];

    // Basic courtesy spacing between live lookups against api.pota.app.
    private const int REQUEST_DELAY_MICROSECONDS = 150_000;

    public function __construct(
        private readonly PotaUnresolvedReferenceCache $unresolvedCache = new PotaUnresolvedReferenceCache(),
    ) {
    }

    private function buildRequest(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout(10)
            ->connectTimeout(3)
            ->withHeaders([
                'Referer' => 'https://pota.app',
                'User-Agent' => 'Logbook Map (zcraig.me/qsos)'
            ]);
    }

    /**
     * @param string[] $references
     * @return string[] the subset of $references not already cached in pota_parks, in one query
     */
    public function filterUncachedReferences(array $references): array
    {
        if (empty($references)) {
            return [];
        }
        $cached = POTAPark::whereIn('reference', $references)->pluck('reference')->all();
        return array_values(array_diff($references, $cached));
    }

    public function getParkInfo(string $parkReference): POTAPark|false
    {
        $canonicalReference = $this->canonicalReference($parkReference);

        $cached = $this->findCached($parkReference, $canonicalReference);
        if ($cached !== null) {
            return $cached;
        }

        if ($this->unresolvedCache->has($parkReference)) {
            return false;
        }

        // A known legacy prefix will never resolve in its raw form, so go
        // straight to the canonical reference instead of wasting a request
        // we already know will fail.
        [$park, $confirmedNotFound] = $this->fetchAndStore($canonicalReference);

        // Only remember a *confirmed* "this reference doesn't exist" (POTA
        // 200s with a null body for those) - not a transient failure like a
        // 5xx or an outage, which would otherwise get suppressed for 30 days
        // even though it might resolve fine on tomorrow's import.
        if ($park === false && $confirmedNotFound) {
            $this->unresolvedCache->remember($parkReference);
        }

        return $park;
    }

    private function findCached(string $parkReference, string $canonicalReference): ?POTAPark
    {
        $park = POTAPark::where('reference', $parkReference)->first();
        if ($park !== null) {
            return $park;
        }

        if ($canonicalReference === $parkReference) {
            return null;
        }

        return POTAPark::where('reference', $canonicalReference)->first();
    }

    private function canonicalReference(string $parkReference): string
    {
        if (isset(self::LEGACY_REFERENCE_ALIASES[$parkReference])) {
            return self::LEGACY_REFERENCE_ALIASES[$parkReference];
        }

        [$prefix, $number] = array_pad(explode('-', $parkReference, 2), 2, null);

        if ($number === null || !isset(self::LEGACY_PREFIX_ALIASES[$prefix])) {
            return $parkReference;
        }

        return self::LEGACY_PREFIX_ALIASES[$prefix] . '-' . $number;
    }

    /**
     * @return array{0: POTAPark|false, 1: bool} the park (or false), and
     *   whether a false result is a *confirmed* not-found vs. a request
     *   failure (non-2xx, timeout, etc.) that shouldn't be negative-cached.
     */
    private function fetchAndStore(string $parkReference): array
    {
        usleep(self::REQUEST_DELAY_MICROSECONDS);

        $apiRequest = $this->buildRequest()->get("/park/$parkReference");

        if (!$apiRequest->successful()) {
            \Log::warning("Failed to get park info from POTA API for reference {$parkReference}: " . $apiRequest->body());
            return [false, false];
        }

        if ($apiRequest->json() == null) {
            // POTA responds 200 with a null body for a reference that
            // genuinely doesn't exist - this is the one case worth remembering.
            return [false, true];
        }

        $apiData = $apiRequest->json();
        $parkType = POTAParkType::firstOrCreate(['name' => $apiData['parktypeDesc']], [
            'id' => $apiData['parktypeId'],
        ]);
        $park = new POTAPark();
        $park->reference = $apiData['reference'];
        $park->name = $apiData['name'];
        $park->latitude = $apiData['latitude'];
        $park->longitude = $apiData['longitude'];
        $park->grid4 = $apiData['grid4'];
        $park->grid6 = $apiData['grid6'];
        $park->park_type_id = $parkType->id;
        $park->active = $apiData['active'];
        $park->comments = $apiData['parkComments'];
        $park->location = $apiData['locationName'];
        $park->first_activation_at = $apiData['firstActivationDate'];
        $park->raw_data = json_encode($apiData, JSON_PRETTY_PRINT);
        $park->save();
        return [$park, false];
    }
}

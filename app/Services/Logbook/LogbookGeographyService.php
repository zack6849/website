<?php

declare(strict_types=1);

namespace App\Services\Logbook;

class LogbookGeographyService
{
    private const float EARTH_RADIUS_MILES = 3958.8;
    private const array CARDINAL_DIRECTIONS = [
        'N',
        'NNE',
        'NE',
        'ENE',
        'E',
        'ESE',
        'SE',
        'SSE',
        'S',
        'SSW',
        'SW',
        'WSW',
        'W',
        'WNW',
        'NW',
        'NNW',
    ];

    public function bearing(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
    ): ?int {
        if ($fromLatitude === $toLatitude && $fromLongitude === $toLongitude) {
            return null;
        }

        $fromLatitudeRadians = deg2rad($fromLatitude);
        $toLatitudeRadians = deg2rad($toLatitude);
        $longitudeDeltaRadians = deg2rad($toLongitude - $fromLongitude);

        $y = sin($longitudeDeltaRadians) * cos($toLatitudeRadians);
        $x = cos($fromLatitudeRadians) * sin($toLatitudeRadians)
            - sin($fromLatitudeRadians) * cos($toLatitudeRadians) * cos($longitudeDeltaRadians);

        $bearing = (int) round(fmod(rad2deg(atan2($y, $x)) + 360, 360));

        return $bearing === 360 ? 0 : $bearing;
    }

    public function cardinalDirection(int $bearing): string
    {
        $index = (int) floor((($bearing % 360) + 11.25) / 22.5) % count(self::CARDINAL_DIRECTIONS);

        return self::CARDINAL_DIRECTIONS[$index];
    }

    public function haversineMiles(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
    ): float {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($longitudeDelta / 2) ** 2;

        return self::EARTH_RADIUS_MILES * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

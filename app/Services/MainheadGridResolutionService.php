<?php

declare(strict_types=1);

namespace App\Services;

use Closure;

/**
 * Class MainheadGridResolutionService
 * @package App\Services
 * Used to convert HAM Mainhead grid locators (eg: FN42) to lat/lon coordinates
 * Inspired by / ported from https://github.com/gravypod/maidenhead/blob/master/maidenhead.php
 */
class MainheadGridResolutionService
{
    private const int MAX_LAT = 90;
    private const int MIN_LAT = -90;
    private const int MAX_LON = 180;
    private const int MIN_LON = -180;

    private const int LAT_DISTANCE = self::MAX_LAT - self::MIN_LAT;
    private const int LON_DISTANCE = self::MAX_LON - self::MIN_LON;

    private function subdivisor(): Closure
    {
        $last = array(18, 10, 24, 10);
        $i = 0;
        return function () use (&$i, &$last) {
            if ($i >= count($last)) {
                $i = 2;
            }
            return $last[$i++];
        };
    }

    private function parseDigit($digit): float|bool|int
    {
        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $value = strpos($alphabet, $digit);
        if (!$value) {
            $value = floatval($digit);
        }
        return $value;
    }

    public function getGridSquare(string $squareId): array
    {
        if ($squareId === '' || strlen($squareId) % 2 != 0) {
            return array(null, null, null, null);
        }

        $grid_square_id = strtoupper($squareId);

        $lat = floatval(self::MIN_LAT);
        $lon = floatval(self::MIN_LON);
        $lat_div = floatval(self::LAT_DISTANCE);
        $lon_div = floatval(self::LON_DISTANCE);

        $base_calculator = $this->subdivisor();

        for ($base = $base_calculator(); strlen($grid_square_id) > 0; $base = $base_calculator()) {

            $lat_id = $grid_square_id[1];
            $lon_id = $grid_square_id[0];

            $lat_div /= $base;
            $lon_div /= $base;

            $lat += $this->parseDigit($lat_id) * $lat_div;
            $lon += $this->parseDigit($lon_id) * $lon_div;

            $grid_square_id = substr($grid_square_id, 2);

        }
        return array($lat, $lon, $lat_div, $lon_div);
    }
}

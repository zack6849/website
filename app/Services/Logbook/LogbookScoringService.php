<?php

declare(strict_types=1);

namespace App\Services\Logbook;

use Illuminate\Support\Carbon;

class LogbookScoringService
{
    private const int RECENCY_DECAY_DAYS = 365;
    private const float MIN_RECENCY_SCORE = 0.18;

    public function recencyScore(?int $ageDays): float
    {
        if ($ageDays === null) {
            return self::MIN_RECENCY_SCORE;
        }

        $score = exp(-$ageDays / self::RECENCY_DECAY_DAYS);

        return round(max(self::MIN_RECENCY_SCORE, $score), 3);
    }

    public function ageDays(?Carbon $qsoTimestamp): ?int
    {
        if ($qsoTimestamp === null) {
            return null;
        }

        $secondsOld = Carbon::now()->getTimestamp() - $qsoTimestamp->getTimestamp();

        return max(0, (int) floor($secondsOld / 86400));
    }
}

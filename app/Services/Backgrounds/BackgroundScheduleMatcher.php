<?php

declare(strict_types=1);

namespace App\Services\Backgrounds;

use App\Enums\BackgroundScheduleRuleType;
use Illuminate\Support\Carbon;

class BackgroundScheduleMatcher
{
    public function matches(?array $rules, Carbon $date): bool
    {
        if (! is_array($rules) || $rules === []) {
            return false;
        }

        return array_any($rules, fn($rule) => is_array($rule) && $this->ruleMatches($rule, $date));

    }

    private function ruleMatches(array $rule, Carbon $date): bool
    {
        $type = is_string($rule['type'] ?? null) ? BackgroundScheduleRuleType::tryFrom($rule['type']) : null;

        return match ($type) {
            BackgroundScheduleRuleType::Date => (int) ($rule['month'] ?? 0) === $date->month
                && (int) ($rule['day'] ?? 0) === $date->day,
            BackgroundScheduleRuleType::Range => $this->rangeMatches($rule, $date),
            BackgroundScheduleRuleType::Weekday => in_array($date->dayOfWeek, array_map('intval', (array) ($rule['days'] ?? [])), true),
            null => false,
        };
    }

    private function rangeMatches(array $rule, Carbon $date): bool
    {
        $start = $rule['start'] ?? null;
        $end = $rule['end'] ?? null;

        if (! is_string($start) || ! is_string($end)) {
            return false;
        }

        try {
            $startDate = Carbon::parse($start)->startOfDay();
            $endDate = Carbon::parse($end)->endOfDay();
        } catch (\Throwable) {
            return false;
        }

        return $date->betweenIncluded($startDate, $endDate);
    }
}

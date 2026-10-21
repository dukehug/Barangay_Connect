<?php

namespace App;

use Carbon\CarbonImmutable;

class ConfirmationDeadline
{
    public function calculate(CarbonImmutable $start, int $hours): CarbonImmutable
    {
        $cursor = $start->setTimezone('Asia/Manila');
        $seconds = $hours * 3600;
        while ($seconds > 0) {
            if ($cursor->isWeekend()) {
                $cursor = $cursor->addDay()->startOfDay();

                continue;
            }
            $midnight = $cursor->addDay()->startOfDay();
            $step = min($seconds, (int) $cursor->diffInSeconds($midnight));
            $cursor = $cursor->addSeconds($step);
            $seconds -= $step;
        }

        return $cursor->setTimezone(config('app.timezone'));
    }
}

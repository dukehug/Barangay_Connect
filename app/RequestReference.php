<?php

namespace App;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RequestReference
{
    public function generate(?string $createdAt = null): string
    {
        $month = CarbonImmutable::parse($createdAt)->setTimezone('Asia/Manila')->format('Y-m');
        $prefix = 'DOC-'.$month.'-';
        $used = DB::table('request_references')->where('reference', 'like', $prefix.'%')
            ->pluck('reference')->map(fn (string $reference): int => (int) substr($reference, -4))->all();
        $available = array_values(array_diff(range(0, 9999), $used));
        shuffle($available);

        foreach ($available as $number) {
            $reference = $prefix.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
            if (DB::table('request_references')->insertOrIgnore(['reference' => $reference])) {
                return $reference;
            }
        }

        throw new RuntimeException('All four-digit request references for '.$month.' have been used.');
    }
}

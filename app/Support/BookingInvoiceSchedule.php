<?php

namespace App\Support;

use App\Models\Property;
use Carbon\CarbonImmutable;

class BookingInvoiceSchedule
{
    public const DTCM_KEYS = [
        'Studio' => 'dtcm_fee_studio',
        '1 BHK' => 'dtcm_fee_1_bhk',
        '2 BHK' => 'dtcm_fee_2_bhk',
        '3 BHK' => 'dtcm_fee_3_bhk',
        '4 BHK' => 'dtcm_fee_4_bhk',
        '5 BHK' => 'dtcm_fee_5_bhk',
        '6 BHK' => 'dtcm_fee_6_bhk',
        'Penthouse' => 'dtcm_fee_penthouse',
        'Villa' => 'dtcm_fee_villa',
    ];

    public static function dtcmRate(Property $property): ?float
    {
        $key = self::DTCM_KEYS[$property->category] ?? null;
        $value = $key ? AppSettings::get($key) : null;

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    public static function periods(string $checkIn, string $checkOut): array
    {
        $start = CarbonImmutable::parse($checkIn)->startOfDay();
        $end = CarbonImmutable::parse($checkOut)->startOfDay();
        $nights = (int) $start->diffInDays($end);
        if ($nights < 1 || $nights > 1095) return [];

        $periods = [];
        for ($offset = 0; $offset < $nights; $offset += 30) {
            $length = min(30, $nights - $offset);
            $periods[] = [
                'from' => $start->addDays($offset)->toDateString(),
                'to' => $start->addDays($offset + $length)->toDateString(),
                'nights' => $length,
                'due_date' => $start->addDays($offset)->toDateString(),
                'renewal' => $offset > 0 && $offset % 90 === 0,
            ];
        }

        return $periods;
    }
}

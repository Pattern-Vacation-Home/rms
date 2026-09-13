<?php

namespace Tests\Unit;

use App\Support\BookingInvoiceSchedule;
use PHPUnit\Framework\TestCase;

class BookingInvoiceScheduleTest extends TestCase
{
    public function test_short_stay_has_one_invoice(): void
    {
        $periods = BookingInvoiceSchedule::periods('2026-09-01', '2026-09-21');
        $this->assertCount(1, $periods);
        $this->assertSame(20, $periods[0]['nights']);
        $this->assertFalse($periods[0]['renewal']);
    }

    public function test_long_stay_has_thirty_night_periods_and_ninety_day_renewals(): void
    {
        $periods = BookingInvoiceSchedule::periods('2026-09-01', '2027-02-01');
        $this->assertCount(6, $periods);
        $this->assertSame([30, 30, 30, 30, 30, 3], array_column($periods, 'nights'));
        $this->assertSame([false, false, false, true, false, false], array_column($periods, 'renewal'));
        $this->assertSame('2026-09-01', $periods[0]['due_date']);
        $this->assertSame($periods[0]['to'], $periods[1]['from']);
    }
}

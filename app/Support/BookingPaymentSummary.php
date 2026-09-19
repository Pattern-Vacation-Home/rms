<?php

namespace App\Support;

use App\Models\Booking;

class BookingPaymentSummary
{
    public static function for(Booking $booking): array
    {
        $invoices = $booking->invoices;
        if ($invoices->isEmpty()) {
            return ['status' => $booking->invoice_status ?: 'unpaid', 'total' => (float) $booking->total_amount,
                'paid' => $booking->invoice_status === 'paid' ? (float) $booking->total_amount : 0.0,
                'balance' => $booking->invoice_status === 'paid' ? 0.0 : (float) $booking->total_amount,
                'invoices' => collect()];
        }

        $rows = $invoices->map(function ($invoice) {
            $total = round((float) $invoice->total_amount, 2);
            $paid = round((float) $invoice->paid_amount, 2);
            $balance = max(0, round($total - $paid, 2));
            return ['invoice' => $invoice, 'total' => $total, 'paid' => $paid, 'balance' => $balance,
                'status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid')];
        })->sortBy(fn ($row) => ($row['invoice']->period_from ?? $row['invoice']->issue_date)?->format('Y-m-d') ?? '')->values();
        $total = round($rows->sum('total'), 2);
        $paid = round($rows->sum('paid'), 2);
        $balance = max(0, round($total - $paid, 2));
        return ['status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
            'total' => $total, 'paid' => $paid, 'balance' => $balance, 'invoices' => $rows];
    }

    public static function sync(Booking $booking): void
    {
        $booking->load(['invoices' => fn ($query) => $query->withSum('payments', 'amount')->withCount('allPayments')]);
        $status = self::for($booking)['status'];
        if ($booking->invoice_status !== $status) $booking->update(['invoice_status' => $status]);
    }
}

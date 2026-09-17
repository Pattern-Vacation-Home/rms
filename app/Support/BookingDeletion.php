<?php

namespace App\Support;

use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\LandlordAccountEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class BookingDeletion
{
    public function __construct(private readonly TtlockClient $locks) {}

    public function delete(Booking $booking, string $reason): void
    {
        $id = $booking->id;
        if (DB::table('booking_deposit_entries')->where('booking_id', $id)->whereNotNull('related_booking_id')->exists()
            || DB::table('booking_deposit_entries')->where('related_booking_id', $id)->exists()
            || (Schema::hasColumn('booking_deposit_refunds', 'related_booking_id')
                && DB::table('booking_deposit_refunds')->where('related_booking_id', $id)->exists())) {
            throw ValidationException::withMessages(['deletion' => 'This booking has a deposit carry-forward linked to another booking. Reconcile both deposit wallets before deleting either booking.']);
        }
        $taskIds = DB::table('booking_tasks')->where('booking_id', $id)->pluck('id');
        if (DB::table('expenses')->where(function ($query) use ($id, $taskIds) {
            $query->where('booking_id', $id)->orWhereIn('booking_task_id', $taskIds);
        })->whereNotNull('owner_charge_invoice_id')->exists()) {
            throw ValidationException::withMessages(['deletion' => 'A booking expense is linked to an owner charge invoice. Remove or settle that independent owner invoice first.']);
        }

        // A live physical door code must not survive deletion of its booking.
        if (Schema::hasTable('booking_lock_accesses')) {
            foreach ($booking->lockAccesses()->whereNull('revoked_at')->with('smartlock')->get() as $access) {
                try {
                    $this->locks->deletePasscode((int) $access->smartlock->remote_id, (int) $access->remote_passcode_id);
                    $access->update(['revoked_at' => now()]);
                } catch (Throwable $exception) {
                    report($exception);
                    throw ValidationException::withMessages(['deletion' => 'TTLock could not confirm that the guest code was revoked. Delete the code in TTLock first, then retry booking deletion.']);
                }
            }
        }

        DB::transaction(function () use ($id) {
            $booking = Booking::whereKey($id)->lockForUpdate()->firstOrFail();
            $invoices = DB::table('booking_invoices')->where('booking_id', $id)->get(['id', 'invoice_number']);
            $invoiceIds = $invoices->pluck('id');
            $payments = DB::table('booking_invoice_payments')->whereIn('booking_invoice_id', $invoiceIds)->get(['id', 'accounting_entry_id', 'bank_account_id']);
            $paymentIds = $payments->pluck('id');
            $batches = Schema::hasTable('booking_payment_batches')
                ? DB::table('booking_payment_batches')->where('booking_id', $id)->get(['id', 'accounting_entry_id']) : collect();
            $taskIds = DB::table('booking_tasks')->where('booking_id', $id)->pluck('id');
            $inspectionIds = DB::table('booking_inspections')->where('booking_id', $id)->orWhereIn('booking_task_id', $taskIds)->pluck('id');
            $expenses = DB::table('expenses')->where('booking_id', $id)->orWhereIn('booking_task_id', $taskIds)
                ->get(['id', 'expense_no', 'owner_charge_invoice_id', 'paid_from_account_id', 'landlord_id']);
            if ($expenses->contains(fn ($expense) => $expense->owner_charge_invoice_id !== null)) {
                throw ValidationException::withMessages(['deletion' => 'A booking expense is linked to an owner charge invoice. Remove or settle that independent owner invoice first.']);
            }
            $expenseIds = $expenses->pluck('id');
            $bills = DB::table('utility_bills')->where('booking_id', $id)->orWhereIn('expense_id', $expenseIds)
                ->get(['id', 'landlord_id']);
            $billIds = $bills->pluck('id');
            $depositRows = DB::table('booking_deposit_entries')->where('booking_id', $id)->get(['accounting_entry_id', 'bank_account_id']);
            $linkedEntryIds = $payments->pluck('accounting_entry_id')->merge($batches->pluck('accounting_entry_id'))
                ->merge($depositRows->pluck('accounting_entry_id'))->filter()->unique();
            $accountingRows = DB::table('accounting_entries')->where('booking_id', $id)
                ->orWhereIn('expense_id', $expenseIds)->orWhereIn('utility_bill_id', $billIds)->orWhereIn('id', $linkedEntryIds)
                ->get(['id', 'paid_from_account_id', 'landlord_id']);
            $accountingIds = $accountingRows->pluck('id');
            $references = collect([$booking->booking_reference])->merge($invoices->pluck('invoice_number'))
                ->merge($paymentIds->map(fn ($paymentId) => 'PAY-'.$paymentId))
                ->merge($paymentIds->map(fn ($paymentId) => 'REV-'.$paymentId))
                ->merge($expenses->pluck('expense_no'))->filter()->unique();
            $ownerEntryQuery = DB::table('landlord_account_entries')->whereIn('booking_invoice_id', $invoiceIds)
                ->orWhereIn('reference', $references);
            foreach ($expenses->pluck('expense_no') as $expenseNumber) {
                $ownerEntryQuery->orWhere('reference', 'REV-'.$expenseNumber)
                    ->orWhere('reference', 'like', 'DRAFT-REV-'.$expenseNumber.'-%');
            }
            $ownerEntries = $ownerEntryQuery->get(['id', 'landlord_id']);
            $ownerIds = $ownerEntries->pluck('landlord_id')->merge($accountingRows->pluck('landlord_id'))
                ->merge($expenses->pluck('landlord_id'))->filter()->unique();
            $bankIds = $accountingRows->pluck('paid_from_account_id')->merge($payments->pluck('bank_account_id'))
                ->merge($depositRows->pluck('bank_account_id'))->merge($expenses->pluck('paid_from_account_id'))
                ->filter()->unique();

            DB::table('landlord_account_entries')->whereIn('id', $ownerEntries->pluck('id'))->delete();
            if (Schema::hasTable('expense_audits')) DB::table('expense_audits')->whereIn('expense_id', $expenseIds)->delete();
            DB::table('accounting_entries')->whereIn('id', $accountingIds)->delete();
            if (Schema::hasTable('inspection_upload_tokens')) DB::table('inspection_upload_tokens')->whereIn('inspection_id', $inspectionIds)->delete();
            if (Schema::hasTable('unit_inventory_movements')) {
                // Stock has physically changed. Keep its movement history, but detach the deleted inspection.
                DB::table('unit_inventory_movements')->whereIn('inspection_id', $inspectionIds)->update(['inspection_id' => null]);
            }
            if (Schema::hasTable('unit_inventory_reviews')) DB::table('unit_inventory_reviews')->whereIn('inspection_id', $inspectionIds)->delete();
            DB::table('booking_inspection_items')->whereIn('booking_inspection_id', $inspectionIds)->delete();
            DB::table('booking_deposit_entries')->where('booking_id', $id)->delete();
            DB::table('booking_deposit_refunds')->where('booking_id', $id)->delete();
            DB::table('booking_inspections')->whereIn('id', $inspectionIds)->delete();
            DB::table('booking_task_remarks')->whereIn('booking_task_id', $taskIds)->delete();
            DB::table('booking_task_activities')->whereIn('booking_task_id', $taskIds)->delete();
            DB::table('booking_task_cost_items')->whereIn('booking_task_id', $taskIds)->delete();
            DB::table('utility_bills')->whereIn('id', $billIds)->delete();
            DB::table('expenses')->whereIn('id', $expenseIds)->delete();
            DB::table('booking_tasks')->whereIn('id', $taskIds)->delete();
            if (Schema::hasTable('booking_lock_accesses')) DB::table('booking_lock_accesses')->where('booking_id', $id)->delete();
            DB::table('booking_invoice_payments')->whereIn('id', $paymentIds)->delete();
            if (Schema::hasTable('booking_payment_batches')) DB::table('booking_payment_batches')->where('booking_id', $id)->delete();
            DB::table('booking_invoices')->whereIn('id', $invoiceIds)->delete();
            DB::table('booking_histories')->where('booking_id', $id)->delete();
            DB::table('bookings')->where('renewed_from_booking_id', $id)->update(['renewed_from_booking_id' => null]);
            DB::table('bookings')->where('id', $id)->delete();

            foreach ($ownerIds as $ownerId) LandlordAccountEntry::recalculateBalancesFor($ownerId);
            foreach ($bankIds as $bankId) {
                $account = BankAccount::whereKey($bankId)->lockForUpdate()->first();
                if (! $account) continue;
                $movement = (float) DB::table('accounting_entries')->where('paid_from_account_id', $bankId)
                    ->whereIn('approval_status', ['posted', 'approved', 'paid'])
                    ->selectRaw('COALESCE(SUM(credit-debit),0) AS total')->value('total');
                $account->update(['current_balance' => round((float) $account->opening_balance + $movement, 2)]);
            }
            if (! Booking::where('property_id', $booking->property_id)->whereIn('status', ['confirmed', 'checked_in'])->exists()) {
                DB::table('properties')->where('id', $booking->property_id)->whereIn('status', ['booked', 'rented'])->update(['status' => 'vacant']);
            }
        });

        Log::warning('Booking and associated records permanently deleted', [
            'booking_id' => $id, 'booking_reference' => $booking->booking_reference,
            'deleted_by' => auth()->id(), 'reason' => $reason,
        ]);
    }
}

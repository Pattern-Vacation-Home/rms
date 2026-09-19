<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\BankStatementTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $bank): BankAccount
    {
        return BankAccount::create(['name' => $bank.' Operating', 'bank_name' => $bank, 'type' => 'bank',
            'currency' => 'AED', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true]);
    }

    public function test_wio_csv_can_be_confirmed_only_against_matching_recorded_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = $this->account('Wio');
        $entry = AccountingEntry::create(['entry_no' => 'JE-TEST-1', 'entry_date' => '2026-09-15', 'type' => 'income',
            'category' => 'rent', 'description' => 'Rent', 'paid_from_account_id' => $account->id,
            'transaction_reference' => 'WIO-REF-1', 'debit' => 0, 'credit' => 250,
            'approval_status' => 'posted', 'status' => 'posted']);
        $csv = "Date,Reference,Description,Debit,Credit\n15/09/2026,WIO-REF-1,Rent,,250.00\n16/09/2026,UNKNOWN,Other,100.00,\n";
        $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.upload'), [
            'bank' => 'wio', 'bank_account_id' => $account->id,
            'statement' => UploadedFile::fake()->createWithContent('wio.csv', $csv),
        ])->assertRedirect();
        $this->assertDatabaseCount('bank_statement_transactions', 2);
        $this->actingAs($admin)->get(route('admin.accounting.bank-reconciliation'))->assertOk()->assertSee('wio.csv');
        $row = BankStatementTransaction::where('reference', 'WIO-REF-1')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.accounting.bank-reconciliation.show', $row->import))->assertOk()->assertSee('Confirm match');
        $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.confirm', $row), ['accounting_entry_id' => $entry->id])->assertRedirect();
        $this->assertSame('confirmed', $row->fresh()->status);
        $this->assertSame($entry->id, $row->fresh()->accounting_entry_id);
        $this->assertDatabaseCount('accounting_entries', 1);
        $unmatched = BankStatementTransaction::where('reference', 'UNKNOWN')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.confirm', $unmatched), ['accounting_entry_id' => $entry->id])->assertSessionHasErrors('accounting_entry_id');
    }

    public function test_adcb_signed_amount_and_duplicate_import(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = $this->account('ADCB');
        $csv = "Transaction Date;Transaction Reference;Narration;Amount;Type\n2026-09-17;ADCB-1;Supplier;125.50;Debit\n";
        $upload = fn () => ['bank' => 'adcb', 'bank_account_id' => $account->id,
            'statement' => UploadedFile::fake()->createWithContent('adcb.csv', $csv)];
        $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.upload'), $upload())->assertRedirect();
        $this->assertSame(125.5, (float) BankStatementTransaction::firstOrFail()->debit);
        $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.upload'), $upload())->assertSessionHasErrors('statement');
        $this->assertDatabaseCount('bank_statement_imports', 1);
    }
}

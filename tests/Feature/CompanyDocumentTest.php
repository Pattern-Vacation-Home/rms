<?php

namespace Tests\Feature;

use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_and_download_company_documents(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.company-documents.index'))
            ->assertOk()->assertSee('Company Document Vault')->assertSee('Trade Licence');

        $this->post(route('admin.company-documents.store'), [
            'type' => 'trade_licence',
            'reference_no' => 'TL-2026-001',
            'issue_date' => '2026-01-01',
            'expires_at' => '2026-12-31',
            'document' => UploadedFile::fake()->create('trade-licence.pdf', 80, 'application/pdf'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $document = CompanyDocument::firstOrFail();
        $this->assertSame('Trade Licence', $document->title);
        $this->assertSame((string) $admin->id, (string) $document->uploaded_by);
        Storage::disk('public')->assertExists($document->file_path);
        $this->get(route('admin.company-documents.download', $document))->assertOk();

        $this->put(route('admin.company-documents.update', $document), [
            'type' => 'custom',
            'custom_title' => 'Civil Defence Certificate',
            'reference_no' => 'CDC-100',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Civil Defence Certificate', $document->fresh()->title);

        $path = $document->file_path;
        $this->delete(route('admin.company-documents.destroy', $document))->assertRedirect();
        $this->assertDatabaseMissing('company_documents', ['id' => $document->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_custom_company_document_requires_a_name(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.company-documents.store'), [
            'type' => 'custom',
            'document' => UploadedFile::fake()->create('custom.pdf', 20, 'application/pdf'),
        ])->assertSessionHasErrors('custom_title');
    }
}

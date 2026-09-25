<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyDocument;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyDocumentController extends Controller
{
    public function index(Request $request)
    {
        $documents = CompanyDocument::with('uploader')
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner->where('custom_title', 'like', $term)->orWhere('reference_no', 'like', $term));
            })
            ->orderByRaw('expires_at IS NULL, expires_at ASC')
            ->latest('created_at')
            ->get();

        return view('admin.company-documents.index', [
            'documents' => $documents,
            'types' => CompanyDocument::TYPES,
            'summary' => [
                'total' => $documents->count(),
                'valid' => $documents->where('expiry_status', 'valid')->count(),
                'attention' => $documents->whereIn('expiry_status', ['expiring', 'expired'])->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $file = $request->file('document');
        $data['file_path'] = MediaStorage::store($file, 'company-documents');
        $data['original_name'] = $file->getClientOriginalName();
        $data['uploaded_by'] = $request->user()->id;
        CompanyDocument::create($data);

        return back()->with('success', 'Company document uploaded successfully.');
    }

    public function update(Request $request, CompanyDocument $document)
    {
        $data = $this->validated($request, false);
        if ($request->hasFile('document')) {
            Storage::disk(MediaStorage::disk())->delete(MediaStorage::path($document->file_path));
            $file = $request->file('document');
            $data['file_path'] = MediaStorage::store($file, 'company-documents');
            $data['original_name'] = $file->getClientOriginalName();
        }
        $document->update($data);

        return back()->with('success', 'Company document updated successfully.');
    }

    public function download(CompanyDocument $document)
    {
        $disk = Storage::disk(MediaStorage::disk());
        $path = MediaStorage::path($document->file_path);
        abort_unless($disk->exists($path), 404, 'Document file is unavailable.');

        return $disk->download($path, $document->original_name ?: $document->title.'.pdf');
    }

    public function destroy(CompanyDocument $document)
    {
        Storage::disk(MediaStorage::disk())->delete(MediaStorage::path($document->file_path));
        $document->delete();

        return back()->with('success', 'Company document deleted.');
    }

    private function validated(Request $request, bool $fileRequired): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys(CompanyDocument::TYPES))],
            'custom_title' => ['nullable', 'required_if:type,custom', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document' => [$fileRequired ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'],
        ]);
    }
}

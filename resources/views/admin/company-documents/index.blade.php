@extends('layouts.app')

@section('title', 'Company Documents')

@section('content')
@php
    $statusMeta = fn ($status) => match ($status) {
        'expired' => ['Expired', 'danger', 'ri-error-warning-line'],
        'expiring' => ['Expiring Soon', 'warning', 'ri-timer-line'],
        'valid' => ['Valid', 'success', 'ri-shield-check-line'],
        default => ['No Expiry', 'secondary', 'ri-infinity-line'],
    };
@endphp
<style>
.company-doc-hero{background:linear-gradient(120deg,#132f48,#5638d8);color:#fff;border-radius:18px;padding:24px;box-shadow:0 16px 36px rgba(29,42,82,.15)}
.doc-stat{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);border-radius:14px;padding:12px 16px;min-width:125px}.doc-stat strong{font-size:22px;display:block}.doc-type-icon{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;background:#eeeafd;color:#6046e8;font-size:21px}.document-row{transition:.18s ease}.document-row:hover{background:#fafaff}.upload-drop{border:1.5px dashed #c9c3f6;border-radius:14px;background:#faf9ff;padding:14px}
</style>

<div class="company-doc-hero mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div><div class="text-uppercase small opacity-75 mb-1" style="letter-spacing:1.4px">Administration</div><h3 class="text-white mb-1">Company Document Vault</h3><p class="mb-0 opacity-75">Licences, establishment records and custom company files in one secure place.</p></div>
    <div class="d-flex flex-wrap gap-2"><div class="doc-stat"><strong>{{ $summary['total'] }}</strong><span>Total documents</span></div><div class="doc-stat"><strong>{{ $summary['valid'] }}</strong><span>Valid</span></div><div class="doc-stat"><strong>{{ $summary['attention'] }}</strong><span>Need attention</span></div></div>
</div>

@if(session('success'))<div class="alert alert-success"><i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><strong>Please check the document details.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card sticky-xl-top" style="top:90px">
            <div class="card-header"><h4 class="card-title mb-1">Add Company Document</h4><p class="text-muted small mb-0">PDF or image, maximum 20 MB.</p></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.company-documents.store') }}" enctype="multipart/form-data" class="row g-3" data-company-document-form>@csrf
                    <div class="col-12"><label class="form-label">Document Type</label><select name="type" class="form-select" required data-document-type>@foreach($types as $value=>$label)<option value="{{ $value }}" @selected(old('type','trade_licence')===$value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-12 d-none" data-custom-title><label class="form-label">Custom Document Name</label><input name="custom_title" class="form-control" value="{{ old('custom_title') }}" placeholder="Example: Chamber of Commerce Certificate"></div>
                    <div class="col-12"><label class="form-label">Reference / Licence Number</label><input name="reference_no" class="form-control" value="{{ old('reference_no') }}" placeholder="Optional"></div>
                    <div class="col-6"><label class="form-label">Issue Date</label><input type="date" name="issue_date" value="{{ old('issue_date') }}" class="form-control"></div>
                    <div class="col-6"><label class="form-label">Expiry Date</label><input type="date" name="expires_at" value="{{ old('expires_at') }}" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Document File</label><div class="upload-drop"><input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required><small class="text-muted d-block mt-2"><i class="ri-lock-line me-1"></i>Stored in the configured secure media storage.</small></div></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3" placeholder="Optional internal notes">{{ old('notes') }}</textarea></div>
                    <div class="col-12"><button class="btn btn-primary w-100"><i class="ri-upload-cloud-2-line me-1"></i>Upload Document</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2"><div><h4 class="card-title mb-1">Stored Documents</h4><p class="text-muted small mb-0">Open, replace or update company records.</p></div><form class="d-flex gap-2"><input name="q" class="form-control" value="{{ request('q') }}" placeholder="Search number or name"><select name="type" class="form-select"><option value="">All types</option>@foreach($types as $value=>$label)<option value="{{ $value }}" @selected(request('type')===$value)>{{ $label }}</option>@endforeach</select><button class="btn btn-outline-primary">Filter</button></form></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead class="bg-light-subtle"><tr><th>Document</th><th>Reference</th><th>Validity</th><th>Uploaded</th><th class="text-end">Actions</th></tr></thead><tbody>
            @forelse($documents as $document)
                @php($meta=$statusMeta($document->expiry_status))
                <tr class="document-row"><td><div class="d-flex align-items-center gap-3"><span class="doc-type-icon"><i class="{{ $document->type==='trade_licence'?'ri-government-line':($document->type==='establishment_card'?'ri-id-card-line':'ri-file-shield-2-line') }}"></i></span><div><strong>{{ $document->title }}</strong><div class="small text-muted">{{ $document->original_name ?: 'Company file' }}</div></div></div></td><td>{{ $document->reference_no ?: '—' }}</td><td><span class="badge bg-{{ $meta[1] }}-subtle text-{{ $meta[1] }}"><i class="{{ $meta[2] }} me-1"></i>{{ $meta[0] }}</span><div class="small text-muted mt-1">{{ $document->expires_at?->format('d M Y') ?? 'No expiry date' }}</div></td><td><span>{{ $document->created_at?->format('d M Y') }}</span><div class="small text-muted">{{ $document->uploader?->name ?? 'System' }}</div></td><td><div class="d-flex justify-content-end gap-1"><a class="btn btn-sm btn-dark" href="{{ route('admin.company-documents.download',$document) }}"><i class="ri-download-2-line"></i></a><button type="button" class="btn btn-sm btn-soft-primary" data-bs-toggle="collapse" data-bs-target="#edit-company-document-{{ $document->id }}"><i class="ri-pencil-line"></i></button><form method="POST" action="{{ route('admin.company-documents.destroy',$document) }}" onsubmit="return confirm('Delete this company document and its file?')">@csrf @method('DELETE')<button class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button></form></div></td></tr>
                <tr class="collapse" id="edit-company-document-{{ $document->id }}"><td colspan="5" class="bg-light-subtle"><form method="POST" action="{{ route('admin.company-documents.update',$document) }}" enctype="multipart/form-data" class="row g-2 p-2" data-company-document-form>@csrf @method('PUT')<div class="col-md-4"><label class="form-label small">Type</label><select name="type" class="form-select" data-document-type>@foreach($types as $value=>$label)<option value="{{ $value }}" @selected($document->type===$value)>{{ $label }}</option>@endforeach</select></div><div class="col-md-4 {{ $document->type==='custom'?'':'d-none' }}" data-custom-title><label class="form-label small">Custom name</label><input name="custom_title" value="{{ $document->custom_title }}" class="form-control"></div><div class="col-md-4"><label class="form-label small">Reference</label><input name="reference_no" value="{{ $document->reference_no }}" class="form-control"></div><div class="col-md-3"><label class="form-label small">Issue date</label><input type="date" name="issue_date" value="{{ $document->issue_date?->format('Y-m-d') }}" class="form-control"></div><div class="col-md-3"><label class="form-label small">Expiry date</label><input type="date" name="expires_at" value="{{ $document->expires_at?->format('Y-m-d') }}" class="form-control"></div><div class="col-md-6"><label class="form-label small">Replace file (optional)</label><input type="file" name="document" class="form-control"></div><div class="col-12"><label class="form-label small">Notes</label><textarea name="notes" class="form-control" rows="2">{{ $document->notes }}</textarea></div><div class="col-12 text-end"><button class="btn btn-primary"><i class="ri-save-line me-1"></i>Save Changes</button></div></form></td></tr>
            @empty<tr><td colspan="5" class="text-center py-5"><i class="ri-folder-open-line fs-1 text-muted"></i><h5 class="mt-2">No company documents yet</h5><p class="text-muted mb-0">Start with the Trade Licence or Establishment Card.</p></td></tr>@endforelse
            </tbody></table></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-company-document-form]').forEach(form => {
    const type = form.querySelector('[data-document-type]');
    const custom = form.querySelector('[data-custom-title]');
    const sync = () => custom?.classList.toggle('d-none', type?.value !== 'custom');
    type?.addEventListener('change', sync); sync();
});
</script>
@endpush

@extends('layouts.app')

@section('content')
<style>
    .access-hero{background:linear-gradient(135deg,#102b4e,#4035a8);border-radius:18px;padding:25px;color:#fff;display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:20px;box-shadow:0 12px 28px rgba(22,43,83,.13)}
    .access-hero h2{color:#fff;margin:0 0 6px}.access-hero p{margin:0;color:#dce5f5}.access-stat{min-width:120px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.16);border-radius:12px;padding:12px 16px;text-align:center}.access-stat strong{display:block;font-size:24px}.access-tabs{display:flex;gap:8px;margin-bottom:18px}.access-tab{border:1px solid #dfe4ef;background:#fff;padding:10px 17px;border-radius:9px;color:#58657a}.access-tab.active{background:#5b47db;color:#fff;border-color:#5b47db}.access-panel[hidden]{display:none!important}
    .permission-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.permission-row{display:grid;grid-template-columns:minmax(160px,1fr) 82px 82px;align-items:center;gap:8px;border:1px solid #e5e9f2;border-radius:10px;padding:11px 13px}.permission-module{display:flex;gap:10px;align-items:center;font-weight:600}.permission-module i{width:32px;height:32px;border-radius:8px;display:grid;place-items:center;background:#eeeaff;color:#5b47db;font-size:17px}.permission-toggle{text-align:center;font-size:12px;color:#718096}.permission-toggle input{display:block;margin:0 auto 4px;width:17px;height:17px}.role-card{border:1px solid #e5e9f2;border-radius:13px;padding:16px;height:100%;background:#fff}.role-card h5{margin-bottom:4px}.role-chip{display:inline-block;background:#eef2f8;border-radius:99px;padding:4px 9px;margin:3px 2px;font-size:11px;color:#506078}.staff-avatar{width:38px;height:38px;border-radius:50%;display:grid;place-items:center;background:#ece9ff;color:#5b47db;font-weight:700}.access-badge{border-radius:99px;padding:5px 9px;font-size:11px;font-weight:600}.access-badge.active{background:#ddf5e7;color:#14794d}.access-badge.inactive{background:#ffe4e5;color:#b93c45}
    @media(max-width:850px){.access-hero{align-items:flex-start;flex-direction:column}.access-hero>div:last-child{display:flex;width:100%;gap:8px}.access-stat{min-width:0;flex:1}.permission-grid{grid-template-columns:1fr}.permission-row{grid-template-columns:1fr 60px 60px}}
</style>

<div class="access-hero">
    <div><div class="text-uppercase small mb-2" style="letter-spacing:1.4px;color:#d4bb85">Administration</div><h2>Roles &amp; Permissions</h2><p>Give every team member only the access needed for their work.</p></div>
    <div class="d-flex gap-2"><div class="access-stat"><strong>{{ $roles->count() }}</strong><span>Roles</span></div><div class="access-stat"><strong>{{ $staff->count() }}</strong><span>Staff users</span></div></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><strong>Please check the form:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="access-tabs" role="tablist"><button type="button" class="access-tab active" data-access-tab="roles"><i class="ri-shield-user-line me-1"></i> Roles</button><button type="button" class="access-tab" data-access-tab="staff"><i class="ri-team-line me-1"></i> Team Access</button></div>

<section class="access-panel" data-access-panel="roles">
    <div class="row g-3">
        <div class="col-xl-4"><div class="card h-100"><div class="card-header"><h4 class="card-title mb-1">Create a role</h4><small class="text-muted">Start with a name, then choose View and Manage access.</small></div><div class="card-body">
            <form method="POST" action="{{ route('admin.access-control.roles.store') }}">@csrf
                <label class="form-label" for="new-role-name">Role name</label><input class="form-control mb-3" id="new-role-name" name="name" placeholder="For example: Booking Supervisor" required>
                @include('admin.access-control.permission-grid', ['prefix' => 'new'])
                <button class="btn btn-primary w-100 mt-3"><i class="ri-add-line me-1"></i>Create Role</button>
            </form>
        </div></div></div>
        <div class="col-xl-8"><div class="row g-3">
            @foreach($roles as $role)
            <div class="col-lg-6"><div class="role-card">
                <div class="d-flex justify-content-between align-items-start gap-2"><div><h5>{{ $role->name }}</h5><small class="text-muted">{{ $role->users_count }} staff member(s)</small></div>@if($role->name === 'Super Administrator')<span class="badge bg-warning-subtle text-warning">Protected</span>@else<button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#role-{{ $role->id }}"><i class="ri-pencil-line"></i></button>@endif</div>
                <div class="mt-3">@foreach($modules as $key => [$label,$icon])@if($role->hasPermissionTo($key.'.view'))<span class="role-chip">{{ $label }} · {{ $role->hasPermissionTo($key.'.manage') ? 'Manage' : 'View' }}</span>@endif @endforeach</div>
            </div></div>
            @if($role->name !== 'Super Administrator')
            <div class="modal fade" id="role-{{ $role->id }}" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><form method="POST" action="{{ route('admin.access-control.roles.update', $role) }}">@csrf @method('PUT')<div class="modal-header"><div><h5 class="modal-title">Edit role</h5><small class="text-muted">Changes apply to every user assigned to this role.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Role name</label><input class="form-control mb-3" name="name" value="{{ $role->name }}" required>@include('admin.access-control.permission-grid', ['prefix' => 'role-'.$role->id, 'currentRole' => $role])</div><div class="modal-footer justify-content-between"><button type="submit" form="delete-role-{{ $role->id }}" class="btn btn-outline-danger" onclick="return confirm('Delete this unused role?')">Delete Role</button><div><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Permissions</button></div></div></form><form id="delete-role-{{ $role->id }}" method="POST" action="{{ route('admin.access-control.roles.destroy', $role) }}">@csrf @method('DELETE')</form></div></div></div>
            @endif
            @endforeach
        </div></div>
    </div>
</section>

<section class="access-panel" data-access-panel="staff" hidden>
    <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><div><h4 class="card-title mb-1">Administrative team</h4><small class="text-muted">Assign one role to each staff account. Business users such as owners, tenants and maintainers remain in their own modules.</small></div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#new-staff"><i class="ri-user-add-line me-1"></i>Add Staff User</button></div><div class="card-body p-0"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Staff member</th><th>Role</th><th>Effective access</th><th>Status</th><th class="text-end">Update</th></tr></thead><tbody>
        @foreach($staff as $user)<tr><td><div class="d-flex align-items-center gap-2"><span class="staff-avatar">{{ strtoupper(substr($user->name,0,1)) }}</span><div><strong>{{ $user->name }}</strong><small class="d-block text-muted">{{ $user->email }}</small></div></div></td><td><select class="form-select" name="role_id" form="staff-{{ $user->id }}" required>@foreach($roles as $role)<option value="{{ $role->id }}" @selected($user->roles->first()?->id === $role->id)>{{ $role->name }}</option>@endforeach</select></td><td><small>{{ $user->roles->first()?->permissions->filter(fn($permission) => str_ends_with($permission->name,'.manage'))->count() ?? 0 }} manageable · {{ $user->roles->first()?->permissions->count() ?? 0 }} permission(s)</small></td><td><select class="form-select" name="is_active" form="staff-{{ $user->id }}"><option value="1" @selected($user->is_active)>Active</option><option value="0" @selected(!$user->is_active)>Disabled</option></select></td><td class="text-end"><form id="staff-{{ $user->id }}" method="POST" action="{{ route('admin.access-control.users.update',$user) }}">@csrf @method('PUT')<button class="btn btn-sm btn-outline-primary">Save</button></form></td></tr>@endforeach
    </tbody></table></div></div></div>
</section>

<div class="modal fade" id="new-staff" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('admin.access-control.users.store') }}">@csrf<div class="modal-header"><div><h5 class="modal-title">Add Staff User</h5><small class="text-muted">Create secure administrative access.</small></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-12"><label class="form-label">Full name</label><input class="form-control" name="name" required></div><div class="col-12"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div><div class="col-12"><label class="form-label">Phone</label><input class="form-control" name="phone"></div><div class="col-12"><label class="form-label">Role</label><select class="form-select" name="role_id" required>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Temporary password</label><input class="form-control" type="password" name="password" required></div><div class="col-md-6"><label class="form-label">Confirm password</label><input class="form-control" type="password" name="password_confirmation" required></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create Staff User</button></div></form></div></div></div>
@endsection

@section('script')<script>
document.querySelectorAll('[data-access-tab]').forEach(button=>button.addEventListener('click',()=>{document.querySelectorAll('[data-access-tab]').forEach(x=>x.classList.toggle('active',x===button));document.querySelectorAll('[data-access-panel]').forEach(x=>x.hidden=x.dataset.accessPanel!==button.dataset.accessTab)}));
document.querySelectorAll('[data-manage-permission]').forEach(input=>input.addEventListener('change',()=>{if(input.checked){const view=document.getElementById(input.dataset.view);if(view)view.checked=true}}));
document.querySelectorAll('[data-view-permission]').forEach(input=>input.addEventListener('change',()=>{if(!input.checked){const manage=document.getElementById(input.dataset.manage);if(manage)manage.checked=false}}));
</script>@endsection

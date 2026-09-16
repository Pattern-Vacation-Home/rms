@extends('layouts.app')

@section('content')
@php
    $photoUrl = $user->profile_photo ? \App\Support\MediaStorage::url($user->profile_photo) : null;
    $initials = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
    $roleName = $user->role === 'admin' ? ($user->roles->first()?->name ?? 'Administrator') : ucfirst($user->role);
@endphp
<style>
    .my-profile{max-width:1100px;margin:0 auto 32px}.my-profile-hero{background:linear-gradient(120deg,#102942,#313695 75%,#5b47db);color:#fff;border-radius:18px;padding:28px 30px;display:flex;align-items:center;gap:20px;box-shadow:0 15px 30px rgba(16,41,66,.12)}.my-profile-hero h2{color:#fff;margin:0}.my-profile-hero p{color:#dce5f4;margin:4px 0 0}.my-profile-avatar{width:82px;height:82px;flex:none;border-radius:22px;background:#e9e7ff;color:#5141cd;display:grid;place-items:center;font-size:28px;font-weight:700;overflow:hidden;border:3px solid rgba(255,255,255,.45)}.my-profile-avatar img{width:100%;height:100%;object-fit:cover}.my-profile-role{display:inline-flex;align-items:center;gap:5px;border:1px solid rgba(255,255,255,.35);border-radius:99px;padding:5px 10px;font-size:12px;margin-top:10px;color:#fff}.my-profile-card{background:#fff;border:1px solid #e5eaf1;border-radius:16px;padding:24px;height:100%;box-shadow:0 5px 18px rgba(19,40,73,.04)}.my-profile-card h4{font-size:18px;margin:0 0 4px}.my-profile-card .lead-text{font-size:13px;color:#718096;margin-bottom:22px}.my-profile-card .form-label{font-weight:600}.my-profile-card .form-control{min-height:43px}.my-profile-note{background:#f4f7fc;border-radius:10px;padding:12px 14px;color:#617084;font-size:12px}.my-profile-action{min-width:145px}.my-profile-photo{display:flex;align-items:center;gap:14px}.my-profile-photo .my-profile-avatar{width:56px;height:56px;border:0;border-radius:14px;font-size:18px}.my-profile-error{color:#d44949;font-size:12px;margin-top:4px}
    @media(max-width:600px){.my-profile-hero{padding:20px;align-items:flex-start}.my-profile-hero .my-profile-avatar{width:64px;height:64px;border-radius:17px;font-size:21px}.my-profile-hero h2{font-size:20px}.my-profile-card{padding:19px}}
</style>

<div class="my-profile">
    <div class="my-profile-hero mb-4">
        <div class="my-profile-avatar">@if($photoUrl)<img src="{{ $photoUrl }}" alt="{{ $user->name }}">@else{{ $initials }}@endif</div>
        <div><div class="text-uppercase small mb-1" style="letter-spacing:1.3px;color:#cad5ef">My account</div><h2>{{ $user->name }}</h2><p>Keep your contact details and account security up to date.</p><span class="my-profile-role"><i class="ri-shield-user-line"></i>{{ $roleName }}</span></div>
    </div>

    @if(session('status') === 'profile-updated')<div class="alert alert-success"><i class="ri-checkbox-circle-line me-1"></i> Profile updated successfully.</div>@endif
    @if(session('status') === 'password-updated')<div class="alert alert-success"><i class="ri-checkbox-circle-line me-1"></i> Password changed successfully.</div>@endif

    <div class="row g-4">
        <div class="col-lg-7"><div class="my-profile-card">
            <h4>Personal information</h4><p class="lead-text">Your photo and contact details appear on your account.</p>
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PATCH')
                <div class="my-profile-photo mb-4"><div class="my-profile-avatar">@if($photoUrl)<img src="{{ $photoUrl }}" alt="Current profile photo">@else{{ $initials }}@endif</div><div class="flex-grow-1"><label for="profile_photo" class="form-label mb-1">Profile photo</label><input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="form-control"><small class="text-muted">JPG, PNG or WebP · up to 5 MB</small>@error('profile_photo')<div class="my-profile-error">{{ $message }}</div>@enderror</div></div>
                <div class="row g-3"><div class="col-md-6"><label class="form-label" for="profile_name">Full name</label><input id="profile_name" name="name" class="form-control" value="{{ old('name',$user->name) }}" required maxlength="255" autocomplete="name">@error('name')<div class="my-profile-error">{{ $message }}</div>@enderror</div><div class="col-md-6"><label class="form-label" for="profile_phone">Phone</label><input id="profile_phone" name="phone" type="tel" class="form-control" value="{{ old('phone',$user->phone) }}" maxlength="50" autocomplete="tel">@error('phone')<div class="my-profile-error">{{ $message }}</div>@enderror</div><div class="col-12"><label class="form-label" for="profile_email">Email address</label><input id="profile_email" name="email" type="email" class="form-control" value="{{ old('email',$user->email) }}" required autocomplete="email">@error('email')<div class="my-profile-error">{{ $message }}</div>@enderror</div></div>
                <div class="my-profile-note mt-4"><i class="ri-information-line me-1"></i> Your assigned role and permissions are managed by an administrator.</div>
                <div class="d-flex justify-content-end mt-4"><button class="btn btn-primary my-profile-action"><i class="ri-save-line me-1"></i>Save changes</button></div>
            </form>
        </div></div>
        <div class="col-lg-5"><div class="my-profile-card">
            <h4>Change password</h4><p class="lead-text">Use a strong password that you do not use elsewhere.</p>
            <form method="POST" action="{{ route('password.update') }}">@csrf @method('PUT')
                <div class="mb-3"><label class="form-label" for="current_password">Current password</label><input id="current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" required>@error('current_password','updatePassword')<div class="my-profile-error">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label" for="password">New password</label><input id="password" name="password" type="password" class="form-control" autocomplete="new-password" required>@error('password','updatePassword')<div class="my-profile-error">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label" for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required></div>
                <div class="my-profile-note"><i class="ri-lock-2-line me-1"></i> Changing your password does not change your role or access permissions.</div>
                <div class="d-flex justify-content-end mt-4"><button class="btn btn-outline-primary my-profile-action"><i class="ri-lock-password-line me-1"></i>Update password</button></div>
            </form>
        </div></div>
    </div>
</div>
@endsection

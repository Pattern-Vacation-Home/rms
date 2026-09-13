@extends('layouts.tenant-pwa')
@section('content')
<main style="max-width:640px;margin:24px auto;padding:24px;background:white;border-radius:16px">
    <h1 style="font-size:24px">{{ $tenant->tenant_profile_required ? 'Complete your guest profile' : 'My Profile' }}</h1>
    <p>We filled in the details available from your booking. Please review and update your information.</p>
    @if($errors->any())<div role="alert" style="color:#a02020">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <p><strong>Login email:</strong> {{ $tenant->email }}<br><small>Contact management if this email is incorrect.</small></p>
    <form method="POST" action="{{ route('tenant.profile.update') }}">@csrf @method('PUT')
    @foreach(['name'=>'Full name','phone'=>'Phone','eid_passport_no'=>'Passport / Emirates ID number','nationality'=>'Nationality','dob'=>'Date of birth','address'=>'Home address','emergency_contact_name'=>'Emergency contact name (optional)','emergency_contact_phone'=>'Emergency contact phone (optional)'] as $field=>$label)
        <label for="profile-{{ $field }}" style="display:block;margin:16px 0 6px">{{ $label }}</label>
        <input id="profile-{{ $field }}" name="{{ $field }}" type="{{ $field==='dob'?'date':'text' }}" value="{{ old($field,$field==='dob'?$tenant->dob?->toDateString():$tenant->{$field}) }}" @required(!str_starts_with($field,'emergency_')) style="width:100%;box-sizing:border-box;padding:12px;border:1px solid #cdd5e0;border-radius:8px;font:inherit">
    @endforeach
        <button style="margin-top:24px;width:100%;padding:14px;background:#0b2f6b;color:white;border:0;border-radius:8px;font:inherit">Save & Continue</button>
    </form>
    <section style="margin-top:28px;padding-top:22px;border-top:1px solid #dce3ed">
        <h2 style="font-size:20px">Change app password</h2>
        <p>Use this after signing in with a temporary password.</p>
        @if(session('status') === 'password-updated')<p role="status" style="color:#147d50">Password updated.</p>@endif
        @if($errors->updatePassword->any())<div role="alert" style="color:#a02020">@foreach($errors->updatePassword->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form method="POST" action="{{ route('password.update') }}">@csrf @method('PUT')
            <label for="current_password" style="display:block;margin:12px 0 6px">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required style="width:100%;box-sizing:border-box;padding:12px;border:1px solid #cdd5e0;border-radius:8px;font:inherit">
            <label for="new_password" style="display:block;margin:12px 0 6px">New password</label><input id="new_password" name="password" type="password" autocomplete="new-password" required style="width:100%;box-sizing:border-box;padding:12px;border:1px solid #cdd5e0;border-radius:8px;font:inherit">
            <label for="password_confirmation" style="display:block;margin:12px 0 6px">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required style="width:100%;box-sizing:border-box;padding:12px;border:1px solid #cdd5e0;border-radius:8px;font:inherit">
            <button style="margin-top:16px;width:100%;padding:13px;background:#5646cc;color:white;border:0;border-radius:8px;font:inherit">Update Password</button>
        </form>
    </section>
    <form method="POST" action="{{ route('logout') }}" style="margin-top:16px">@csrf<button style="background:none;border:0;color:#0b2f6b">Sign out</button></form>
</main>
@endsection

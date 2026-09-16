<div class="permission-grid">
@foreach($modules as $key => [$label, $icon])
@php($viewId=$prefix.'-'.$key.'-view') @php($manageId=$prefix.'-'.$key.'-manage')
<div class="permission-row"><div class="permission-module"><i class="{{ $icon }}"></i><span>{{ $label }}</span></div><label class="permission-toggle" for="{{ $viewId }}"><input id="{{ $viewId }}" type="checkbox" name="permissions[]" value="{{ $key }}" data-view-permission data-manage="{{ $manageId }}" @checked(isset($currentRole) && $currentRole->hasPermissionTo($key.'.view'))>View</label><label class="permission-toggle" for="{{ $manageId }}"><input id="{{ $manageId }}" type="checkbox" name="manage[]" value="{{ $key }}" data-manage-permission data-view="{{ $viewId }}" @checked(isset($currentRole) && $currentRole->hasPermissionTo($key.'.manage'))>Manage</label></div>
@endforeach
</div>

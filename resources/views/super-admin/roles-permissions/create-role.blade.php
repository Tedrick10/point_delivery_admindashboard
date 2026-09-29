@extends('super-admin.layout')

@section('title', __('message.add_form_title', ['form' => __('message.role')]))
@section('page_title', __('message.add_form_title', ['form' => __('message.role')]))
@section('page_sub', __('message.sa_screen_roles_permissions_sub'))

@section('content')
<div class="sa-module-page" style="padding-top: 0;">
    <a href="{{ route('super-admin.screens.show', ['screen' => 'roles-permissions']) }}" class="sa-module-page__back">
        <i class="fas fa-arrow-left"></i> {{ __('message.sa_screen_roles_permissions') }}
    </a>

    <div class="sa-card" style="max-width: 560px;">
        <form method="POST" action="{{ route('super-admin.roles-permissions.store-role') }}">
            @csrf
            <div class="sa-form-grid">
                <div class="sa-field full">
                    <label>{{ __('message.name') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="sa-field full">
                    <label>{{ __('message.employee_type') }}</label>
                    <select name="employee_type_id">
                        <option value="">{{ __('message.select_name', ['select' => __('message.employee_type')]) }}</option>
                        @foreach(($employeeTypes ?? []) as $type)
                            <option value="{{ $type->id }}" @selected((string) old('employee_type_id') === (string) $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_type_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="sa-field full">
                    <label>{{ __('message.new_employee_type') }}</label>
                    <input type="text" name="employee_type_name" value="{{ old('employee_type_name') }}"
                           placeholder="{{ __('message.new_employee_type_placeholder') }}">
                    <small style="color:#64748b;">{{ __('message.new_employee_type_hint') }}</small>
                    @error('employee_type_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-3 d-flex" style="gap: 10px;">
                <button type="submit" class="sa-btn sa-btn-primary">{{ __('message.save') }}</button>
                <a href="{{ route('super-admin.screens.show', ['screen' => 'roles-permissions']) }}" class="sa-btn sa-btn-ghost">{{ __('message.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('super-admin.layout')

@section('title', __('message.add_form_title', ['form' => __('message.role')]))
@section('page_title', __('message.add_form_title', ['form' => __('message.role')]))

@section('content')
<div class="sa-module-page" style="padding-top: 0;">
    <section class="sa-module-panel sa-form-panel sa-role-form">
        <header class="sa-module-panel__head">
            <div>
                <h3>{{ __('message.role') }}</h3>
            </div>
        </header>

        <form method="POST" action="{{ route('super-admin.roles-permissions.store-role') }}">
            @csrf

            <div class="sa-form-grid">
                <div class="sa-field">
                    <label>{{ __('message.name') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autocomplete="off">
                    @error('name')<div class="sa-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="sa-field">
                    <label>{{ __('message.employee_type') }}</label>
                    <input type="text" name="employee_type_name" value="{{ old('employee_type_name') }}"
                           placeholder="{{ __('message.new_employee_type_placeholder') }}" required autocomplete="off">
                    @error('employee_type_name')<div class="sa-field-error">{{ $message }}</div>@enderror
                    @error('employee_type_id')<div class="sa-field-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="sa-form-footer">
                <button type="submit" class="sa-btn sa-btn-primary">{{ __('message.save') }}</button>
                <a href="{{ route('super-admin.screens.show', ['screen' => 'roles-permissions']) }}" class="sa-btn sa-btn-ghost">
                    {{ __('message.cancel') }}
                </a>
            </div>
        </form>
    </section>
</div>
@endsection

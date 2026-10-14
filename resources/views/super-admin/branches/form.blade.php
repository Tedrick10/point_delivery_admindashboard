@extends('super-admin.layout')

@php
    $isEdit = !empty($branch);
@endphp

@section('title', $isEdit ? __('message.sa_edit_branch') : __('message.sa_new_branch'))
@section('page_title', $isEdit ? __('message.sa_edit_branch') : __('message.sa_new_branch'))
@section('page_sub', __('message.sa_new_branch_sub'))

@section('content')
<div class="sa-module-panel sa-form-panel">
    <form method="POST" action="{{ $isEdit ? route('super-admin.branches.update', $branch->id) : route('super-admin.branches.store') }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="sa-form-grid">
            <div class="sa-field">
                <label for="sa-branch-name">{{ __('message.branch_name') }}</label>
                <input id="sa-branch-name" type="text" name="name" value="{{ old('name', $branch->name ?? '') }}" required autocomplete="organization" placeholder="{{ __('message.branch_name') }}">
                @error('name')
                    <div class="sa-field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="sa-field">
                <label for="sa-branch-code">{{ __('message.code') }}</label>
                <input id="sa-branch-code" type="text" name="code" value="{{ old('code', $branch->code ?? '') }}" autocomplete="off" placeholder="MDY">
                @error('code')
                    <div class="sa-field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="sa-field">
                <label for="sa-branch-city">{{ __('message.city_name') }}</label>
                <input id="sa-branch-city" type="text" name="city_name" value="{{ old('city_name', $branch->city_name ?? '') }}" autocomplete="address-level2" placeholder="Mandalay">
                @error('city_name')
                    <div class="sa-field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="sa-field">
                <label for="sa-branch-phone">{{ __('message.phone') }}</label>
                <input id="sa-branch-phone" type="text" name="phone" value="{{ old('phone', $branch->phone ?? '') }}" autocomplete="tel" placeholder="09">
                @error('phone')
                    <div class="sa-field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="sa-field full">
                <label for="sa-branch-address">{{ __('message.address') }}</label>
                <input id="sa-branch-address" type="text" name="address" value="{{ old('address', $branch->address ?? '') }}" autocomplete="street-address" placeholder="{{ __('message.address') }}">
                @error('address')
                    <div class="sa-field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="sa-field">
                <label for="sa-branch-status">{{ __('message.status') }}</label>
                <select id="sa-branch-status" name="status" required>
                    <option value="1" @selected((string) old('status', $branch->status ?? 1) === '1')>{{ __('message.active') }}</option>
                    <option value="0" @selected((string) old('status', $branch->status ?? 1) === '0')>{{ __('message.inactive') }}</option>
                </select>
            </div>
            @if(\Illuminate\Support\Facades\Schema::hasColumn('branches', 'delivery_settlement_mode'))
                <div class="sa-field">
                    <label for="sa-branch-settlement">{{ __('message.branch_settlement_mode') }}</label>
                    <select id="sa-branch-settlement" name="delivery_settlement_mode">
                        @php
                            $currentMode = old(
                                'delivery_settlement_mode',
                                $branch?->settlementMode() ?? \App\Models\Branch::SETTLEMENT_MANUAL
                            );
                        @endphp
                        <option value="{{ \App\Models\Branch::SETTLEMENT_MANUAL }}" @selected($currentMode === \App\Models\Branch::SETTLEMENT_MANUAL)>
                            {{ __('message.branch_settlement_manual') }}
                        </option>
                        <option value="{{ \App\Models\Branch::SETTLEMENT_MANUAL_HALF_DELI }}" @selected($currentMode === \App\Models\Branch::SETTLEMENT_MANUAL_HALF_DELI)>
                            {{ __('message.branch_settlement_manual_half_deli') }}
                        </option>
                        <option value="{{ \App\Models\Branch::SETTLEMENT_HALF_DELI }}" @selected($currentMode === \App\Models\Branch::SETTLEMENT_HALF_DELI)>
                            {{ __('message.branch_settlement_half_deli') }}
                        </option>
                    </select>
                </div>
            @endif
        </div>

        <div class="sa-form-footer">
            <button type="submit" class="sa-btn sa-btn-primary">
                {{ $isEdit ? __('message.sa_save_changes') : __('message.sa_create_branch') }}
            </button>
            <a href="{{ route('super-admin.branches.index') }}" class="sa-btn sa-btn-ghost">{{ __('message.cancel') }}</a>
        </div>
    </form>
</div>
@endsection

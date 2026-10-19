<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-branch-admin-form-page">
        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                            <p class="text-muted mb-0 mt-1" style="font-size:13px;">{{ __('message.sa_one_admin_per_branch') }}</p>
                        </div>
                        <a href="{{ route('branch-admins.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                    </div>
                    <div class="card-body pds-page-body">
                        @if ($errors->any())
                            <div class="alert alert-danger pds-ba-alert" role="alert">
                                <strong>{{ __('message.something_went_wrong') }}</strong>
                                <ul class="mb-0 mt-2 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ $admin ? route('branch-admins.update', $admin->id) : route('branch-admins.store') }}" id="branchAdminForm">
                            @csrf
                            @if($admin)
                                @method('PUT')
                            @endif

                            @php
                                $occupied = collect($occupiedBranchIds ?? [])->map(fn ($id) => (int) $id)->all();
                                $selectedBranchId = (int) old('branch_id', $prefillBranchId ?? 0);
                            @endphp

                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="ba-name">{{ __('message.name') }} <span class="text-danger">*</span></label>
                                    <input id="ba-name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $admin->name ?? '') }}" required autocomplete="name">
                                    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ba-username">{{ __('message.username') }}</label>
                                    <input id="ba-username" type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $admin->username ?? '') }}" autocomplete="username" placeholder="optional">
                                    @error('username')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ba-email">{{ __('message.email') }} <span class="text-danger">*</span></label>
                                    <input id="ba-email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $admin->email ?? '') }}" required autocomplete="email">
                                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ba-phone">{{ __('message.phone') }}</label>
                                    <input id="ba-phone" type="text" name="contact_number" class="form-control @error('contact_number') is-invalid @enderror" value="{{ old('contact_number', $admin->contact_number ?? '') }}" autocomplete="tel">
                                    @error('contact_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ba-branch">{{ __('message.branch') }} <span class="text-danger">*</span></label>
                                    <div class="pds-ba-branch-select @error('branch_id') is-invalid @enderror">
                                        <select id="ba-branch" name="branch_id" class="form-control pds-ba-branch-dropdown" required data-placeholder="{{ __('message.sa_select_branch') }}">
                                            <option value="">{{ __('message.sa_select_branch') }}</option>
                                            @foreach(($branches ?? []) as $branch)
                                                @php
                                                    $bid = (int) $branch->id;
                                                    $isOccupied = in_array($bid, $occupied, true);
                                                    $label = method_exists($branch, 'displayLabel') ? $branch->displayLabel() : $branch->name;
                                                    if ($isOccupied && $selectedBranchId !== $bid) {
                                                        $label .= ' ('.__('message.sa_branch_taken').')';
                                                    }
                                                @endphp
                                                <option
                                                    value="{{ $bid }}"
                                                    @selected($selectedBranchId === $bid)
                                                    @disabled($isOccupied && $selectedBranchId !== $bid)
                                                >{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('branch_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                    <div class="pds-ba-branch-hint">{{ __('message.sa_one_admin_per_branch') }}</div>
                                    @can('branch-add')
                                        <a href="{{ route('branch.create') }}" class="d-inline-block mt-2 pds-ba-new-branch-link">
                                            + {{ __('message.sa_new_branch') }}
                                        </a>
                                    @endcan
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ba-status">{{ __('message.status') }}</label>
                                    <select id="ba-status" name="status" class="form-control">
                                        <option value="1" @selected((string) old('status', $admin->status ?? 1) === '1')>{{ __('message.active') }}</option>
                                        <option value="0" @selected((string) old('status', $admin->status ?? 1) === '0')>{{ __('message.inactive') }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ba-password">{{ __('message.password') }} {{ $admin ? __('message.sa_password_keep') : '' }} @unless($admin)<span class="text-danger">*</span>@endunless</label>
                                    <input id="ba-password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ $admin ? '' : 'required' }} autocomplete="new-password" minlength="6">
                                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ba-password2">{{ __('message.confirm_password') }} @unless($admin)<span class="text-danger">*</span>@endunless</label>
                                    <input id="ba-password2" type="password" name="password_confirmation" class="form-control" {{ $admin ? '' : 'required' }} autocomplete="new-password" minlength="6">
                                </div>
                            </div>

                            <div class="mt-3 pds-ba-form-actions">
                                <button type="submit" class="btn btn-primary" id="baSubmitBtn">{{ $admin ? __('message.save') : __('message.sa_create_admin') }}</button>
                                <a href="{{ route('branch-admins.index') }}" class="btn btn-outline-secondary">{{ __('message.cancel') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('bottom_script')
        <script>
            (function ($) {
                function initBranchSelect() {
                    var $el = $('#ba-branch');
                    if (!$el.length || typeof $.fn.select2 !== 'function') {
                        return;
                    }
                    if ($el.hasClass('select2-hidden-accessible')) {
                        $el.select2('destroy');
                    }
                    $el.select2({
                        width: '100%',
                        placeholder: $el.data('placeholder') || @json(__('message.sa_select_branch')),
                        allowClear: true,
                        dropdownCssClass: 'pds-ba-branch-dropdown-panel',
                    });
                }

                $(document).ready(initBranchSelect);
                $(document).on('admin:live-reloaded', initBranchSelect);

                $('#branchAdminForm').on('submit', function () {
                    var $btn = $('#baSubmitBtn');
                    $btn.prop('disabled', true);
                    setTimeout(function () { $btn.prop('disabled', false); }, 8000);
                });
            })(jQuery);
        </script>
    @endpush
</x-master-layout>

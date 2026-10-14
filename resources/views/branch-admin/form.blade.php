<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                        <a href="{{ route('branch-admins.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                    </div>
                    <div class="card-body pds-page-body">
                        <form method="POST" action="{{ $admin ? route('branch-admins.update', $admin->id) : route('branch-admins.store') }}">
                            @csrf
                            @if($admin)
                                @method('PUT')
                            @endif

                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="ba-name">{{ __('message.name') }} <span class="text-danger">*</span></label>
                                    <input id="ba-name" type="text" name="name" class="form-control" value="{{ old('name', $admin->name ?? '') }}" required autocomplete="name">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ba-username">{{ __('message.username') }}</label>
                                    <input id="ba-username" type="text" name="username" class="form-control" value="{{ old('username', $admin->username ?? '') }}" autocomplete="username">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ba-email">{{ __('message.email') }} <span class="text-danger">*</span></label>
                                    <input id="ba-email" type="email" name="email" class="form-control" value="{{ old('email', $admin->email ?? '') }}" required autocomplete="email">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ba-phone">{{ __('message.phone') }}</label>
                                    <input id="ba-phone" type="text" name="contact_number" class="form-control" value="{{ old('contact_number', $admin->contact_number ?? '') }}" autocomplete="tel">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ba-branch">{{ __('message.branch') }} <span class="text-danger">*</span></label>
                                    <select id="ba-branch" name="branch_id" class="form-control" required>
                                        <option value="">{{ __('message.sa_select_branch') }}</option>
                                        @foreach(($branches ?? []) as $branch)
                                            <option value="{{ $branch->id }}" @selected((int) old('branch_id', $prefillBranchId ?? 0) === (int) $branch->id)>
                                                {{ method_exists($branch, 'displayLabel') ? $branch->displayLabel() : $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('branch_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                    @can('branch-add')
                                        <a href="{{ route('branch.create') }}" class="d-inline-block mt-2" style="color:var(--site-color,#FE6F07);font-weight:600;">
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
                                    <input id="ba-password" type="password" name="password" class="form-control" {{ $admin ? '' : 'required' }} autocomplete="new-password">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ba-password2">{{ __('message.confirm_password') }} @unless($admin)<span class="text-danger">*</span>@endunless</label>
                                    <input id="ba-password2" type="password" name="password_confirmation" class="form-control" {{ $admin ? '' : 'required' }} autocomplete="new-password">
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">{{ $admin ? __('message.save') : __('message.sa_create_admin') }}</button>
                                <a href="{{ route('branch-admins.index') }}" class="btn btn-outline-secondary">{{ __('message.cancel') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>

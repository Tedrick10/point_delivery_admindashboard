<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="card pds-page-card">
            <div class="card-header pds-page-header d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                <div class="d-flex align-items-center" style="gap:8px;">
                    <a href="{{ route('hr.staff.index') }}" class="btn btn-sm {{ empty($group) ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
                    <a href="{{ route('hr.staff.index', ['staff_group' => 'office']) }}" class="btn btn-sm {{ ($group ?? '') === 'office' ? 'btn-primary' : 'btn-outline-primary' }}">Office</a>
                    <a href="{{ route('hr.staff.index', ['staff_group' => 'rider']) }}" class="btn btn-sm {{ ($group ?? '') === 'rider' ? 'btn-primary' : 'btn-outline-primary' }}">Rider</a>
                </div>
            </div>
            <div class="card-body pds-page-body">
                <form method="POST" action="{{ route('hr.staff.store') }}" class="mb-4 border rounded p-3 bg-light">
                    @csrf
                    <div class="row">
                        <div class="form-group col-md-2">
                            <label>{{ __('message.hr_staff_code') }} *</label>
                            <input type="text" name="code" class="form-control" required maxlength="50" placeholder="THW">
                        </div>
                        <div class="form-group col-md-3">
                            <label>{{ __('message.name') }} *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label>{{ __('message.hr_staff_group') }}</label>
                            <select name="staff_group" class="form-control">
                                <option value="office" @selected(($group ?? '') === 'office')>Office</option>
                                <option value="rider" @selected(($group ?? '') === 'rider')>Rider</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label>{{ __('message.hr_monthly_salary') }}</label>
                            <input type="number" step="1" min="0" name="monthly_salary" class="form-control" value="0">
                        </div>
                        <div class="form-group col-md-2">
                            <label>{{ __('message.hr_allowance_minutes') }}</label>
                            <input type="number" min="0" name="allowance_minutes" class="form-control" value="60">
                        </div>
                        <div class="form-group col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block">{{ __('message.save') }}</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('message.hr_staff_code') }}</th>
                            <th>{{ __('message.name') }}</th>
                            <th>{{ __('message.hr_staff_group') }}</th>
                            <th>{{ __('message.hr_monthly_salary') }}</th>
                            <th>{{ __('message.hr_allowance_minutes') }}</th>
                            <th>{{ __('message.status') }}</th>
                            <th>{{ __('message.action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($staff as $i => $member)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td colspan="7">
                                    <form method="POST" action="{{ route('hr.staff.update', $member->id) }}" class="row align-items-center no-gutters" style="gap:6px 0;">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-md-2 pr-1"><input name="code" class="form-control form-control-sm" value="{{ $member->code }}" required></div>
                                        <div class="col-md-3 pr-1"><input name="name" class="form-control form-control-sm" value="{{ $member->name }}" required></div>
                                        <div class="col-md-1 pr-1">
                                            <select name="staff_group" class="form-control form-control-sm">
                                                <option value="office" @selected($member->staff_group === 'office')>Office</option>
                                                <option value="rider" @selected($member->staff_group === 'rider')>Rider</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 pr-1"><input type="number" name="monthly_salary" class="form-control form-control-sm" value="{{ (int) $member->monthly_salary }}"></div>
                                        <div class="col-md-1 pr-1"><input type="number" name="allowance_minutes" class="form-control form-control-sm" value="{{ $member->allowance_minutes }}"></div>
                                        <div class="col-md-1 pr-1">
                                            <select name="status" class="form-control form-control-sm">
                                                <option value="1" @selected((int)$member->status === 1)>{{ __('message.active') }}</option>
                                                <option value="0" @selected((int)$member->status === 0)>{{ __('message.inactive') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex" style="gap:6px;">
                                            <button class="btn btn-sm btn-primary">{{ __('message.update') }}</button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('hr.staff.destroy', $member->id) }}" class="d-inline ml-1" onsubmit="return confirm('Delete?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">{{ __('message.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center">{{ __('message.no_record_found') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
